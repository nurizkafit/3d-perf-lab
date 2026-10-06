<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestResult;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\User;
use App\Support\Aggregate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Run lifecycle (BENCHMARK.md §4.1, §5): start → running → completed | invalid | cancelled.
 * The browser measures; this service validates, stores, aggregates (median of repetitions), and
 * evaluates the budget snapshot. It never calculates frame rates.
 */
class RunResultService
{
    public function __construct(private BudgetEvaluator $evaluator) {}

    public function start(TestSession $test, User $user, int $deviceClassId, array $environment): TestRun
    {
        return $test->runs()->create([
            'device_class_id' => $deviceClassId,
            'environment_json' => $environment,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $user->id,
        ]);
    }

    /** Invalid runs never produce budget results (BENCHMARK.md §4.1 step 5). */
    public function invalidate(TestRun $run, string $reason): void
    {
        $run->update(['status' => 'invalid', 'invalid_reason' => mb_substr($reason, 0, 250), 'completed_at' => now()]);
    }

    public function cancel(TestRun $run): void
    {
        $run->update(['status' => 'cancelled', 'completed_at' => now()]);
    }

    /** Marks running runs past their expiry as invalid ("expired"). Returns the number of runs. */
    public function expireStale(): int
    {
        $cutoff = now()->subMinutes(config('lab.run_expiry_minutes'));

        return TestRun::where('status', 'running')->where('started_at', '<', $cutoff)
            ->update(['status' => 'invalid', 'invalid_reason' => 'expired', 'completed_at' => now()]);
    }

    /**
     * Store the submitted results, evaluate the budget, and complete the run.
     *
     * @param  array{environment?: array, results: list<array<string, mixed>>}  $payload  validated by SubmitResultsRequest
     */
    public function submit(TestRun $run, array $payload): TestRun
    {
        $test = $run->session()->with('assets.metrics')->firstOrFail();
        $this->checkConsistency($test, $payload['results']);

        DB::transaction(function () use ($run, $test, $payload) {
            if (isset($payload['environment'])) {
                $run->environment_json = array_merge($run->environment_json ?? [], $payload['environment']);
            }

            foreach ($payload['results'] as $result) {
                $run->results()->create([
                    'asset_id' => $result['asset_id'] ?? null,
                    'step_instances' => $result['step_instances'] ?? null,
                    'repetition' => $result['repetition'],
                    'metrics_json' => array_filter([
                        'summary' => $result['metrics'],
                        'load' => $result['load'] ?? null,
                        'probes' => isset($result['probes']) ? array_map(fn ($probe) => array_intersect_key($probe, array_flip(['instances', 'pass', 'interval_p95_ms', 'jank_pct'])), $result['probes']) : null,
                        'flags' => $result['flags'] ?? null,
                    ]),
                    'samples_json' => $result['samples'] ?? null,
                ]);
            }

            foreach ($this->evaluations($test, $run->results()->get()) as $row) {
                $run->evaluations()->create($row);
            }

            $run->status = 'completed';
            $run->completed_at = now();
            $run->save();
        });

        return $run->fresh();
    }

    /** Stress items evaluated at each instance step (BENCHMARK.md §4.3). The others are evaluated once per asset. */
    public const STEP_ITEMS = ['max_p95_frame_ms', 'max_jank_pct', 'max_draw_calls'];

    /**
     * Median per asset (or per asset set, asset_id null) over repetitions, then the budget per asset.
     * Stress (decision D37): the frame items are evaluated at each step (rows with step_instances); file size,
     * texture memory, and "instances at budget" (the largest step whose median p95 meets the limit) once.
     */
    public function evaluations(TestSession $test, Collection $results): array
    {
        $items = $test->budget_json['items'] ?? [];
        $rows = [];

        foreach ($results->groupBy(fn (TestResult $r) => $r->asset_id ?? 'set') as $assetKey => $group) {
            $assetIds = $assetKey === 'set' ? $test->assets->pluck('id')->all() : [(int) $assetKey];
            $assets = $test->assets->whereIn('id', $assetIds);
            $assetId = $assetKey === 'set' ? null : (int) $assetKey;
            $byStep = $group->groupBy(fn ($r) => $r->step_instances ?? 0)->sortKeys();

            $static = [
                'file_size_bytes' => $assets->sum('file_size'),
                'texture_memory_estimate_bytes' => $assets->every(fn (Asset $a) => $a->metrics?->texture_memory_estimate_bytes !== null)
                    ? $assets->sum(fn (Asset $a) => $a->metrics->texture_memory_estimate_bytes) : null,
            ];

            if ($test->method === 'stress') {
                $summaryItems = array_diff_key($items, array_flip(self::STEP_ITEMS));
                $measured = $static + ['instances_at_budget' => $this->instancesAtBudget($byStep, $items['max_p95_frame_ms']['limit'] ?? null)];
                foreach ($this->evaluator->evaluate($summaryItems, 'stress', $measured) as $row) {
                    $rows[] = $row + ['asset_id' => $assetId, 'step_instances' => null];
                }
                $stepItems = array_intersect_key($items, array_flip(self::STEP_ITEMS));
                foreach ($byStep as $step => $stepResults) {
                    foreach ($this->evaluator->evaluate($stepItems, 'stress', $this->frameMedians($stepResults)) as $row) {
                        $rows[] = $row + ['asset_id' => $assetId, 'step_instances' => (int) $step];
                    }
                }

                continue;
            }

            $largest = $byStep->last();
            $loadMedian = fn (string $phase) => Aggregate::summary($largest->map(fn ($r) => $r->metrics_json['load'][$phase]['ttff_ms'] ?? null)->all())['median'] ?? null;
            $measured = $static + $this->frameMedians($largest) + [
                'ttff_cold_ms' => $loadMedian('cold'),
                'ttff_warm_ms' => $loadMedian('warm'),
            ];

            foreach ($this->evaluator->evaluate($items, $test->method, $measured) as $row) {
                $rows[] = $row + ['asset_id' => $assetId, 'step_instances' => null];
            }
        }

        return $rows;
    }

    /** Medians of the frame metrics over the repetitions of one step. */
    private function frameMedians(Collection $results): array
    {
        $summary = Aggregate::records($results->map(fn ($r) => $r->metrics_json['summary'] ?? [])->all());

        return [
            'interval_p95_ms' => $summary['interval_p95_ms']['median'] ?? null,
            'jank_pct' => $summary['jank_pct']['median'] ?? null,
            'draw_calls' => $summary['draw_calls']['median'] ?? null,
            'headroom_instances' => $summary['headroom_instances']['median'] ?? null,
        ];
    }

    private function instancesAtBudget(Collection $byStep, ?float $limit): ?int
    {
        if ($limit === null || $byStep->keys()->all() === [0]) {
            return null;
        }
        $passing = $byStep->filter(function ($results, $step) use ($limit) {
            $p95 = Aggregate::summary($results->map(fn ($r) => $r->metrics_json['summary']['interval_p95_ms'] ?? null)->all());

            return $step > 0 && $p95 !== null && $p95['median'] <= $limit;
        });

        return $passing->isEmpty() ? 0 : (int) $passing->keys()->max();
    }

    /** Asset ids must belong to the test; repetitions must be inside the configured range. */
    private function checkConsistency(TestSession $test, array $results): void
    {
        $assetIds = $test->assets->pluck('id')->all();
        $repetitions = (int) ($test->configuration_json['repetitions'] ?? 5);
        $errors = [];

        foreach ($results as $index => $result) {
            if (isset($result['asset_id']) && ! in_array((int) $result['asset_id'], $assetIds, true)) {
                $errors["results.{$index}.asset_id"] = 'This asset is not part of the test.';
            }
            if (! isset($result['asset_id']) && $test->scenario !== 'mixed') {
                $errors["results.{$index}.asset_id"] = 'Each result needs its asset.';
            }
            if ($test->method === 'headroom' && ! isset($result['metrics']['headroom_instances'])) {
                $errors["results.{$index}.metrics.headroom_instances"] = 'A Headroom result needs its headroom value.';
            }
            if ($test->method === 'stress' && ! in_array((int) ($result['step_instances'] ?? 0), $test->configuration_json['steps'] ?? [], true)) {
                $errors["results.{$index}.step_instances"] = 'This instance count is not one of the test steps.';
            }
            if ($result['repetition'] > $repetitions) {
                $errors["results.{$index}.repetition"] = "The test has {$repetitions} repetitions.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}

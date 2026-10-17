<?php

namespace App\Services;

use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\User;
use App\Support\Aggregate;
use App\Support\Smoothness;
use Illuminate\Support\Facades\DB;

/**
 * Run lifecycle (BENCHMARK.md §4.1, §5): start → running → completed | invalid | cancelled.
 * Side-by-Side results are stored and evaluated here; the Crowd Test is added in M4.4.
 */
class RunResultService
{
    public function __construct(private TargetEvaluator $evaluator) {}

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

    /**
     * Store the submitted results, aggregate the repetitions (median, min–max), and store the smoothness labels and
     * Target checks per asset (BENCHMARK.md §5). Arithmetic on submitted numbers, not measurement.
     *
     * @param  array{environment?: array, results: list<array>}  $payload  validated by SubmitResultsRequest
     */
    public function submit(TestRun $run, array $payload): void
    {
        $test = $run->session;
        if ($test->method === 'crowd') {
            $this->submitCrowd($run, $payload);

            return;
        }
        $assets = $test->assets()->get()->keyBy('id');

        DB::transaction(function () use ($run, $payload, $test, $assets) {
            foreach ($payload['results'] as $result) {
                $isLoad = ($result['motion'] ?? null) === null;
                $run->results()->create([
                    'asset_id' => $result['asset_id'],
                    'motion' => $result['motion'] ?? null,
                    'repetition' => $result['repetition'],
                    'metrics_json' => $isLoad
                        ? ['cold' => $result['load']['cold'] ?? null, 'warm' => $result['load']['warm'] ?? null, 'collider' => $result['collider'] ?? null]
                        : $result['metrics'],
                    'samples_json' => $isLoad ? null : ($result['samples'] ?? null),
                ]);
            }

            $byAsset = collect($payload['results'])->groupBy('asset_id');
            foreach ($assets as $assetId => $asset) {
                $rows = $byAsset->get($assetId, collect());
                $loads = $rows->whereNull('motion');
                $motions = [];
                foreach ($test->configuration_json['motions'] as $motion) {
                    $metrics = $rows->where('motion', $motion)->pluck('metrics')->all();
                    $motions[$motion] = [
                        'interval_p95_ms' => Aggregate::summary(array_column($metrics, 'interval_p95_ms'))['median'] ?? null,
                        'jank_pct' => Aggregate::summary(array_column($metrics, 'jank_pct'))['median'] ?? null,
                    ];
                }
                $checks = $this->evaluator->evaluate($test->target_json, [
                    'ttff_cold_ms' => Aggregate::summary($loads->pluck('load.cold.ttff_ms')->all())['median'] ?? null,
                    'motions' => $motions,
                    'file_size_bytes' => $asset->file_size,
                ]);
                foreach ($checks as $check) {
                    $run->checks()->create(['asset_id' => $assetId, ...$check]);
                }
            }

            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'environment_json' => array_merge($run->environment_json ?? [], $payload['environment'] ?? []),
            ]);
        });
    }

    /**
     * Crowd (BENCHMARK.md §4.6): store every probe with its smoothness label, and one row per search. The server
     * labels each probe from its submitted summary (§4.4) and derives `smooth_copies` = the largest Smooth probe,
     * so the result follows the same rule as everywhere else. The Crowd has no Target checks (decision D46).
     */
    private function submitCrowd(TestRun $run, array $payload): void
    {
        $test = $run->session;
        $perTool = $test->scenario === 'per_tool';
        [, $objects] = $test->crowdShape();
        $capCopies = max(1, intdiv($test->configuration_json['cap'], max(1, $objects)));
        $fps = $test->target_json['target_fps'];

        DB::transaction(function () use ($run, $payload, $perTool, $objects, $capCopies, $fps) {
            $rows = collect($payload['results']);
            $groupOf = fn (array $row) => $perTool ? $row['group_tool_id'] : $row['asset_id'];
            foreach ($rows->whereNull('search') as $probe) {
                $metrics = $probe['metrics'];
                $metrics['label'] = Smoothness::label($metrics['interval_p95_ms'] ?? null, $metrics['jank_pct'] ?? null, $fps);
                $metrics['instances'] = $probe['probe_copies'] * $objects;
                $run->results()->create([
                    'asset_id' => $perTool ? null : $probe['asset_id'],
                    'group_tool_id' => $perTool ? $probe['group_tool_id'] : null,
                    'motion' => $probe['motion'],
                    'probe_copies' => $probe['probe_copies'],
                    'repetition' => $probe['repetition'],
                    'metrics_json' => $metrics,
                ]);
            }
            foreach ($rows->whereNotNull('search') as $search) {
                $probes = $rows->whereNull('search')->filter(fn ($probe) => $groupOf($probe) === $groupOf($search)
                    && $probe['motion'] === $search['motion'] && $probe['repetition'] === $search['repetition']);
                $smooth = $probes->filter(fn ($probe) => Smoothness::label($probe['metrics']['interval_p95_ms'] ?? null, $probe['metrics']['jank_pct'] ?? null, $fps) === 'smooth');
                $copies = (int) ($smooth->max('probe_copies') ?? 0);
                $run->results()->create([
                    'asset_id' => $perTool ? null : $search['asset_id'],
                    'group_tool_id' => $perTool ? $search['group_tool_id'] : null,
                    'motion' => $search['motion'],
                    'probe_copies' => null,
                    'repetition' => $search['repetition'],
                    'metrics_json' => [
                        'smooth_copies' => $copies,
                        'smooth_instances' => $copies * $objects,
                        'cap_copies' => $capCopies,
                        'at_cap' => $copies >= $capCopies,
                        'cap_reached' => (bool) ($search['search']['cap_reached'] ?? false),
                        'time_limit_reached' => (bool) ($search['search']['time_limit_reached'] ?? false),
                        'probes' => $probes->count(),
                    ],
                ]);
            }

            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'environment_json' => array_merge($run->environment_json ?? [], $payload['environment'] ?? []),
            ]);
        });
    }

    /** Invalid runs never produce results or Target checks (BENCHMARK.md §4.1 step 5). */
    public function invalidate(TestRun $run, string $reason): void
    {
        $run->update(['status' => 'invalid', 'invalid_reason' => mb_substr($reason, 0, 250), 'completed_at' => now()]);
    }

    public function cancel(TestRun $run): void
    {
        $run->update(['status' => 'cancelled', 'completed_at' => now()]);
    }

    /**
     * Marks running runs past their expiry as invalid ("expired"). The expiry depends on each run's estimate
     * (decision D59), so the candidates older than the minimum are checked one by one. Returns the number of runs.
     */
    public function expireStale(): int
    {
        $cutoff = now()->subMinutes(config('lab.run_expiry_minutes'));
        $expired = TestRun::where('status', 'running')->where('started_at', '<', $cutoff)->with(['session', 'deviceClass'])->get()
            ->filter(fn (TestRun $run) => $run->isExpired());
        $expired->each(fn (TestRun $run) => $run->update(['status' => 'invalid', 'invalid_reason' => 'expired', 'completed_at' => now()]));

        return $expired->count();
    }
}

<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\TestRun;
use App\Models\TestSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Shapes one test and its run for the results page (PRD.md §8.4, DESIGN.md §9.4). Assets always in
 * selection order; values are medians of the repetitions with the min–max spread. No sorting by result.
 */
class TestResultPresenter
{
    /** Runtime rows of the metric table: summary key => label, unit, explanation. */
    public const RUNTIME_ROWS = [
        'interval_p95_ms', 'interval_p50_ms', 'work_p95_ms', 'gpu_p95_ms', 'jank_pct', 'draw_calls',
    ];

    public function __construct(public TestSession $test, public ?TestRun $run) {}

    public static function for(TestSession $test): self
    {
        $test->loadMissing(['assets.metrics', 'assets.tool', 'assets.uploader']);
        $run = $test->runs()->with(['results', 'evaluations', 'deviceClass'])
            ->orderByRaw("status = 'completed' desc")->latest('id')->first();

        return new self($test, $run);
    }

    public function completed(): bool
    {
        return $this->run?->status === 'completed';
    }

    /** Rows for components/assets/tested-assets. */
    public function assets(): array
    {
        return $this->test->assets->map(fn (Asset $asset) => [
            'id' => $asset->id,
            'name' => $asset->name,
            'tool' => $asset->tool->name,
            'author' => $asset->author,
            'format' => strtoupper($asset->format),
            'size' => Number::fileSize($asset->file_size, maxPrecision: 1),
            'vertices' => $asset->metrics?->vertices ?? 0,
            'triangles' => $asset->metrics?->triangles ?? 0,
            'hours' => (float) $asset->production_time_hours,
            'uploaded_by' => $asset->uploader->name,
        ])->values()->all();
    }

    /** Data for components/performance/budget-table: item => [limit, [outcome, measured] per asset]. */
    public function budget(): array
    {
        if (! $this->completed()) {
            return [];
        }
        $byAsset = $this->run->evaluations->groupBy('asset_id');
        $rows = [];
        foreach (MetricDefinitions::BUDGET_ITEMS as $key => $definition) {
            $cells = [];
            $limit = null;
            foreach ($this->test->assets as $asset) {
                $evaluation = $byAsset->get($asset->id)?->firstWhere('item_key', $key);
                if ($evaluation) {
                    $limit = $evaluation->limit_value;
                    $cells[] = [str_replace('_', '-', $evaluation->outcome), $evaluation->measured_value];
                } else {
                    $cells[] = ['not-measured', null];
                }
            }
            if ($limit !== null) {
                $rows[$key] = [self::number($limit), ...array_map(fn ($cell) => [$cell[0], $cell[1] === null ? null : self::measured($cell[1])], $cells)];
            }
        }

        return $rows;
    }

    /** Assets that meet every enabled budget item (summary line, not a score). */
    public function meetingAll(): int
    {
        return $this->test->assets->filter(function (Asset $asset) {
            $rows = $this->run?->evaluations->where('asset_id', $asset->id) ?? collect();

            return $rows->isNotEmpty() && $rows->every(fn ($row) => $row->outcome === 'pass');
        })->count();
    }

    /** Per asset: aggregated summary keys and load phases (median, min, max). */
    public function aggregates(): array
    {
        if (! $this->completed()) {
            return [];
        }
        $byAsset = $this->run->results->groupBy('asset_id');

        return $this->test->assets->mapWithKeys(function (Asset $asset) use ($byAsset) {
            $results = $byAsset->get($asset->id, collect());
            $summary = Aggregate::records($results->map(fn ($r) => $r->metrics_json['summary'] ?? [])->all());
            $load = [];
            foreach (['cold', 'warm'] as $phase) {
                $load[$phase] = Aggregate::records($results->map(fn ($r) => $r->metrics_json['load'][$phase] ?? [])->all());
            }

            return [$asset->id => [
                'summary' => $summary,
                'load' => $load,
                'unstable' => Aggregate::unstable($summary['interval_p95_ms'] ?? null),
                'repetitions' => $results->count(),
            ]];
        })->all();
    }

    /** "16.9 (16.7–17.4)" or "—" when not measured. */
    public static function spread(?array $summary, int $digits = 1): string
    {
        if ($summary === null) {
            return '—';
        }
        $median = self::number($summary['median'], $digits);
        if ($summary['n'] < 2 || $summary['min'] == $summary['max']) {
            return $median;
        }

        return $median.' ('.self::number($summary['min'], $digits).'–'.self::number($summary['max'], $digits).')';
    }

    /** Thousands separators; trailing decimal zeros dropped (never integer zeros). */
    public static function number(float|int $value, int $digits = 1): string
    {
        $formatted = number_format($value, $digits, '.', ',');

        return $digits > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }

    /** Measured budget values: enough decimals that small values (0.01 MB) do not show as 0. */
    public static function measured(float $value): string
    {
        return self::number($value, abs($value) < 1 && $value != 0 ? 3 : 1);
    }

    /** Chart data for features/results.js: load-phase medians and all frame-interval samples per asset. */
    public function charts(): array
    {
        if (! $this->completed()) {
            return [];
        }
        $aggregates = $this->aggregates();
        $byAsset = $this->run->results->groupBy('asset_id');
        $p95 = $this->test->budget_json['items']['max_p95_frame_ms'] ?? null;

        return [
            'assets' => $this->test->assets->map(fn (Asset $asset) => [
                'name' => $asset->name,
                'load' => collect(['fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms'])->mapWithKeys(fn ($key) => [
                    $key => [
                        'cold' => $aggregates[$asset->id]['load']['cold'][$key]['median'] ?? 0,
                        'warm' => $aggregates[$asset->id]['load']['warm'][$key]['median'] ?? 0,
                    ],
                ])->all(),
                'intervals' => $byAsset->get($asset->id, collect())->flatMap(fn ($r) => $r->samples_json['interval'] ?? [])->values()->all(),
            ])->values()->all(),
            'budget_p95_ms' => ($p95['enabled'] ?? false) ? $p95['limit'] : null,
        ];
    }

    /** Environment summary rows (PRD.md §10.3 item 6). */
    public function environment(): array
    {
        $env = $this->run?->environment_json ?? [];
        $resolution = $env['resolution'] ?? ($this->test->configuration_json['resolution']['values'] ?? null);

        return array_filter([
            'Device class' => $this->run?->deviceClass?->name,
            'Browser (user agent)' => $env['user_agent'] ?? null,
            'GPU (browser-reported)' => $env['gpu_renderer'] ?? 'Not exposed by the browser',
            'Refresh rate (estimate)' => isset($env['refresh_hz_estimate']) ? $env['refresh_hz_estimate'].' Hz' : null,
            'Drawing buffer' => $resolution ? $resolution['width'].' × '.$resolution['height'] : null,
            'Three.js' => isset($env['three_revision']) ? 'r'.$env['three_revision'] : null,
            'CPU threads' => $env['hardware_concurrency'] ?? null,
            'Animation' => $env['animation'] ?? null,
            'Shuffle seed' => $env['shuffle_seed'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /** Configuration rows (PRD.md §10.3 item 5). */
    public function configuration(): array
    {
        $c = $this->test->configuration_json;

        return array_filter([
            'Repetitions' => $c['repetitions'] ?? null,
            'Warm-up' => isset($c['warmup_s']) ? $c['warmup_s'].' s' : null,
            'Measurement window' => isset($c['measurement_s']) ? $c['measurement_s'].' s' : null,
            'Resolution' => $c['resolution']['name'] ?? null,
            'Camera' => $c['camera']['name'] ?? null,
            'Lighting' => $c['lighting']['name'] ?? null,
            'Background' => $c['background']['name'] ?? null,
            'Scenario' => $this->test->scenario ? ucfirst($this->test->scenario).($this->test->scenario === 'mixed' ? ' assets (one tool)' : ' asset') : null,
            'Instance steps' => isset($c['steps']) ? implode(' / ', $c['steps']) : null,
            'Instance mode' => $c['instance_mode'] ?? null,
            'Budget preset' => $this->test->budget_json['preset_name'] ?? null,
        ], fn ($value) => $value !== null);
    }

    public function assetCount(): int
    {
        return $this->test->assets->count();
    }

    /** @return Collection<int, Asset> */
    public function assetModels(): Collection
    {
        return $this->test->assets;
    }

    // ---------- Stress (BENCHMARK.md §4.3, decision D37) ----------

    /** Result groups: one per asset (repeated) or one for the asset set (mixed, asset_id null). */
    public function stressGroups(): array
    {
        if (! $this->completed()) {
            return [];
        }
        $mixed = $this->test->scenario === 'mixed';
        $label = $mixed ? 'Mixed set: '.$this->test->assets->pluck('name')->join(', ') : $this->test->assets->first()?->name;
        $results = $this->run->results->where('asset_id', $mixed ? null : $this->test->assets->first()?->id);
        $evaluations = $this->run->evaluations->where('asset_id', $mixed ? null : $this->test->assets->first()?->id);

        $steps = $results->groupBy('step_instances')->sortKeys()->map(function ($stepResults, $step) use ($evaluations) {
            $summary = Aggregate::records($stepResults->map(fn ($r) => $r->metrics_json['summary'] ?? [])->all());

            return [
                'instances' => (int) $step,
                'summary' => $summary,
                'unstable' => Aggregate::unstable($summary['interval_p95_ms'] ?? null),
                'budget' => $evaluations->where('step_instances', (int) $step)->keyBy('item_key')
                    ->map(fn ($e) => [str_replace('_', '-', $e->outcome), $e->measured_value === null ? null : self::measured($e->measured_value), self::number($e->limit_value)])->all(),
            ];
        })->values()->all();

        return [[
            'label' => $label,
            'steps' => $steps,
            'summary_budget' => $evaluations->whereNull('step_instances')->keyBy('item_key')
                ->map(fn ($e) => [str_replace('_', '-', $e->outcome), $e->measured_value === null ? null : self::measured($e->measured_value), self::number($e->limit_value)])->all(),
        ]];
    }

    /** Chart data: medians per step (BENCHMARK.md §4.3 results UI). */
    public function stressChart(): array
    {
        $group = $this->stressGroups()[0] ?? null;
        $p95 = $this->test->budget_json['items']['max_p95_frame_ms'] ?? null;
        $median = fn ($step, $key) => $step['summary'][$key]['median'] ?? null;

        return $group ? [
            'label' => $group['label'],
            'instances' => array_column($group['steps'], 'instances'),
            'interval_p95' => array_map(fn ($step) => $median($step, 'interval_p95_ms'), $group['steps']),
            'work_p95' => array_map(fn ($step) => $median($step, 'work_p95_ms'), $group['steps']),
            'gpu_p95' => array_map(fn ($step) => $median($step, 'gpu_p95_ms'), $group['steps']),
            'budget_p95_ms' => ($p95['enabled'] ?? false) ? $p95['limit'] : null,
        ] : [];
    }

    /** Largest step whose p95 frame interval passed, from the stored per-step evaluations (shown when the item is disabled). */
    public static function largestPassingStep(TestRun $run): ?int
    {
        $rows = $run->evaluations->where('item_key', 'max_p95_frame_ms')->whereNotNull('step_instances');
        if ($rows->isEmpty()) {
            return null; // the p95 item is disabled
        }
        $passing = $rows->where('outcome', 'pass');

        return $passing->isEmpty() ? 0 : (int) $passing->max('step_instances');
    }
}

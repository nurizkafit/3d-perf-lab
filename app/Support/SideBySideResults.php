<?php

namespace App\Support;

use App\Models\TestRun;
use App\Models\TestSession;
use App\Services\TargetEvaluator;

/**
 * View data for the Side-by-Side results (BENCHMARK.md §4.3–§4.5, PRD.md §8.5): per asset in selection order the
 * Target checks, smoothness labels, load times, and the best markers; the aggregated metrics per motion scenario.
 */
class SideBySideResults
{
    public const LOAD_PHASES = ['fetch_ms' => 'Download', 'parse_ms' => 'Parse', 'gpu_upload_ms' => 'GPU upload', 'first_frame_ms' => 'First frame'];

    /** Metrics in Technical details, per motion scenario (BENCHMARK.md §3.3). */
    public const DETAIL_KEYS = [
        'interval_p50_ms', 'interval_p95_ms', 'interval_p99_ms', 'fps_avg', 'fps_1pct_low', 'jank_pct',
        'work_p50_ms', 'work_p95_ms', 'physics_p95_ms', 'gpu_p95_ms', 'draw_calls', 'rendered_triangles', 'textures_in_memory', 'js_heap_used', 'frames',
    ];

    public static function for(TestSession $test, TestRun $run): array
    {
        $motions = $test->configuration_json['motions'];
        $results = $run->results()->get()->groupBy('asset_id');
        $checks = $run->checks()->get()->groupBy('asset_id');
        $assets = [];

        foreach ($test->assets as $asset) {
            $rows = $results->get($asset->id, collect());
            $loads = $rows->whereNull('motion')->pluck('metrics_json');
            $assetChecks = $checks->get($asset->id, collect());
            $metrics = [];
            foreach ($motions as $motion) {
                $metrics[$motion] = Aggregate::records($rows->where('motion', $motion)->pluck('metrics_json')->all());
            }

            $assets[$asset->id] = [
                'asset' => $asset,
                'checks' => $assetChecks->map(fn ($check) => $check->only(['check_key', 'motion', 'limit_value', 'measured_value', 'label', 'outcome']))->values()->all(),
                'meets_all' => TargetEvaluator::meetsAll($assetChecks->map->only(['outcome'])->all()),
                'labels' => $assetChecks->where('check_key', 'smooth')->mapWithKeys(fn ($check) => [$check->motion => ['label' => $check->label, 'fps' => $check->measured_value]])->all(),
                'cold' => Aggregate::summary($loads->pluck('cold.ttff_ms')->all()),
                'warm' => Aggregate::summary($loads->pluck('warm.ttff_ms')->all()),
                'phases' => collect(self::LOAD_PHASES)->mapWithKeys(fn ($label, $key) => [$key => Aggregate::summary($loads->pluck("cold.{$key}")->all())['median'] ?? null])->all(),
                'collider' => $loads->pluck('collider')->filter()->first(),
                'metrics' => $metrics,
                'unstable' => collect($metrics)->map(fn ($m) => Aggregate::unstable($m['interval_p95_ms'] ?? null))->all(),
            ];
        }

        $markers = [
            'fastest_load' => BestMarkers::lowestSpread(array_map(fn ($a) => $a['cold'], $assets)),
            'smallest_file' => BestMarkers::lowestStatic(array_map(fn ($a) => $a['asset']->file_size, $assets)),
            'lightest' => collect($motions)->mapWithKeys(fn ($motion) => [$motion => BestMarkers::lowestSpread(array_map(fn ($a) => $a['metrics'][$motion]['work_p95_ms'] ?? null, $assets))])->all(),
        ];

        // The markers each asset earned, for its pane.
        foreach ($assets as $id => &$entry) {
            $entry['markers'] = [];
            foreach (['fastest_load', 'smallest_file'] as $key) {
                if (in_array($id, $markers[$key]['winners'], true)) {
                    $entry['markers'][] = BestMarkers::MARKERS[$key]['label'];
                }
            }
            foreach ($markers['lightest'] as $motion => $marker) {
                if (in_array($id, $marker['winners'], true)) {
                    $entry['markers'][] = BestMarkers::MARKERS['lightest']['label'].' · '.MetricDefinitions::MOTIONS[$motion]['label'];
                }
            }
        }
        unset($entry);

        return ['run' => $run, 'motions' => $motions, 'assets' => $assets, 'markers' => $markers];
    }
}

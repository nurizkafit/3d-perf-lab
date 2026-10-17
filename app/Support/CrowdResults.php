<?php

namespace App\Support;

use App\Models\TestRun;
use App\Models\TestSession;

/**
 * View data for the Crowd results (BENCHMARK.md §4.6, PRD.md §8.5): per group (asset or tool set) in selection order
 * and per motion scenario the median `smooth_copies` with its spread and flags, the Most copies marker, the probe
 * sequences, and the explore viewer configuration.
 */
class CrowdResults
{
    public static function for(TestSession $test, TestRun $run): array
    {
        $motions = $test->configuration_json['motions'];
        $perTool = $test->scenario === 'per_tool';
        [, $objects] = $test->crowdShape();
        $capCopies = max(1, intdiv($test->configuration_json['cap'], max(1, $objects)));
        $rows = $run->results()->orderBy('id')->get();
        $groupKey = fn ($row) => $perTool ? $row->group_tool_id : $row->asset_id;
        $groups = [];

        foreach (CrowdGroups::for($test) as $group) {
            $mine = $rows->filter(fn ($row) => (int) $groupKey($row) === (int) $group['key']);
            $byMotion = [];
            foreach ($motions as $motion) {
                $searches = $mine->where('motion', $motion)->whereNull('probe_copies')->sortBy('repetition');
                $probes = $mine->where('motion', $motion)->whereNotNull('probe_copies');
                $byMotion[$motion] = [
                    'copies' => Aggregate::summary($searches->map(fn ($s) => $s->metrics_json['smooth_copies'])->all()),
                    'at_cap' => $searches->contains(fn ($s) => $s->metrics_json['at_cap'] ?? false),
                    'time_limit' => $searches->contains(fn ($s) => $s->metrics_json['time_limit_reached'] ?? false),
                    'repetitions' => $searches->map(fn ($s) => ['repetition' => $s->repetition, ...$s->metrics_json])->values()->all(),
                    'probes' => $probes->groupBy('repetition')->map(fn ($list) => $list->map(fn ($p) => [
                        'copies' => $p->probe_copies,
                        'instances' => $p->metrics_json['instances'] ?? $p->probe_copies * $objects,
                        'label' => $p->metrics_json['label'] ?? null,
                        'interval_p95_ms' => $p->metrics_json['interval_p95_ms'] ?? null,
                        'jank_pct' => $p->metrics_json['jank_pct'] ?? null,
                        'work_p95_ms' => $p->metrics_json['work_p95_ms'] ?? null,
                        'physics_p95_ms' => $p->metrics_json['physics_p95_ms'] ?? null,
                        'draw_calls' => $p->metrics_json['draw_calls'] ?? null,
                        'frames' => $p->metrics_json['frames'] ?? null,
                    ])->values()->all())->all(),
                ];
            }
            $groups[$group['key']] = [...$group, 'motions' => $byMotion];
        }

        $markers = collect($motions)->mapWithKeys(fn ($motion) => [$motion => BestMarkers::highestSpread(array_map(fn ($g) => $g['motions'][$motion]['copies'], $groups))])->all();
        $largest = max(1, collect($groups)->flatMap(fn ($g) => collect($g['motions'])->pluck('copies.median'))->max() ?? 1);

        return [
            'run' => $run,
            'motions' => $motions,
            'groups' => $groups,
            'markers' => $markers,
            'cap_copies' => $capCopies,
            'objects' => $objects,
            'largest' => $largest,
            'explorer' => [
                'camera' => $test->configuration_json['camera']['values'] ?? null,
                'lighting' => $test->configuration_json['lighting']['values'] ?? null,
                'background' => $test->configuration_json['background']['values'] ?? null,
                'lookCopies' => 16,
                'groups' => collect($groups)->map(fn ($g) => [
                    'label' => $g['label'],
                    'assets' => $g['assets']->map(fn ($asset) => ['url' => route('assets.file', [$asset, basename($asset->web_file_path)])])->values()->all(),
                    'defaults' => collect($g['motions'])->map(fn ($m) => (int) round($m['copies']['median'] ?? 1))->all(),
                ])->values()->all(),
            ],
        ];
    }
}

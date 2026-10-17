<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\TestRun;
use App\Support\Aggregate;
use App\Support\BestMarkers;
use App\Support\CommonObjects;
use App\Support\MetricDefinitions;
use Illuminate\Support\Collection;

/**
 * Tool summary (BENCHMARK.md §4.7, PRD.md §8.3): a view, not a test. For one device class and two or more tools it
 * compares the objects that every tool has, with the results that already exist. Per asset it uses the most recent
 * completed result on that device class: Side-by-Side for load and smoothness, Crowd Per asset for copies. Tool crowd
 * results (Crowd Per tool) are shown as they were measured. Medians across objects summarise measured values; they
 * are not a score.
 */
class ToolSummaryService
{
    private ?int $ownerId = null;

    /**
     * @param  list<int>  $toolIds
     * @param  list<int>  $excludedObjectIds
     * @param  int|null  $ownerId  only this user's tests (SITEMAP.md §3.4); null for an admin (all tests)
     */
    public function build(int $deviceClassId, array $toolIds, array $excludedObjectIds = [], ?int $ownerId = null): array
    {
        $this->ownerId = $ownerId;
        $common = CommonObjects::for($toolIds);
        $objects = collect($common['objects'])->reject(fn ($object) => in_array($object['id'], $excludedObjectIds, true))->values();
        $candidateIds = $objects->flatMap(fn ($object) => collect($object['assets'])->flatten(1)->pluck('id'))->unique()->all();

        // Completed runs on this device class that include any candidate asset or any selected tool, newest first.
        $runs = TestRun::where('status', 'completed')->where('device_class_id', $deviceClassId)
            ->whereHas('session', fn ($q) => $q->when($ownerId, fn ($o) => $o->where('created_by', $ownerId))->whereHas('assets', fn ($a) => $a->whereIn('assets.id', $candidateIds ?: [0])))
            ->with(['session.assets:id', 'results' => fn ($q) => $q->withoutSamples(), 'checks'])
            ->orderByDesc('completed_at')->orderByDesc('id')->get();

        $assets = Asset::with('metrics')->whereIn('id', $candidateIds)->get()->keyBy('id');
        $cells = []; // [objectId][toolId] => per-asset values with their source tests
        foreach ($objects as $object) {
            foreach ($common['tools'] as $tool) {
                $cells[$object['id']][$tool['id']] = $this->cell($object['assets'][$tool['id']], $runs, $assets);
            }
        }

        $motions = array_keys(MetricDefinitions::MOTIONS);
        $tools = [];
        foreach ($common['tools'] as $tool) {
            $column = collect($cells)->pluck($tool['id']);
            $tools[$tool['id']] = [
                'id' => $tool['id'],
                'name' => $tool['name'],
                'objects' => $column->count(),
                'file_size' => Aggregate::summary($column->pluck('file_size')->all())['median'] ?? null,
                'triangles' => Aggregate::summary($column->pluck('triangles')->all())['median'] ?? null,
                'cold_ms' => Aggregate::summary($column->pluck('cold_ms')->all())['median'] ?? null,
                'tested' => $column->filter(fn ($c) => $c['side_by_side'] !== null)->count(),
                'checks' => $this->checkCounts($column),
                'copies' => collect($motions)->mapWithKeys(fn ($m) => [$m => Aggregate::summary($column->pluck("copies.{$m}")->all())['median'] ?? null])->all(),
                'crowd_tested' => $column->filter(fn ($c) => $c['crowd'] !== null)->count(),
                'tool_crowds' => $this->toolCrowds($tool['id'], $toolIds, $deviceClassId),
                'sources' => $column->flatMap(fn ($c) => array_filter([$c['side_by_side'], $c['crowd']]))->unique('test_id')->values()->all(),
            ];
        }

        $byTool = fn (string $key) => collect($tools)->mapWithKeys(fn ($t, $id) => [$id => data_get($t, $key)])->all();
        $markers = [
            'smallest_file' => BestMarkers::lowestStatic($byTool('file_size')),
            'fastest_load' => BestMarkers::lowestStatic($byTool('cold_ms')),
            'most_copies' => collect($motions)->mapWithKeys(fn ($m) => [$m => self::highestStatic($byTool("copies.{$m}"))])->all(),
        ];

        return [
            'tools' => $tools,
            'objects' => $objects->all(),
            'missing' => $common['missing'],
            'excluded' => collect($common['objects'])->filter(fn ($o) => in_array($o['id'], $excludedObjectIds, true))->values()->all(),
            'cells' => $cells,
            'motions' => $motions,
            'markers' => $markers,
        ];
    }

    /**
     * One object in one tool. With several candidate assets, the newest asset that has a result on this device class
     * is used; without any result, the newest asset ("Not tested").
     */
    private function cell(array $candidates, Collection $runs, Collection $assets): array
    {
        $chosen = null;
        foreach ($candidates as $candidate) {
            if ($this->latestRun($runs, $candidate['id'], 'side_by_side') || $this->latestRun($runs, $candidate['id'], 'crowd')) {
                $chosen = $candidate;
                break;
            }
        }
        $chosen ??= $candidates[0];
        $asset = $assets->get($chosen['id']);
        $sbs = $this->latestRun($runs, $chosen['id'], 'side_by_side');
        $crowd = $this->latestRun($runs, $chosen['id'], 'crowd');

        $cell = [
            'asset' => $chosen,
            'file_size' => $asset?->file_size,
            'triangles' => $asset?->metrics?->triangles,
            'cold_ms' => null,
            'checks' => [],
            'target' => null,
            'copies' => [],
            'side_by_side' => null,
            'crowd' => null,
        ];
        if ($sbs) {
            $cell['cold_ms'] = Aggregate::summary($sbs->results->where('asset_id', $chosen['id'])->whereNull('motion')->pluck('metrics_json.cold.ttff_ms')->all())['median'] ?? null;
            $cell['checks'] = $sbs->checks->where('asset_id', $chosen['id'])->map(fn ($c) => $c->only(['check_key', 'motion', 'outcome', 'label', 'measured_value']))->values()->all();
            $cell['target'] = $sbs->session->target_json['name'] ?? null;
            $cell['side_by_side'] = ['test_id' => $sbs->test_session_id, 'name' => $sbs->session->name, 'method' => 'side_by_side'];
        }
        if ($crowd) {
            foreach ($crowd->results->where('asset_id', $chosen['id'])->whereNull('probe_copies')->groupBy('motion') as $motion => $rows) {
                $cell['copies'][$motion] = Aggregate::summary($rows->pluck('metrics_json.smooth_copies')->all())['median'] ?? null;
            }
            $cell['crowd'] = ['test_id' => $crowd->test_session_id, 'name' => $crowd->session->name, 'method' => 'crowd'];
        }

        return $cell;
    }

    /** Most recent completed run of a method that includes the asset (Crowd: Per asset only). */
    private function latestRun(Collection $runs, int $assetId, string $method): ?TestRun
    {
        return $runs->first(fn (TestRun $run) => $run->session->method === $method
            && ($method !== 'crowd' || $run->session->scenario === 'per_asset')
            && $run->session->assets->contains('id', $assetId));
    }

    /** "18 of 20 load in time": per check (and motion), passes out of assets with that check. */
    private function checkCounts(Collection $column): array
    {
        $counts = [];
        foreach ($column as $cell) {
            foreach ($cell['checks'] as $check) {
                $key = $check['check_key'].($check['motion'] ? ':'.$check['motion'] : '');
                $counts[$key] ??= ['check_key' => $check['check_key'], 'motion' => $check['motion'], 'pass' => 0, 'total' => 0, 'targets' => []];
                $counts[$key]['total']++;
                $counts[$key]['pass'] += $check['outcome'] === 'pass' ? 1 : 0;
                if ($cell['target']) {
                    $counts[$key]['targets'][$cell['target']] = true;
                }
            }
        }

        return collect($counts)->map(fn ($c) => [...$c, 'targets' => array_keys($c['targets'])])->sortBy(fn ($c) => array_search($c['check_key'], ['load', 'smooth', 'file_size']).($c['motion'] ? array_search($c['motion'], array_keys(MetricDefinitions::MOTIONS)) : ''))->all();
    }

    /** Crowd Per tool results that include this tool, as measured (newest first, at most five). */
    private function toolCrowds(int $toolId, array $toolIds, int $deviceClassId): array
    {
        return TestRun::where('status', 'completed')->where('device_class_id', $deviceClassId)
            ->whereHas('session', fn ($q) => $q->where('method', 'crowd')->where('scenario', 'per_tool')->when($this->ownerId, fn ($o) => $o->where('created_by', $this->ownerId)))
            ->with(['session', 'results' => fn ($q) => $q->withoutSamples()->where('group_tool_id', $toolId)->whereNull('probe_copies')])
            ->orderByDesc('completed_at')->get()
            ->filter(fn (TestRun $run) => in_array($toolId, $run->session->configuration_json['tool_ids'] ?? [], true))
            ->take(5)
            ->map(fn (TestRun $run) => [
                'test_id' => $run->test_session_id,
                'name' => $run->session->name,
                'objects' => count($run->session->configuration_json['object_ids'] ?? []),
                'tools' => count($run->session->configuration_json['tool_ids'] ?? []),
                'copies' => $run->results->groupBy('motion')->map(fn ($rows) => Aggregate::summary($rows->pluck('metrics_json.smooth_copies')->all())['median'] ?? null)->all(),
            ])->values()->all();
    }

    /** Higher is better for a static value; equal values share the marker. */
    public static function highestStatic(array $values): array
    {
        $negated = array_map(fn ($v) => $v === null ? null : -$v, $values);

        return BestMarkers::lowestStatic($negated);
    }
}

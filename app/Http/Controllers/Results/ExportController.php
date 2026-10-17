<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\TestSession;
use App\Models\Tool;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports (PRD.md §10.6): JSON with everything, including raw samples; CSV with one row per stored result
 * (asset or tool set, motion scenario, probe, repetition). Times stay in UTC (decision D38). Owner or admin.
 */
class ExportController extends Controller
{
    private const CSV_METRICS = [
        'frames', 'interval_p50_ms', 'interval_p95_ms', 'interval_p99_ms', 'fps_avg', 'fps_1pct_low', 'jank_pct', 'work_p50_ms', 'work_p95_ms',
        'physics_p95_ms', 'gpu_p95_ms', 'draw_calls', 'rendered_triangles', 'label', 'smooth_copies', 'smooth_instances', 'at_cap', 'cap_reached', 'time_limit_reached',
    ];

    public function json(TestSession $test): JsonResponse
    {
        Gate::authorize('export', $test);
        $test->load(['assets.tool', 'assets.object', 'assets.metrics', 'runs.deviceClass', 'runs.results', 'runs.checks']);

        return response()->json([
            'test' => $test->only(['id', 'name', 'method', 'scenario', 'configuration_json', 'target_json', 'created_at']),
            'assets' => $test->assets->map(fn ($asset) => [
                'id' => $asset->id, 'name' => $asset->name, 'tool' => $asset->tool->name, 'object' => $asset->object?->name, 'author' => $asset->author,
                'format' => $asset->format, 'file_size' => $asset->file_size, 'position' => $asset->pivot->position, 'group_tool_id' => $asset->pivot->group_tool_id,
                'metrics' => $asset->metrics?->only(['vertices', 'triangles', 'meshes', 'materials', 'textures', 'texture_memory_estimate_bytes']),
            ]),
            'runs' => $test->runs->map(fn ($run) => [
                ...$run->only(['id', 'status', 'invalid_reason', 'started_at', 'completed_at', 'environment_json']),
                'device_class' => $run->deviceClass->name,
                'results' => $run->results->map->only(['asset_id', 'group_tool_id', 'motion', 'probe_copies', 'repetition', 'metrics_json', 'samples_json']),
                'target_checks' => $run->checks->map->only(['asset_id', 'motion', 'check_key', 'limit_value', 'measured_value', 'label', 'outcome']),
            ]),
        ], 200, ['Content-Disposition' => 'attachment; filename="'.$this->filename($test).'.json"']);
    }

    public function csv(TestSession $test): StreamedResponse
    {
        Gate::authorize('export', $test);
        $test->load(['assets', 'runs.deviceClass', 'runs.results']);
        $names = $test->assets->pluck('name', 'id');
        $tools = Tool::pluck('name', 'id');

        return response()->streamDownload(function () use ($test, $names, $tools) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['run_id', 'run_status', 'device_class', 'asset', 'tool_set', 'motion', 'probe_copies', 'repetition', 'ttff_cold_ms', 'ttff_warm_ms', ...self::CSV_METRICS]);
            foreach ($test->runs as $run) {
                foreach ($run->results as $row) {
                    $m = $row->metrics_json;
                    fputcsv($out, [
                        $run->id, $run->status, $run->deviceClass->name, $names[$row->asset_id] ?? '', $tools[$row->group_tool_id] ?? '',
                        $row->motion ?? 'load', $row->probe_copies, $row->repetition, $m['cold']['ttff_ms'] ?? '', $m['warm']['ttff_ms'] ?? '',
                        ...array_map(fn ($key) => is_bool($m[$key] ?? null) ? (int) $m[$key] : ($m[$key] ?? ''), self::CSV_METRICS),
                    ]);
                }
            }
            fclose($out);
        }, $this->filename($test).'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filename(TestSession $test): string
    {
        return (Str::slug($test->name) ?: 'test').'-'.$test->id;
    }
}

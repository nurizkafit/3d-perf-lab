<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\TestRun;
use App\Support\CrowdGroups;
use App\Support\RunEstimate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Benchmark mode page (SITEMAP.md §3.3): only the run creator, only while the run is running. */
class RunController extends Controller
{
    public function show(Request $request, TestRun $run): View
    {
        abort_unless($run->created_by === $request->user()->id, 403);
        abort_unless($run->status === 'running' && ! $run->isExpired(), 410, 'This run is no longer running.');

        $run->load(['deviceClass', 'session.assets.tool', 'session.assets.object', 'session.assets.metrics']);
        $test = $run->session;

        $asset = fn ($asset) => [
            'id' => $asset->id,
            'name' => $asset->name,
            'object' => $asset->object?->name,
            'url' => route('assets.file', [$asset, basename($asset->web_file_path)]),
        ];

        return view('pages.lab.run', [
            'groups' => CrowdGroups::for($test),
            'run' => $run,
            'test' => $test,
            'estimateSeconds' => $run->estimatedSeconds(),
            'typicalSeconds' => $test->method === 'crowd' ? RunEstimate::crowdSeconds($test->configuration_json, ...[...$test->crowdShape(), $run->deviceClass->category])['typical'] : null,
            'config' => [
                'run_id' => $run->id,
                'method' => $test->method,
                'device_category' => $run->deviceClass->category,
                'cooldown_s' => RunEstimate::coolDownSeconds($run->deviceClass->category),
                'configuration' => $test->configuration_json,
                'target' => $test->target_json,
                'scenario' => $test->scenario,
                'assets' => $test->assets->map($asset)->values(),
                // Crowd: one group per asset (Per asset) or per tool (Per tool), in selection order.
                'groups' => $test->method === 'crowd' ? CrowdGroups::for($test)->map(fn ($group) => [...$group, 'assets' => $group['assets']->map($asset)->values()])->values() : null,
                'endpoints' => [
                    'submit' => route('api.runs.submit', $run),
                    'invalidate' => route('api.runs.invalidate', $run),
                    'cancel' => route('api.runs.cancel', $run),
                    'done' => route($test->method === 'crowd' ? 'lab.crowd' : 'lab.side-by-side'),
                    'results' => route('results.show', $test),
                    'again' => route('api.tests.runs.store', $test),
                ],
            ],
        ]);
    }
}

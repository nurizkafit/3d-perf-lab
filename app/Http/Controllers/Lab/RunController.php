<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\TestRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Benchmark mode page (SITEMAP.md §3.3): only the run creator, only while the run is running. */
class RunController extends Controller
{
    public function show(Request $request, TestRun $run): View
    {
        abort_unless($run->created_by === $request->user()->id, 403);
        abort_unless($run->status === 'running' && ! $run->isExpired(), 410, 'This run is no longer running.');

        $test = $run->session()->with('assets')->firstOrFail();
        $run->load('deviceClass');

        return view('pages.lab.run', [
            'run' => $run,
            'test' => $test,
            'config' => [
                'run_id' => $run->id,
                'method' => $test->method,
                'device_category' => $run->deviceClass->category,
                'scenario' => $test->scenario,
                'configuration' => $test->configuration_json,
                'budget' => $test->budget_json,
                'assets' => $test->assets->map(fn ($asset) => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'url' => route('assets.file', [$asset, basename($asset->web_file_path)]),
                ])->values(),
                'endpoints' => [
                    'submit' => route('api.runs.submit', $run),
                    'invalidate' => route('api.runs.invalidate', $run),
                    'cancel' => route('api.runs.cancel', $run),
                    'done' => route('lab.index'),
                    'results' => route('results.show', $test),
                    'again' => route('api.tests.runs.store', $test),
                ],
            ],
        ]);
    }
}

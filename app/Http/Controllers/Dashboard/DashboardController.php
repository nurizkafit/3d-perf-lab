<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\DeviceClass;
use App\Models\Report;
use App\Models\TestSession;
use App\Support\TestSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Dashboard (PRD.md §9): device class, counts, recent tests and uploads, quick actions. Users see their own tests. */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $tests = TestSession::query()->when(! $user->isAdmin(), fn ($q) => $q->where('created_by', $user->id));
        $recent = (clone $tests)->with(['runs' => fn ($q) => $q->orderByDesc('id'), 'runs.deviceClass', 'runs.results' => fn ($q) => $q->withoutSamples(), 'runs.checks'])
            ->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get();

        return view('pages.dashboard.index', [
            'deviceClass' => ($id = $request->session()->get('device_class_id')) ? DeviceClass::find($id) : null,
            'counts' => [
                'assets' => Asset::count(),
                'tests' => (clone $tests)->count(),
                'reports' => Report::published()->count(),
            ],
            'tests' => $recent,
            'summaries' => $recent->mapWithKeys(fn ($test) => [$test->id => TestSummary::line($test, $test->runs->firstWhere('status', 'completed'))]),
            'uploads' => Asset::with('tool', 'object')->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Http\Requests\Results\DeleteTestRequest;
use App\Models\DeviceClass;
use App\Models\TestSession;
use App\Support\CrowdResults;
use App\Support\MetricDefinitions;
use App\Support\SideBySideResults;
use App\Support\TestSummary;
use App\Support\ViewerConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Test history and test detail (PRD.md §8.5, §10.1): owner or admin (TestSessionPolicy). The detail shows the most
 * recent completed run; without one, the status of the most recent run.
 */
class ResultController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TestSession::class);
        $classes = DeviceClass::orderBy('sort_order')->orderBy('name')->pluck('name', 'id');
        // Invalid filter values are ignored. Dates are in the display zone (decision D38).
        $method = array_key_exists((string) $request->query('method'), MetricDefinitions::METHODS) ? $request->query('method') : null;
        $device = $classes->has((int) $request->query('device')) ? (int) $request->query('device') : null;
        $date = fn (string $key) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query($key)) ? $request->query($key) : null;
        $zone = config('lab.display_timezone');

        $tests = TestSession::query()
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('created_by', $request->user()->id))
            ->when($method, fn ($q) => $q->where('method', $method))
            ->when($device, fn ($q) => $q->whereHas('runs', fn ($r) => $r->where('device_class_id', $device)))
            ->when($date('from'), fn ($q, $from) => $q->where('created_at', '>=', Carbon::parse($from, $zone)->startOfDay()->utc()))
            ->when($date('to'), fn ($q, $to) => $q->where('created_at', '<=', Carbon::parse($to, $zone)->endOfDay()->utc()))
            ->with(['runs' => fn ($q) => $q->orderByDesc('id'), 'runs.deviceClass', 'runs.results' => fn ($q) => $q->withoutSamples(), 'runs.checks', 'report', 'creator'])
            ->withCount('assets')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('pages.results.index', [
            'tests' => $tests,
            'summaries' => $tests->getCollection()->mapWithKeys(fn ($test) => [$test->id => TestSummary::line($test, $test->runs->firstWhere('status', 'completed'))]),
            'classes' => $classes,
            'filters' => ['method' => $method, 'device' => $device, 'from' => $date('from'), 'to' => $date('to')],
        ]);
    }

    public function show(TestSession $test): View
    {
        Gate::authorize('view', $test);
        $test->load(['assets.tool', 'assets.object', 'assets.metrics', 'assets.uploader', 'runs.deviceClass', 'report']);
        $completed = $test->runs->where('status', 'completed')->sortByDesc('id')->first();

        return view('pages.results.show', [
            'test' => $test,
            'latest' => $test->runs->sortByDesc('id')->first(),
            'results' => $completed ? ($test->method === 'crowd' ? CrowdResults::for($test, $completed) : SideBySideResults::for($test, $completed)) : null,
            'viewer' => ViewerConfig::forTest($test),
        ]);
    }

    public function destroy(DeleteTestRequest $request, TestSession $test): RedirectResponse
    {
        // A draft report goes with its test; runs, results, and checks cascade.
        DB::transaction(function () use ($test) {
            $test->report?->delete();
            $test->delete();
        });

        return redirect()->route('results.index')->with('status', "Deleted the test \"{$test->name}\".");
    }
}

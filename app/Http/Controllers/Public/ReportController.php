<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DeviceClass;
use App\Support\SampleReports;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public report gallery and detail. M1.2 serves sample data only (SampleReports).
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $deviceClasses = DeviceClass::where('is_active', true)->orderBy('sort_order')->pluck('name')->all();

        // Invalid filter values are ignored (SITEMAP.md §2).
        $search = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $method = array_key_exists($request->query('method'), SampleReports::METHODS) ? $request->query('method') : null;
        $device = in_array($request->query('device'), $deviceClasses, true) ? $request->query('device') : null;

        $reports = collect(SampleReports::all())
            ->when($search !== '', fn ($reports) => $reports->filter(
                fn ($report) => Str::contains($report['title'].' '.$report['question'], $search, ignoreCase: true)
            ))
            ->when($method, fn ($reports) => $reports->where('method', $method))
            ->when($device, fn ($reports) => $reports->where('device_class', $device))
            ->values();

        return view('pages.public.reports', [
            'reports' => $reports,
            'filters' => ['q' => $search, 'method' => $method, 'device' => $device],
            'methods' => SampleReports::METHODS,
            'deviceClasses' => $deviceClasses,
            'previewState' => $this->previewState($request),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $report = SampleReports::find($slug) ?? abort(404);

        return view('pages.public.report-show', [
            'report' => $report,
            'method' => SampleReports::METHODS[$report['method']],
            'budgetItems' => SampleReports::BUDGET_ITEMS,
            'standardLimitations' => SampleReports::STANDARD_LIMITATIONS,
            'previewState' => $this->previewState($request),
        ]);
    }

    /** Local-only preview of the loading and error states (`?_state=loading|error`). */
    private function previewState(Request $request): ?string
    {
        $state = $request->query('_state');

        return app()->environment('local') && in_array($state, ['loading', 'error'], true) ? $state : null;
    }
}

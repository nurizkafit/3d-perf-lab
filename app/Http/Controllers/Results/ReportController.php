<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\PublishReportRequest;
use App\Http\Requests\Reports\UpdateReportRequest;
use App\Models\TestSession;
use App\Services\ReportService;
use App\Support\ReportLimitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Report editor (PRD.md §10.2–§10.4, SITEMAP.md §3.4): question, limitations, conclusion, public asset approval, publish. */
class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function edit(Request $request, TestSession $test): View
    {
        Gate::authorize('update', $test);
        $test->load(['assets.tool', 'assets.object', 'assets.metrics', 'assets.uploader', 'runs.deviceClass']);
        $report = $this->reports->forTest($test, $request->user());

        return view('pages.results.report', [
            'test' => $test,
            'report' => $report,
            'approved' => $report->exists ? $report->publicAssets()->pluck('assets.id')->all() : [],
            'blockers' => $this->reports->blockers($report),
            'run' => $test->latestCompletedRun(),
            'standardLimitations' => ReportLimitations::STANDARD,
        ]);
    }

    public function update(UpdateReportRequest $request, TestSession $test): RedirectResponse
    {
        $this->reports->save($test, $request->user(), $request->validated());

        return redirect()->route('results.report', $test)->with('status', 'Draft saved.');
    }

    public function publish(PublishReportRequest $request, TestSession $test): RedirectResponse
    {
        $report = $this->reports->publish($test->report, $request->user());

        return redirect()->route('results.report', $test)->with('status', 'Published. The public page is '.route('reports.show', $report->public_slug));
    }

    public function unpublish(PublishReportRequest $request, TestSession $test): RedirectResponse
    {
        $this->reports->unpublish($test->report);

        return redirect()->route('results.report', $test)->with('status', 'Unpublished. The public page and every public file link stopped working.');
    }
}

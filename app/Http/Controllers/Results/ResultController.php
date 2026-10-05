<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\TestSession;
use App\Support\MetricDefinitions;
use App\Support\TestResultPresenter;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Test detail (PRD.md §8.4, §10): owner or admin (TestSessionPolicy). */
class ResultController extends Controller
{
    public function show(TestSession $test): View
    {
        Gate::authorize('view', $test);

        return view('pages.results.show', [
            'test' => $test,
            'result' => TestResultPresenter::for($test),
            'budgetItems' => MetricDefinitions::BUDGET_ITEMS,
            'metrics' => MetricDefinitions::METRICS,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\TestSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Test detail (PRD.md §8.5, §10): owner or admin (TestSessionPolicy).
 * The Side-by-Side and Crowd results views are built in M4.3 and M4.4; until then the page shows the test record.
 */
class ResultController extends Controller
{
    public function show(TestSession $test): View
    {
        Gate::authorize('view', $test);

        return view('pages.results.show', [
            'test' => $test->load(['assets.tool', 'assets.object', 'runs.deviceClass']),
        ]);
    }
}

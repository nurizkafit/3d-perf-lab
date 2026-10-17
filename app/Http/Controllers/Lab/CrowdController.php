<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Support\LabPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Crowd Test page (BENCHMARK.md §4.6, DESIGN.md §9.2): scenario, selection, look phase, configuration steps. */
class CrowdController extends Controller
{
    public function show(Request $request): View
    {
        $data = LabPage::data($request);
        $data['config'] += ['maxAssets' => 8, 'maxObjects' => 30, 'defaultCap' => 1024, 'maxCap' => 4096, 'lookCopies' => 16];

        return view('pages.lab.crowd', $data);
    }
}

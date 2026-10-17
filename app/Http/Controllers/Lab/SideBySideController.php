<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Support\LabPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Side-by-Side Test page (BENCHMARK.md §4.3, DESIGN.md §9.2): look phase and configuration steps. */
class SideBySideController extends Controller
{
    public function show(Request $request): View
    {
        $data = LabPage::data($request);
        $data['config'] += ['minAssets' => 2, 'maxAssets' => 4];

        return view('pages.lab.side-by-side', $data);
    }
}

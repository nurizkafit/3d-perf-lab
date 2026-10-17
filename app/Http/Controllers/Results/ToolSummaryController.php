<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\DeviceClass;
use App\Models\TestSession;
use App\Models\Tool;
use App\Services\ToolSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Tool summary (SITEMAP.md §3.4, BENCHMARK.md §4.7): device class, tools, and common objects in query parameters,
 * so the page works without JavaScript and can be bookmarked. Users see their own results; admins see all.
 */
class ToolSummaryController extends Controller
{
    public function index(Request $request, ToolSummaryService $summary): View
    {
        Gate::authorize('viewAny', TestSession::class);
        $ids = fn (string $key) => collect((array) $request->query($key, []))->filter(fn ($id) => ctype_digit((string) $id))->map(fn ($id) => (int) $id)->unique()->values()->all();
        $classes = DeviceClass::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);
        $deviceClassId = (int) ($request->query('device_class') ?: $request->session()->get('device_class_id'));
        $deviceClassId = $classes->contains('id', $deviceClassId) ? $deviceClassId : null;
        $toolIds = Tool::whereIn('id', $ids('tools'))->pluck('id')->sortBy(fn ($id) => array_search($id, $ids('tools')))->values()->all();

        return view('pages.results.tools', [
            'classes' => $classes,
            'tools' => Tool::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'),
            'deviceClassId' => $deviceClassId,
            'toolIds' => $toolIds,
            'excluded' => $ids('exclude'),
            'summary' => $deviceClassId && count($toolIds) >= 2
                ? $summary->build($deviceClassId, $toolIds, $ids('exclude'), $request->user()->isAdmin() ? null : $request->user()->id)
                : null,
        ]);
    }
}

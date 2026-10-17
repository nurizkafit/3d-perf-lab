<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DeviceClass;
use App\Models\Report;
use App\Support\AssetFiles;
use App\Support\MetricDefinitions;
use App\Support\PublicReportPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public report gallery, detail, and approved model files (SITEMAP.md §2, §7). Published reports only; every
 * value passes through PublicReportPresenter. Drafts, wrong slugs, revoked or wrong tokens all return the same 404.
 */
class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $deviceClasses = DeviceClass::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id');

        // Invalid filter values are ignored (SITEMAP.md §2).
        $search = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $method = array_key_exists((string) $request->query('method'), MetricDefinitions::METHODS) ? $request->query('method') : null;
        $device = $deviceClasses->contains($request->query('device')) ? $request->query('device') : null;
        $like = '%'.addcslashes($search, '%_\\').'%';

        $reports = Report::published()
            ->with('test.runs.deviceClass')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('question', 'like', $like)->orWhere('conclusion', 'like', $like)))
            ->when($method, fn ($q) => $q->whereHas('test', fn ($t) => $t->where('method', $method)))
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (Report $report) => PublicReportPresenter::card($report))
            ->when($device, fn ($cards) => $cards->where('device_class', $device))
            ->values();

        return view('pages.public.reports', [
            'reports' => $reports,
            'filters' => ['q' => $search, 'method' => $method, 'device' => $device],
            'methods' => MetricDefinitions::METHODS,
            'deviceClasses' => $deviceClasses->values()->all(),
        ]);
    }

    public function show(string $slug): View
    {
        $report = Report::published()->where('public_slug', $slug)->first() ?? abort(404);

        return view('pages.public.report-show', [
            'report' => PublicReportPresenter::present($report),
            'methods' => MetricDefinitions::METHODS,
        ]);
    }

    /**
     * One approved model file. `name` serves the dependent files of a GLTF from the same folder. No difference
     * between an unpublished report, a revoked token, and a wrong token (SITEMAP.md §2).
     */
    public function asset(string $slug, string $token, ?string $name = null): StreamedResponse
    {
        $report = Report::published()->where('public_slug', $slug)->first() ?? abort(404);
        $asset = $report->publicAssets()->wherePivot('token', $token)->first() ?? abort(404);
        $path = $name === null ? $asset->web_file_path : (AssetFiles::file($asset, $name) ?? abort(404));
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), array_filter([
            'Content-Type' => match (pathinfo($path, PATHINFO_EXTENSION)) {
                'gltf' => 'model/gltf+json',
                'glb' => 'model/gltf-binary',
                'bin' => 'application/octet-stream',
                default => null,
            },
            'Cache-Control' => 'public, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex',
        ]));
    }
}

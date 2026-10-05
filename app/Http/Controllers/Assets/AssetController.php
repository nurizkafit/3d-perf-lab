<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assets\BulkAssetsRequest;
use App\Http\Requests\Assets\DeleteAssetRequest;
use App\Http\Requests\Assets\ForceDeleteAssetRequest;
use App\Http\Requests\Assets\RestoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Tool;
use App\Models\User;
use App\Services\AssetDeletionService;
use App\Support\AssetFiles;
use App\Support\AssetFilters;
use App\Support\ViewerConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Asset library, detail, edit, delete (PRD.md §7.2–7.4, SITEMAP.md §3.2). */
class AssetController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Asset::class);
        $filters = new AssetFilters($request);

        return view('pages.assets.index', [
            // withCount after apply(): apply() sets select('assets.*'), which would drop the count column.
            'assets' => $filters->apply(Asset::query()->with(['tool', 'metrics', 'uploader']))->withCount('testSessions')->paginate(24)->withQueryString(),
            'filters' => $filters,
            'tools' => Tool::orderBy('sort_order')->pluck('name', 'id')->all(),
            'uploaders' => User::whereIn('id', Asset::withTrashed()->select('uploaded_by'))->orderBy('name')->pluck('name', 'id')->all(),
            'canForceDelete' => $request->user()->can('forceDeleteAny', Asset::class),
            // Users can bulk-delete their own assets; in the deleted list only admins have an action.
            'canBulk' => ! $filters->values['deleted'] || $request->user()->can('forceDeleteAny', Asset::class),
        ]);
    }

    public function show(Request $request, Asset $asset): View
    {
        Gate::authorize('view', $asset);
        $asset->load(['tool', 'metrics', 'uploader']);
        $user = $request->user();

        // Tests the viewer may open: their own, or all for an admin (PRD.md §4).
        $tests = $asset->testSessions()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('test_sessions.created_by', $user->id))
            ->latest('test_sessions.created_at')
            ->get();

        return view('pages.assets.show', [
            'asset' => $asset,
            'tests' => $tests,
            'otherTests' => $asset->testUsageCount() - $tests->count(),
            'viewerConfig' => ViewerConfig::preview(),
        ]);
    }

    public function edit(Asset $asset): View
    {
        Gate::authorize('update', $asset);

        return view('pages.assets.edit', [
            'asset' => $asset,
            'tools' => Tool::where('is_active', true)->orWhere('id', $asset->tool_id)->orderBy('sort_order')->pluck('name', 'id')->all(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->update($request->assetAttributes());

        return redirect()->route('assets.show', $asset)->with('status', 'Saved the metadata.');
    }

    /** Soft delete: the asset leaves the library and new tests; old results keep it (PRD.md §7.4). */
    public function destroy(DeleteAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->delete();

        return redirect()->route('assets.show', $asset)->with('status', "Deleted {$asset->name}. It no longer appears in the library or in new tests, and it can be restored.");
    }

    public function restore(RestoreAssetRequest $request, Asset $asset): RedirectResponse
    {
        $asset->restore();

        return redirect()->route('assets.show', $asset)->with('status', "Restored {$asset->name}.");
    }

    /** Admin only; blocked while any test refers to the asset (decision D33). Removes the files. */
    public function forceDestroy(ForceDeleteAssetRequest $request, Asset $asset, AssetDeletionService $deletion): RedirectResponse
    {
        $uses = $asset->testUsageCount();
        if ($uses > 0) {
            return back()->withErrors(['item' => "{$asset->name} is used by {$uses} ".($uses === 1 ? 'test' : 'tests').', so it cannot be deleted permanently. Test results keep their assets; it can stay deleted (hidden) instead.']);
        }

        $deletion->forceDelete($request->user(), [$asset]);

        return redirect()->route('assets.index', ['deleted' => 1])->with('status', "Permanently deleted {$asset->name} and its files.");
    }

    /** Bulk soft delete from the library table (decision D34). */
    public function bulkDestroy(BulkAssetsRequest $request, AssetDeletionService $deletion): RedirectResponse
    {
        $result = $deletion->softDelete($request->user(), $request->assets());
        $message = "Deleted {$result['deleted']} ".Str::plural('asset', $result['deleted']).'. They can be restored.';
        if ($result['not_allowed']) {
            $message .= " Skipped {$result['not_allowed']} that you do not own.";
        }

        return back()->with('status', $message);
    }

    /** Bulk permanent delete, admins only; assets used by a test are skipped (decisions D33, D34). */
    public function bulkForceDestroy(BulkAssetsRequest $request, AssetDeletionService $deletion): RedirectResponse
    {
        Gate::authorize('forceDeleteAny', Asset::class);
        $result = $deletion->forceDelete($request->user(), $request->assets(withTrashed: true));
        $message = "Permanently deleted {$result['deleted']} ".Str::plural('asset', $result['deleted']).' and their files.';
        if ($result['used']) {
            $message .= " Skipped {$result['used']} used in tests.";
        }

        return back()->with('status', $message);
    }

    public function download(Asset $asset): BinaryFileResponse
    {
        Gate::authorize('download', $asset);

        return AssetFiles::download($asset);
    }

    /** Model file or GLTF dependent for the viewer. Same rule as download. */
    public function file(Asset $asset, string $name): StreamedResponse
    {
        Gate::authorize('download', $asset);
        $path = AssetFiles::file($asset, $name) ?? abort(404);

        $type = match (pathinfo($name, PATHINFO_EXTENSION)) {
            'gltf' => 'model/gltf+json',
            'glb' => 'model/gltf-binary',
            'bin' => 'application/octet-stream',
            default => null, // images: detected from the content
        };

        return Storage::disk('local')->response($path, null, array_filter([
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }

    public function thumbnail(Asset $asset): StreamedResponse
    {
        Gate::authorize('view', $asset);
        abort_unless($asset->thumbnail_path && Storage::disk('local')->exists($asset->thumbnail_path), 404);

        return Storage::disk('local')->response($asset->thumbnail_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;

/**
 * Asset Selector search (PRD.md §8.2, §13 `GET /api/assets`). Registered, not deleted assets only:
 * a deleted asset cannot be selected for new tests (PRD.md §7.4).
 */
class AssetSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Asset::class);
        $q = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $like = '%'.addcslashes($q, '%_\\').'%';
        $id = fn ($value) => ctype_digit((string) $value) ? (int) $value : null;
        $ids = collect(explode(',', (string) $request->query('ids', '')))->map($id)->filter()->take(50)->all();

        $assets = Asset::query()
            ->with(['tool', 'object', 'metrics'])
            ->when($ids, fn ($query) => $query->whereIn('id', $ids))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', $like))
            ->when($id($request->query('tool')), fn ($query, $tool) => $query->where('tool_id', $tool))
            ->when($id($request->query('object')), fn ($query, $object) => $query->where('object_id', $object))
            ->when(trim((string) $request->query('author', '')) !== '', fn ($query) => $query->where('author', 'like', '%'.addcslashes(trim((string) $request->query('author')), '%_\\').'%'))
            ->when(in_array($request->query('format'), ['glb', 'gltf'], true), fn ($query) => $query->where('format', $request->query('format')))
            ->orderBy('name')
            ->limit(60)
            ->get();

        return response()->json([
            'assets' => $assets->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'tool_id' => $asset->tool_id,
                'tool' => $asset->tool->name,
                'object_id' => $asset->object_id,
                'object' => $asset->object?->name,
                'author' => $asset->author,
                'format' => strtoupper($asset->format),
                'size' => Number::fileSize($asset->file_size, maxPrecision: 1),
                'triangles' => $asset->metrics?->triangles,
                'thumbnail_url' => $asset->thumbnail_path ? route('assets.thumbnail', $asset) : null,
                'file_url' => route('assets.file', [$asset, basename($asset->web_file_path)]),
            ])->values(),
            'limited' => $assets->count() === 60,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetObject;
use App\Models\Tool;
use App\Models\UploadItem;
use App\Models\UploadSession;
use App\Services\UploadService;
use App\Support\UploadItemPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Bulk upload pages (SITEMAP.md §3.2). The session is created on the first drop (Api\UploadController). */
class UploadController extends Controller
{
    public function create(Request $request): View
    {
        Gate::authorize('create', Asset::class);

        return view('pages.assets.upload', $this->pageData($request, null));
    }

    /** Session record and resume: every file with its result; staged rows can still be completed. */
    public function show(Request $request, UploadSession $session, UploadService $uploads): View
    {
        Gate::authorize('view', $session);

        return view('pages.assets.upload', array_merge($this->pageData($request, $session), [
            'items' => $session->items()->with('asset')->get()
                ->map(fn ($item) => UploadItemPresenter::present($item, $request->user(), $uploads) + ['asset_name' => $item->asset?->name])
                ->values(),
        ]));
    }

    /** A staged thumbnail, visible only to the session owner. */
    public function thumbnail(UploadSession $session, UploadItem $item): StreamedResponse
    {
        Gate::authorize('view', $session);
        abort_unless($item->upload_session_id === $session->id && $item->thumbnail_path, 404);

        return Storage::disk('local')->response($item->thumbnail_path);
    }

    private function pageData(Request $request, ?UploadSession $session): array
    {
        $user = $request->user();

        return [
            'session' => $session,
            'items' => collect(),
            'tools' => Tool::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all(),
            'objects' => AssetObject::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all(),
            'canSetAuthor' => $user->can('setAuthor', Asset::class),
            'maxBytes' => UploadService::maxBytes(),
        ];
    }
}

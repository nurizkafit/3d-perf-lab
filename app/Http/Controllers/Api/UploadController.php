<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Uploads\RegisterUploadRequest;
use App\Http\Requests\Uploads\RemoveUploadItemRequest;
use App\Http\Requests\Uploads\StoreUploadSessionRequest;
use App\Http\Requests\Uploads\UpdateUploadItemRequest;
use App\Http\Requests\Uploads\UploadFileRequest;
use App\Models\UploadItem;
use App\Models\UploadSession;
use App\Services\AssetRegistrationService;
use App\Services\UploadService;
use App\Support\UploadItemPresenter;
use Illuminate\Http\JsonResponse;

/** JSON endpoints of the bulk upload flow (PRD.md §13). Session auth + CSRF. */
class UploadController extends Controller
{
    public function __construct(private UploadService $uploads, private AssetRegistrationService $registration) {}

    public function storeSession(StoreUploadSessionRequest $request): JsonResponse
    {
        $session = UploadSession::create(['created_by' => $request->user()->id, 'status' => 'open']);

        return response()->json([
            'id' => $session->id,
            'url' => route('assets.upload.show', $session),
        ], 201);
    }

    public function storeFile(UploadFileRequest $request, UploadSession $session): JsonResponse
    {
        $item = $this->uploads->stage(
            $session,
            $request->file('file'),
            $request->file('dependents', []),
            $request->metrics(),
            $request->file('thumbnail'),
        );

        return response()->json(UploadItemPresenter::present($item, $request->user(), $this->uploads), 201);
    }

    public function updateItem(UpdateUploadItemRequest $request, UploadItem $item): JsonResponse
    {
        $item->update(['metadata_json' => $this->registration->normalise($request->validated(), $request->user())]);

        return response()->json(['saved' => true]);
    }

    public function destroyItem(RemoveUploadItemRequest $request, UploadItem $item): JsonResponse
    {
        $this->uploads->remove($item);

        return response()->json(UploadItemPresenter::present($item->fresh(), $request->user(), $this->uploads));
    }

    public function register(RegisterUploadRequest $request, UploadSession $session): JsonResponse
    {
        $result = $this->registration->register($session, $request->user(), $request->validated('rows'));

        return response()->json($result + [
            'items' => $session->items()->get()->map(fn ($item) => UploadItemPresenter::present($item, $request->user(), $this->uploads))->values(),
            'session_status' => $session->fresh()->status,
        ]);
    }
}

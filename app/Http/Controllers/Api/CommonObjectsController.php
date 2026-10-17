<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Support\CommonObjects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** `GET /api/objects/common?tools=1,2` (PRD.md §8.3, §13): objects present in every given tool, and the missing ones. */
class CommonObjectsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Asset::class);
        $ids = collect(explode(',', (string) $request->query('tools', '')))->filter(fn ($id) => ctype_digit($id))->map(fn ($id) => (int) $id)->unique()->take(10)->values()->all();

        return response()->json(CommonObjects::for($ids));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\RunActionRequest;
use App\Http\Requests\Lab\StartRunRequest;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Services\RunResultService;
use Illuminate\Http\JsonResponse;

/**
 * Lab run lifecycle endpoints (PRD.md §13): start run, invalidate, cancel.
 * Creating tests and submitting results return with the Side-by-Side and Crowd tests (PROJECT.md M4.3, M4.4).
 */
class TestRunController extends Controller
{
    public function __construct(private RunResultService $runs) {}

    public function startRun(StartRunRequest $request, TestSession $test): JsonResponse
    {
        $run = $this->runs->start($test, $request->user(), (int) $request->session()->get('device_class_id'), $request->validated('environment'));

        return response()->json(['id' => $run->id], 201);
    }

    public function invalidate(RunActionRequest $request, TestRun $run): JsonResponse
    {
        $this->runs->invalidate($run, $request->validated('reason'));

        return response()->json(['status' => 'invalid']);
    }

    public function cancel(RunActionRequest $request, TestRun $run): JsonResponse
    {
        $this->runs->cancel($run);

        return response()->json(['status' => 'cancelled']);
    }
}

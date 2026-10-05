<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\CreateTestRequest;
use App\Http\Requests\Lab\RunActionRequest;
use App\Http\Requests\Lab\StartRunRequest;
use App\Http\Requests\Lab\SubmitResultsRequest;
use App\Models\TestRun;
use App\Models\TestSession;
use App\Services\RunResultService;
use App\Services\TestSessionService;
use Illuminate\Http\JsonResponse;

/** Lab JSON endpoints (PRD.md §13): create test, start run, submit results, invalidate, cancel. */
class TestRunController extends Controller
{
    public function __construct(private RunResultService $runs) {}

    public function storeTest(CreateTestRequest $request, TestSessionService $tests): JsonResponse
    {
        $test = $tests->create($request->user(), $request->validated());

        return response()->json(['id' => $test->id], 201);
    }

    public function startRun(StartRunRequest $request, TestSession $test): JsonResponse
    {
        $run = $this->runs->start($test, $request->user(), (int) $request->session()->get('device_class_id'), $request->validated('environment'));

        return response()->json(['id' => $run->id, 'url' => route('lab.run', $run)], 201);
    }

    public function submit(SubmitResultsRequest $request, TestRun $run): JsonResponse
    {
        $run = $this->runs->submit($run, $request->payload());

        return response()->json([
            'status' => $run->status,
            'evaluations' => $run->evaluations()->get(['asset_id', 'item_key', 'limit_value', 'measured_value', 'outcome']),
        ]);
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

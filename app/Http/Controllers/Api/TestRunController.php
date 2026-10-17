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

/**
 * Lab endpoints (PRD.md §13): create test, start run, submit results, invalidate, cancel.
 * The Crowd Test adds its configuration in M4.4.
 */
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

        return response()->json(['id' => $run->id, 'url' => route('lab.runs.show', $run)], 201);
    }

    public function submit(SubmitResultsRequest $request, TestRun $run): JsonResponse
    {
        $this->runs->submit($run, $request->payload());

        return response()->json(['status' => 'completed', 'url' => route('results.show', $run->test_session_id)]);
    }

    public function invalidate(RunActionRequest $request, TestRun $run): JsonResponse
    {
        if (! $this->runs->invalidate($run, $request->validated('reason'))) {
            return $this->notRunning($run);
        }

        return response()->json(['status' => 'invalid']);
    }

    public function cancel(RunActionRequest $request, TestRun $run): JsonResponse
    {
        if (! $this->runs->cancel($run)) {
            return $this->notRunning($run);
        }

        return response()->json(['status' => 'cancelled']);
    }

    /** The run ended between the request check and the update (for example, a submit completed it first). */
    private function notRunning(TestRun $run): JsonResponse
    {
        $status = $run->fresh()->status;

        return response()->json(['message' => "This run is {$status}.", 'status' => $status], 409);
    }
}

<?php

namespace App\Services;

use App\Models\TestRun;
use App\Models\TestSession;
use App\Models\User;

/**
 * Run lifecycle (BENCHMARK.md §4.1, §5): start → running → completed | invalid | cancelled.
 * Result submission for the Side-by-Side and Crowd tests is added with those methods (PROJECT.md M4.3, M4.4).
 */
class RunResultService
{
    public function start(TestSession $test, User $user, int $deviceClassId, array $environment): TestRun
    {
        return $test->runs()->create([
            'device_class_id' => $deviceClassId,
            'environment_json' => $environment,
            'status' => 'running',
            'started_at' => now(),
            'created_by' => $user->id,
        ]);
    }

    /** Invalid runs never produce results or Target checks (BENCHMARK.md §4.1 step 5). */
    public function invalidate(TestRun $run, string $reason): void
    {
        $run->update(['status' => 'invalid', 'invalid_reason' => mb_substr($reason, 0, 250), 'completed_at' => now()]);
    }

    public function cancel(TestRun $run): void
    {
        $run->update(['status' => 'cancelled', 'completed_at' => now()]);
    }

    /** Marks running runs past their expiry as invalid ("expired"). Returns the number of runs. */
    public function expireStale(): int
    {
        $cutoff = now()->subMinutes(config('lab.run_expiry_minutes'));

        return TestRun::where('status', 'running')->where('started_at', '<', $cutoff)
            ->update(['status' => 'invalid', 'invalid_reason' => 'expired', 'completed_at' => now()]);
    }
}

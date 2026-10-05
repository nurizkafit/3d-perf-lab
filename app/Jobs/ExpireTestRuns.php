<?php

namespace App\Jobs;

use App\Services\RunResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs still `running` config('lab.run_expiry_minutes') after start become invalid with reason
 * "expired" (BENCHMARK.md §5). They never produce budget results.
 */
class ExpireTestRuns implements ShouldQueue
{
    use Queueable;

    public function handle(RunResultService $runs): void
    {
        $runs->expireStale();
    }
}

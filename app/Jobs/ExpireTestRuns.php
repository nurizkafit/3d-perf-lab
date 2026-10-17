<?php

namespace App\Jobs;

use App\Services\RunResultService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Runs still `running` after their expiry (30 minutes, or the estimate + 15 minutes; D59) become invalid with reason
 * "expired" (BENCHMARK.md §5). They never produce results or Target checks.
 */
class ExpireTestRuns implements ShouldQueue
{
    use Queueable;

    public function handle(RunResultService $runs): void
    {
        $runs->expireStale();
    }
}

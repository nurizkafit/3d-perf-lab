<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Marks runs that are still running config('lab.run_expiry_minutes') after start as expired (BENCHMARK.md §5).
 * Shell only; the run lifecycle is implemented in M4.1.
 */
class ExpireTestRuns implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}

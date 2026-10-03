<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Deletes abandoned staged uploads older than config('lab.staging_retention_days') (PRD.md §7.1).
 * Shell only; the cleanup is implemented in M3.2.
 */
class CleanupStagedUploads implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        //
    }
}

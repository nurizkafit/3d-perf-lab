<?php

namespace App\Jobs;

use App\Models\UploadSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Deletes abandoned staged files after config('lab.staging_retention_days') (PRD.md §7.1).
 * A session is abandoned when nothing changed in it for the retention period. Its items stay
 * as a record with status `removed`; registered assets are never touched.
 */
class CleanupStagedUploads implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $cutoff = now()->subDays(config('lab.staging_retention_days'));
        $disk = Storage::disk('local');

        UploadSession::where('status', 'open')
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('items', fn ($query) => $query->where('updated_at', '>=', $cutoff))
            ->each(function (UploadSession $session) use ($disk) {
                $disk->deleteDirectory("staging/{$session->id}");
                $session->items()->whereIn('status', ['uploading', 'staged', 'invalid'])
                    ->update(['status' => 'removed', 'staged_path' => null, 'thumbnail_path' => null]);
                $session->update(['status' => 'expired']);
            });
    }
}

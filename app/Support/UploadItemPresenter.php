<?php

namespace App\Support;

use App\Models\Asset;
use App\Models\UploadItem;
use App\Models\User;
use App\Services\UploadService;
use Illuminate\Support\Number;

/** The shape of one upload row for the browser (no storage paths, no internal ids beyond the item id). */
class UploadItemPresenter
{
    public static function present(UploadItem $item, User $user, UploadService $uploads): array
    {
        $metadata = $item->metadata_json ?? [];
        $duplicate = $item->status === 'staged' ? $uploads->duplicateOf($item, $user->id) : null;

        return [
            'id' => $item->id,
            'file' => $item->original_filename,
            'status' => $item->status,
            'format' => $item->format ? strtoupper($item->format) : '—',
            'size' => $item->file_size ? Number::fileSize($item->file_size, maxPrecision: 1) : '—',
            'name' => $metadata['name'] ?? UploadService::defaultName($item->original_filename),
            'author' => $user->can('setAuthor', Asset::class) ? ($metadata['author'] ?? $user->name) : $user->name,
            'tool_id' => isset($metadata['tool_id']) ? (string) $metadata['tool_id'] : '',
            'object_id' => isset($metadata['object_id']) ? (string) $metadata['object_id'] : '',
            'production_time_minutes' => isset($metadata['production_time_minutes']) ? (string) $metadata['production_time_minutes'] : '',
            'description' => $metadata['description'] ?? '',
            'error' => $item->error_json['file'] ?? null,
            'field_errors' => $item->status === 'staged' ? collect($item->error_json ?? [])->except('file')->map(fn ($messages) => (array) $messages)->all() : [],
            'warning' => $duplicate ? "You already registered this file as \"{$duplicate->name}\"." : null,
            'thumbnail_url' => match (true) {
                (bool) $item->thumbnail_path => route('assets.upload.thumbnail', [$item->upload_session_id, $item->id]),
                $item->status === 'registered' && $item->asset_id !== null => route('assets.thumbnail', $item->asset_id),
                default => null,
            },
            'asset_id' => $item->asset_id,
        ];
    }
}

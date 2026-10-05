<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'upload_session_id', 'asset_id', 'original_filename', 'staged_path', 'thumbnail_path', 'file_size',
    'sha256', 'format', 'metrics_json', 'metadata_json', 'status', 'error_json',
])]
class UploadItem extends Model
{
    protected function casts(): array
    {
        return [
            'metrics_json' => 'array',
            'metadata_json' => 'array',
            'error_json' => 'array',
            'file_size' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(UploadSession::class, 'upload_session_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}

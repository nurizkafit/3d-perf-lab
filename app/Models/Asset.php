<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name', 'tool_id', 'author', 'uploaded_by', 'description', 'production_time_hours',
    'format', 'web_file_path', 'thumbnail_path', 'file_size', 'sha256',
])]
class Asset extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'production_time_hours' => 'decimal:2',
            'file_size' => 'integer',
        ];
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

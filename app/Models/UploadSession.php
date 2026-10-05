<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['created_by', 'status', 'completed_at'])]
class UploadSession extends Model
{
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(UploadItem::class)->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

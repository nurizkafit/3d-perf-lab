<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A report answers one question about one test (PRD.md §10). Private until published. Approved assets are the rows
 * of report_public_assets; their tokens are valid only while the report is published and change on every publish
 * and unpublish, so an old token URL stops working at once.
 */
#[Fillable(['test_session_id', 'title', 'question', 'conclusion', 'limitations', 'status', 'public_slug', 'published_at', 'published_by', 'created_by'])]
class Report extends Model
{
    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(TestSession::class, 'test_session_id');
    }

    /** Assets approved for public delivery, with their current token. */
    public function publicAssets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'report_public_assets')->withPivot('token', 'created_at')->withTrashed();
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}

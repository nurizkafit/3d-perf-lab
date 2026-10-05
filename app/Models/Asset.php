<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function metrics(): HasOne
    {
        return $this->hasOne(AssetMetric::class);
    }

    /** Test sessions that used this asset (selection order in `position`). */
    public function testSessions(): BelongsToMany
    {
        return $this->belongsToMany(TestSession::class, 'test_session_assets')->withPivot('position');
    }

    /**
     * Number of tests that refer to the asset. Such an asset can only be soft-deleted: results keep
     * their reference (PRD.md §7.4, decision D33). This includes every published report that shows it.
     */
    public function testUsageCount(): int
    {
        return $this->testSessions()->count();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}

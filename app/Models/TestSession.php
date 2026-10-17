<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'method', 'scenario', 'configuration_json', 'target_json', 'created_by'])]
class TestSession extends Model
{
    protected function casts(): array
    {
        return [
            'configuration_json' => 'array',
            'target_json' => 'array',
        ];
    }

    /** Assets in selection order. */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'test_session_assets')->withPivot('position', 'group_tool_id')->orderByPivot('position')->withTrashed();
    }

    /**
     * Crowd groups: Per asset = one group per asset, one object each; Per tool = one group per tool with the chosen
     * objects. Returns [group count, objects per group].
     *
     * @return array{0: int, 1: int}
     */
    public function crowdShape(): array
    {
        if ($this->scenario === 'per_tool') {
            return [count($this->configuration_json['tool_ids'] ?? []), count($this->configuration_json['object_ids'] ?? [])];
        }

        return [$this->assets()->count(), 1];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }

    /** The most recent completed run: the one the results, the report, and the exports use. */
    public function latestCompletedRun(): ?TestRun
    {
        return $this->runs()->where('status', 'completed')->orderByDesc('completed_at')->orderByDesc('id')->first();
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

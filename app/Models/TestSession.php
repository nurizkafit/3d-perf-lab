<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'method', 'scenario', 'tool_constraint_id', 'configuration_json', 'budget_json', 'created_by'])]
class TestSession extends Model
{
    protected function casts(): array
    {
        return [
            'configuration_json' => 'array',
            'budget_json' => 'array',
        ];
    }

    /** Assets in selection order. */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'test_session_assets')->withPivot('position')->orderByPivot('position')->withTrashed();
    }

    public function runs(): HasMany
    {
        return $this->hasMany(TestRun::class);
    }
}

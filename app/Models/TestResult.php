<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['test_run_id', 'asset_id', 'group_tool_id', 'motion', 'probe_copies', 'repetition', 'metrics_json', 'samples_json'])]
class TestResult extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['metrics_json' => 'array', 'samples_json' => 'array'];
    }

    /** Every column except the raw samples: lists and summaries need the summary values only. */
    public function scopeWithoutSamples(Builder $query): void
    {
        $query->select(['id', 'test_run_id', 'asset_id', 'group_tool_id', 'motion', 'probe_copies', 'repetition', 'metrics_json', 'created_at']);
    }
}

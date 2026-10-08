<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** One Target check or smoothness label of a run (BENCHMARK.md §4.4, §6.1). */
#[Fillable(['test_run_id', 'asset_id', 'group_tool_id', 'motion', 'check_key', 'limit_value', 'measured_value', 'label', 'outcome'])]
class TargetCheck extends Model
{
    protected function casts(): array
    {
        return ['limit_value' => 'float', 'measured_value' => 'float'];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['test_run_id', 'asset_id', 'step_instances', 'item_key', 'limit_value', 'measured_value', 'outcome'])]
class BudgetEvaluation extends Model
{
    protected function casts(): array
    {
        return ['limit_value' => 'float', 'measured_value' => 'float'];
    }
}

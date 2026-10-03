<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

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
}

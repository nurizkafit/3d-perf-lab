<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'name', 'values_json', 'is_default', 'is_active', 'sort_order'])]
class OptionPreset extends Model
{
    protected function casts(): array
    {
        return [
            'values_json' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

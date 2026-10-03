<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'items_json', 'is_default', 'is_active', 'sort_order'])]
class BudgetPreset extends Model
{
    protected function casts(): array
    {
        return [
            'items_json' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function deviceClasses(): BelongsToMany
    {
        return $this->belongsToMany(DeviceClass::class);
    }
}

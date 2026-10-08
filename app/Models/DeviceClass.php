<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'category', 'platform', 'is_active', 'sort_order'])]
class DeviceClass extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function targets(): BelongsToMany
    {
        return $this->belongsToMany(Target::class);
    }
}

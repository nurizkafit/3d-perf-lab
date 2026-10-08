<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * What an asset depicts (for example "Bird"), from the research plan (PRD.md §6.1, decision D41).
 * Named AssetObject because `Object` is reserved in PHP.
 */
#[Fillable(['name', 'is_active', 'sort_order'])]
class AssetObject extends Model
{
    protected $table = 'objects';

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}

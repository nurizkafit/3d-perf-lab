<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'asset_id', 'vertices', 'triangles', 'meshes', 'materials', 'textures',
    'texture_dimensions_json', 'texture_memory_estimate_bytes',
])]
class AssetMetric extends Model
{
    protected $primaryKey = 'asset_id';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['texture_dimensions_json' => 'array'];
    }
}

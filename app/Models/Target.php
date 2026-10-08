<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A simple performance goal for one kind of device: three checks (BENCHMARK.md §6). */
#[Fillable(['name', 'max_load_s', 'target_fps', 'max_file_size_mb', 'is_default', 'is_active', 'sort_order'])]
class Target extends Model
{
    protected function casts(): array
    {
        return [
            'max_load_s' => 'float',
            'target_fps' => 'integer',
            'max_file_size_mb' => 'float',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function deviceClasses(): BelongsToMany
    {
        return $this->belongsToMany(DeviceClass::class);
    }

    /** The values a test stores as its snapshot (PRD.md §6.3). */
    public function snapshot(): array
    {
        return [
            'target_id' => $this->id,
            'name' => $this->name,
            'max_load_s' => $this->max_load_s,
            'target_fps' => $this->target_fps,
            'max_file_size_mb' => $this->max_file_size_mb,
        ];
    }
}

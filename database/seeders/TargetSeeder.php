<?php

namespace Database\Seeders;

use App\Models\DeviceClass;
use App\Models\Target;
use Illuminate\Database\Seeder;

class TargetSeeder extends Seeder
{
    /** BENCHMARK.md §6.2: load time (s), goal frame rate (fps), file size (MB), linked device classes. */
    private const TARGETS = [
        'Desktop' => [
            'values' => [3, 60, 20],
            'classes' => ['Desktop — Integrated GPU', 'Desktop — Dedicated GPU', 'Laptop — Integrated GPU', 'Laptop — Dedicated GPU'],
            'default' => true,
        ],
        'Phone — Flagship' => [
            'values' => [3, 60, 12],
            'classes' => ['Android Phone — Flagship', 'iPhone — Pro / Flagship'],
        ],
        'Phone — Mid-range' => [
            'values' => [4, 30, 8],
            'classes' => ['Android Phone — Mid-range', 'iPhone — Standard'],
        ],
        'Phone — Entry' => [
            'values' => [6, 30, 5],
            'classes' => ['Android Phone — Entry'],
        ],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::TARGETS as $name => $definition) {
            [$load, $fps, $size] = $definition['values'];
            $target = Target::firstOrCreate(
                ['name' => $name],
                ['max_load_s' => $load, 'target_fps' => $fps, 'max_file_size_mb' => $size, 'is_default' => $definition['default'] ?? false, 'sort_order' => $order++],
            );

            if ($target->wasRecentlyCreated) {
                $target->deviceClasses()->sync(DeviceClass::whereIn('name', $definition['classes'])->pluck('id'));
            }
        }
    }
}

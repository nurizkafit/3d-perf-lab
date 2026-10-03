<?php

namespace Database\Seeders;

use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use Illuminate\Database\Seeder;

class BudgetPresetSeeder extends Seeder
{
    /**
     * BENCHMARK.md §6.2. Order of limits: file size (MB), cold TTFF (ms), warm TTFF (ms),
     * p95 frame (ms), jank (%), draw calls, texture memory (MB), headroom instances.
     */
    private const PRESETS = [
        'Desktop — Standard' => [
            'classes' => ['Desktop — Integrated GPU', 'Desktop — Dedicated GPU', 'Laptop — Integrated GPU', 'Laptop — Dedicated GPU'],
            'limits' => [20, 3000, 1000, 16.7, 1, 500, 512, 50],
            'default' => true,
        ],
        'Mobile — Mid-range' => [
            'classes' => ['Android Phone — Mid-range', 'iPhone — Standard'],
            'limits' => [8, 4000, 1500, 33.3, 2, 200, 256, 20],
        ],
        'Mobile — Entry' => [
            'classes' => ['Android Phone — Entry'],
            'limits' => [5, 6000, 2500, 33.3, 3, 100, 128, 10],
        ],
        'Mobile — Flagship' => [
            'classes' => ['Android Phone — Flagship', 'iPhone — Pro / Flagship'],
            'limits' => [12, 3000, 1000, 16.7, 1, 300, 384, 40],
        ],
    ];

    private const KEYS = [
        'max_file_size_mb', 'max_ttff_cold_ms', 'max_ttff_warm_ms', 'max_p95_frame_ms',
        'max_jank_pct', 'max_draw_calls', 'max_texture_memory_mb', 'min_headroom_instances',
    ];

    public function run(): void
    {
        $order = 0;

        foreach (self::PRESETS as $name => $preset) {
            $items = [];
            foreach (self::KEYS as $i => $key) {
                $items[$key] = ['enabled' => true, 'limit' => $preset['limits'][$i]];
            }
            // No seed value in BENCHMARK.md §6.2; disabled until set (decision D24).
            $items['min_instances_at_budget'] = ['enabled' => false, 'limit' => null];

            $budget = BudgetPreset::firstOrCreate(
                ['name' => $name],
                ['items_json' => $items, 'is_default' => $preset['default'] ?? false, 'sort_order' => $order++],
            );

            if ($budget->wasRecentlyCreated) {
                $budget->deviceClasses()->sync(
                    DeviceClass::whereIn('name', $preset['classes'])->pluck('id'),
                );
            }
        }
    }
}

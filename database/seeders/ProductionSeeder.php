<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Production base data (DEPLOYMENT.md §5.1 step 9): first admin, tools, presets, device classes, Targets.
 * Never development fixtures, never Objects (the research plan defines them; an admin adds them). Idempotent.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ToolSeeder::class,
            OptionPresetSeeder::class,
            DeviceClassSeeder::class,
            TargetSeeder::class,
        ]);
    }
}

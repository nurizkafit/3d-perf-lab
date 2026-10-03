<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Base data for every environment. Each seeder is idempotent.
     * DevFixtureSeeder is not called here; it runs in the verification environment only.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ToolSeeder::class,
            OptionPresetSeeder::class,
            DeviceClassSeeder::class,
            BudgetPresetSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\DeviceClass;
use Illuminate\Database\Seeder;

class DeviceClassSeeder extends Seeder
{
    // BENCHMARK.md §7.2.
    public const CLASSES = [
        ['Desktop — Integrated GPU', 'desktop', 'any'],
        ['Desktop — Dedicated GPU', 'desktop', 'any'],
        ['Laptop — Integrated GPU', 'laptop', 'any'],
        ['Laptop — Dedicated GPU', 'laptop', 'any'],
        ['Android Phone — Entry', 'phone', 'android'],
        ['Android Phone — Mid-range', 'phone', 'android'],
        ['Android Phone — Flagship', 'phone', 'android'],
        ['iPhone — Standard', 'phone', 'ios'],
        ['iPhone — Pro / Flagship', 'phone', 'ios'],
        ['Android Tablet', 'tablet', 'android'],
        ['iPad', 'tablet', 'ios'],
    ];

    public function run(): void
    {
        foreach (self::CLASSES as $order => [$name, $category, $platform]) {
            DeviceClass::firstOrCreate(
                ['name' => $name],
                ['category' => $category, 'platform' => $platform, 'sort_order' => $order],
            );
        }
    }
}

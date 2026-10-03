<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Blender', 'Prisma3D', 'Spline'] as $order => $name) {
            Tool::firstOrCreate(['name' => $name], ['sort_order' => $order]);
        }
    }
}

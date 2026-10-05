<?php

namespace App\Support;

/**
 * Sample data for the static app screens (M1.3). Never stored.
 * Every screen that shows it must show the "Sample data" badge.
 * Replaced by real queries from M2 onwards.
 */
class SampleScreens
{
    /** @return list<array<string, mixed>> */
    public static function assets(): array
    {
        return [
            ['id' => 1, 'name' => 'Apple', 'tool' => 'Blender', 'author' => 'Dummy Author A', 'uploaded_by' => 'Dummy Admin', 'format' => 'GLB', 'size' => '10.2 KB', 'triangles' => 1204, 'vertices' => 642, 'uploaded' => '2026-10-03', 'hours' => 1.5],
            ['id' => 2, 'name' => 'Chicken', 'tool' => 'Prisma3D', 'author' => 'Dummy Author B', 'uploaded_by' => 'Dummy Admin', 'format' => 'GLB', 'size' => '9.7 KB', 'triangles' => 980, 'vertices' => 517, 'uploaded' => '2026-10-03', 'hours' => 2.0],
            ['id' => 3, 'name' => 'Book', 'tool' => 'Spline', 'author' => 'Dummy Author C', 'uploaded_by' => 'Dummy User', 'format' => 'GLB', 'size' => '5.7 KB', 'triangles' => 96, 'vertices' => 64, 'uploaded' => '2026-10-02', 'hours' => 0.5],
            ['id' => 4, 'name' => 'Apple, low poly', 'tool' => 'Blender', 'author' => 'Dummy Author A', 'uploaded_by' => 'Dummy User', 'format' => 'GLTF', 'size' => '6.1 KB', 'triangles' => 312, 'vertices' => 180, 'uploaded' => '2026-09-30', 'hours' => 0.75],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function tests(): array
    {
        return [
            ['id' => 1, 'name' => 'Three tools, mid-range Android', 'method' => 'comparison', 'date' => '2026-09-28 14:10', 'assets' => 3, 'device' => 'Android Phone — Mid-range', 'budget' => 'Mobile — Mid-range', 'status' => 'completed', 'summary' => '2 of 3 assets meet all budget items'],
            ['id' => 2, 'name' => 'Chicken scale, desktop', 'method' => 'stress', 'date' => '2026-09-21 09:42', 'assets' => 1, 'device' => 'Desktop — Dedicated GPU', 'budget' => 'Desktop — Standard', 'status' => 'completed', 'summary' => '1 of 1 asset meets all budget items'],
            ['id' => 3, 'name' => 'Book headroom, iPhone', 'method' => 'headroom', 'date' => '2026-09-14 18:03', 'assets' => 1, 'device' => 'iPhone — Standard', 'budget' => 'Mobile — Mid-range', 'status' => 'completed', 'summary' => '1 of 1 asset meets all budget items'],
            ['id' => 4, 'name' => 'Mixed Blender scene', 'method' => 'stress', 'date' => '2026-09-02 11:27', 'assets' => 2, 'device' => 'Laptop — Integrated GPU', 'budget' => 'Desktop — Standard', 'status' => 'completed', 'summary' => '0 of 2 assets meet all budget items'],
            ['id' => 5, 'name' => 'Quick check, laptop', 'method' => 'comparison', 'date' => '2026-09-01 16:55', 'assets' => 2, 'device' => 'Laptop — Integrated GPU', 'budget' => 'Desktop — Standard', 'status' => 'invalid', 'summary' => 'Run invalid: the tab was hidden during measurement'],
        ];
    }
}

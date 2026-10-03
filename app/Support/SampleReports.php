<?php

namespace App\Support;

/**
 * Sample public reports for the static public site (M1.2). Never stored.
 * Every page that shows them must show the "Sample data" badge.
 * The shape follows the allowed public fields in SITEMAP.md §7.
 * Replaced by PublicReportPresenter and real reports in M4.5.
 */
class SampleReports
{
    public const METHODS = [
        'comparison' => ['label' => 'Comparison Test', 'icon' => 'columns'],
        'stress' => ['label' => 'Stress Test', 'icon' => 'stack'],
        'headroom' => ['label' => 'Headroom Test', 'icon' => 'gauge'],
    ];

    /** Standard limitations shown in every report (BENCHMARK.md §9). */
    public const STANDARD_LIMITATIONS = [
        'Results apply to the recorded device and browser only.',
        'Browsers limit timer precision; very short intervals have coarse resolution.',
        'Frame interval is capped by the display refresh rate.',
        'GPU time is available on some browsers only.',
        'Mobile devices can reduce performance when hot or on battery saver.',
        'Texture memory is an estimate, not a measured GPU value.',
    ];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return [
            [
                'slug' => 'fruit-model-mid-range-android-x7k2',
                'title' => 'Three tools, one budget: mid-range Android',
                'question' => 'Which tool export meets the Mobile — Mid-range budget on a mid-range Android phone?',
                'method' => 'comparison',
                'scenario' => null,
                'device_class' => 'Android Phone — Mid-range',
                'published_at' => '2026-09-28',
                'assets' => [
                    ['name' => 'Apple', 'tool' => 'Blender', 'author' => 'Dummy Author A', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 1204],
                    ['name' => 'Chicken', 'tool' => 'Prisma3D', 'author' => 'Dummy Author B', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 980],
                    ['name' => 'Book', 'tool' => 'Spline', 'author' => 'Dummy Author C', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 96],
                ],
                'budget_preset' => 'Mobile — Mid-range',
                'budget' => [
                    'max_file_size_mb' => [8, ['pass', 0.01], ['pass', 0.01], ['pass', 0.01]],
                    'max_ttff_cold_ms' => [4000, ['pass', 412.3], ['pass', 388.1], ['fail', 5201.7]],
                    'max_p95_frame_ms' => [33.3, ['pass', 16.9], ['pass', 17.2], ['pass', 16.8]],
                    'max_jank_pct' => [2, ['pass', 0.4], ['pass', 0.7], ['not-measured', null]],
                    'max_draw_calls' => [200, ['pass', 3], ['pass', 5], ['pass', 1]],
                ],
                'configuration' => ['Repetitions' => '3', 'Warm-up' => '2 s', 'Measurement window' => '10 s', 'Resolution' => '1280×720', 'Camera' => 'Auto-frame', 'Lighting' => 'Studio', 'Background' => 'Neutral'],
                'environment' => ['Browser' => 'Chrome 141', 'OS' => 'Android 15', 'GPU' => 'Adreno (TM) 642L', 'Refresh rate (estimate)' => '60 Hz', 'Three.js' => 'r186'],
                'limitations' => ['The phone was at 80% battery and not charging.'],
                'conclusion' => 'Apple and Chicken meet every enabled budget item. Book fails the cold time to first frame.',
            ],
            [
                'slug' => 'chicken-stress-desktop-q91m',
                'title' => 'How far does the chicken scale on desktop?',
                'question' => 'At how many instances does the Prisma3D chicken leave the Desktop — Standard frame budget?',
                'method' => 'stress',
                'scenario' => 'repeated',
                'device_class' => 'Desktop — Dedicated GPU',
                'published_at' => '2026-09-21',
                'assets' => [
                    ['name' => 'Chicken', 'tool' => 'Prisma3D', 'author' => 'Dummy Author B', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 980],
                ],
                'budget_preset' => 'Desktop — Standard',
                'budget' => [
                    'max_file_size_mb' => [20, ['pass', 0.01]],
                    'max_p95_frame_ms' => [16.7, ['pass', 16.6]],
                    'max_jank_pct' => [1, ['pass', 0.2]],
                    'max_draw_calls' => [500, ['pass', 50]],
                ],
                'configuration' => ['Scenario' => 'Repeated asset', 'Instance steps' => '1 / 5 / 10 / 25 / 50', 'Repetitions' => '3', 'Resolution' => '1920×1080', 'Camera' => 'Auto-frame (largest step)'],
                'environment' => ['Browser' => 'Firefox 143', 'OS' => 'Windows 11', 'GPU' => 'Not exposed by the browser', 'Refresh rate (estimate)' => '144 Hz', 'Three.js' => 'r186'],
                'limitations' => [],
                'conclusion' => null,
            ],
            [
                'slug' => 'book-headroom-iphone-b3pz',
                'title' => 'Headroom for a simple book on iPhone',
                'question' => 'How many Spline book instances can an iPhone — Standard hold inside the Mobile — Mid-range budget?',
                'method' => 'headroom',
                'scenario' => 'repeated',
                'device_class' => 'iPhone — Standard',
                'published_at' => '2026-09-14',
                'assets' => [
                    ['name' => 'Book', 'tool' => 'Spline', 'author' => 'Dummy Author C', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 96],
                ],
                'budget_preset' => 'Mobile — Mid-range',
                'budget' => [
                    'max_p95_frame_ms' => [33.3, ['pass', 33.1]],
                    'max_jank_pct' => [2, ['pass', 1.6]],
                    'min_headroom_instances' => [20, ['pass', 184]],
                ],
                'configuration' => ['Scenario' => 'Repeated asset', 'Cap' => '1,024', 'Probe' => '1 s warm-up, 4 s window', 'Repetitions' => '3'],
                'environment' => ['Browser' => 'Safari 26', 'OS' => 'iOS 26', 'GPU' => 'Apple GPU', 'Refresh rate (estimate)' => '60 Hz', 'Three.js' => 'r186'],
                'limitations' => ['iPhone GPU strings are masked; the tier was chosen by the tester.'],
                'conclusion' => 'The book stays inside the budget up to 184 instances (median of 3).',
            ],
            [
                'slug' => 'mixed-stress-laptop-k8wd',
                'title' => 'Mixed Blender scene on an integrated-GPU laptop',
                'question' => 'Does a mixed scene of Blender models stay inside the Desktop — Standard budget at 25 instances?',
                'method' => 'stress',
                'scenario' => 'mixed',
                'device_class' => 'Laptop — Integrated GPU',
                'published_at' => '2026-09-02',
                'assets' => [
                    ['name' => 'Apple', 'tool' => 'Blender', 'author' => 'Dummy Author A', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 1204],
                    ['name' => 'Apple, low poly', 'tool' => 'Blender', 'author' => 'Dummy Author A', 'format' => 'GLB', 'file_size_mb' => 0.01, 'triangles' => 312],
                ],
                'budget_preset' => 'Desktop — Standard',
                'budget' => [
                    'max_p95_frame_ms' => [16.7, ['fail', 21.4], ['fail', 21.4]],
                    'max_jank_pct' => [1, ['fail', 3.8], ['fail', 3.8]],
                ],
                'configuration' => ['Scenario' => 'Mixed asset', 'Instance steps' => '1 / 5 / 10 / 25 / 50', 'Repetitions' => '3'],
                'environment' => ['Browser' => 'Chrome 141', 'OS' => 'macOS 26', 'GPU' => 'Apple M2', 'Refresh rate (estimate)' => '60 Hz', 'Three.js' => 'r186'],
                'limitations' => [],
                'conclusion' => null,
            ],
        ];
    }

    public static function find(string $slug): ?array
    {
        return collect(self::all())->firstWhere('slug', $slug);
    }
}

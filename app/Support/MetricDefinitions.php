<?php

namespace App\Support;

/**
 * Labels and short explanations for budget items and metrics, shown next to the values.
 * The definitions themselves are in BENCHMARK.md (§3, §6.1); this text only explains them.
 */
class MetricDefinitions
{
    /** Budget items (BENCHMARK.md §6.1). `methods` = the methods the item applies to. */
    public const BUDGET_ITEMS = [
        'max_file_size_mb' => [
            'label' => 'File size', 'unit' => 'MB', 'rule' => '≤', 'methods' => ['comparison', 'stress', 'headroom'],
            'help' => 'Size of the model file, including the .bin and texture files of a GLTF.',
        ],
        'max_ttff_cold_ms' => [
            'label' => 'Time to first frame, cold', 'unit' => 'ms', 'rule' => '≤', 'methods' => ['comparison'],
            'help' => 'Download, parse, GPU upload, and first frame with an empty cache. Median of the repetitions.',
        ],
        'max_ttff_warm_ms' => [
            'label' => 'Time to first frame, warm', 'unit' => 'ms', 'rule' => '≤', 'methods' => ['comparison'],
            'help' => 'The same load a second time, with the file in the browser cache. Median of the repetitions.',
        ],
        'max_p95_frame_ms' => [
            'label' => 'p95 frame interval', 'unit' => 'ms', 'rule' => '≤', 'methods' => ['comparison', 'stress', 'headroom'],
            'help' => '95% of frames arrive within this time; the slowest 5% take longer. Lower is smoother.',
        ],
        'max_jank_pct' => [
            'label' => 'Jank frames', 'unit' => '%', 'rule' => '≤', 'methods' => ['comparison', 'stress', 'headroom'],
            'help' => 'Share of frames that take more than twice the budget frame time: visible stutter.',
        ],
        'max_draw_calls' => [
            'label' => 'Draw calls', 'unit' => '', 'rule' => '≤', 'methods' => ['comparison', 'stress'],
            'help' => 'Number of draw commands the renderer sends to the GPU for one frame of the stable scene.',
        ],
        'max_texture_memory_mb' => [
            'label' => 'Texture memory', 'unit' => 'MB', 'rule' => '≤', 'methods' => ['comparison', 'stress', 'headroom'],
            'help' => 'Estimated GPU memory for the textures (width × height × 4 bytes, more with mipmaps). An estimate, not measured.',
        ],
        'min_headroom_instances' => [
            'label' => 'Headroom instances', 'unit' => '', 'rule' => '≥', 'methods' => ['headroom'],
            'help' => 'Largest instance count that stays inside the p95 frame and jank limits. Median of the repetitions.',
        ],
        'min_instances_at_budget' => [
            'label' => 'Instances at budget', 'unit' => '', 'rule' => '≥', 'methods' => ['stress'],
            'help' => 'Largest Stress step that still meets the p95 frame interval limit.',
        ],
    ];

    /** Runtime and load metrics shown in result tables (BENCHMARK.md §3.2, §3.3). */
    public const METRICS = [
        'interval_p95_ms' => ['label' => 'p95 frame interval', 'unit' => 'ms', 'help' => 'Time between frames that 95% of frames do not exceed. Shows stutter that an average hides.'],
        'interval_p50_ms' => ['label' => 'p50 frame interval', 'unit' => 'ms', 'help' => 'The typical time between frames (median). Capped by the display refresh rate.'],
        'work_p95_ms' => ['label' => 'Render work p95', 'unit' => 'ms', 'help' => 'CPU time spent in one render call (95th percentile). Not capped by the display, so it shows cost.'],
        'gpu_p95_ms' => ['label' => 'GPU time p95', 'unit' => 'ms', 'help' => 'GPU time per frame from a WebGL timer query. Only some browsers expose it; never estimated.'],
        'jank_pct' => ['label' => 'Jank', 'unit' => '%', 'help' => 'Share of frames slower than twice the budget frame time.'],
        'ttff_cold_ms' => ['label' => 'Time to first frame, cold', 'unit' => 'ms', 'help' => 'Download + parse + GPU upload + first frame, with an empty cache.'],
        'draw_calls' => ['label' => 'Draw calls', 'unit' => '', 'help' => 'Draw commands per frame in the stable scene (browser-reported by the renderer).'],
    ];

    /** Budget items that apply to one method, in display order. */
    public static function budgetItemsFor(string $method): array
    {
        return array_filter(self::BUDGET_ITEMS, fn ($item) => in_array($method, $item['methods'], true));
    }
}

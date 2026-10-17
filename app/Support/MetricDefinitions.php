<?php

namespace App\Support;

/**
 * Labels and short explanations for Target checks, smoothness labels, and metrics, shown next to the values.
 * The definitions themselves are in BENCHMARK.md (§3, §4.4, §6); this text only explains them.
 */
class MetricDefinitions
{
    /** Test methods (BENCHMARK.md §4). */
    public const METHODS = [
        'side_by_side' => ['label' => 'Side-by-Side Test', 'icon' => 'columns'],
        'crowd' => ['label' => 'Crowd Test', 'icon' => 'users-three'],
    ];

    /** Target fields (BENCHMARK.md §6.2), in display order. */
    public const TARGET_FIELDS = [
        'max_load_s' => [
            'label' => 'Loads in', 'unit' => 's', 'rule' => '≤', 'min' => 0.1, 'max' => 60, 'step' => 0.1,
            'help' => 'Time to first frame on a first visit (cold load): download, parse, GPU upload, and first frame.',
        ],
        'target_fps' => [
            'label' => 'Smooth at', 'unit' => 'fps', 'rule' => '', 'min' => 10, 'max' => 240, 'step' => 1,
            'help' => 'Goal frame rate. A scene is Smooth when 95% of frames arrive within 1.25 × the goal frame time.',
        ],
        'max_file_size_mb' => [
            'label' => 'File size', 'unit' => 'MB', 'rule' => '≤', 'min' => 0.1, 'max' => 1000, 'step' => 0.1,
            'help' => 'Size of the model file, including the .bin and texture files of a GLTF.',
        ],
    ];

    /** Target checks (BENCHMARK.md §6.1). */
    public const TARGET_CHECKS = [
        'load' => ['label' => 'Loads in time', 'help' => 'Median cold time to first frame is within the Target load time.'],
        'smooth' => ['label' => 'Runs smooth', 'help' => 'The motion scenario is Smooth at the Target frame rate.'],
        'file_size' => ['label' => 'File size', 'help' => 'The model file is within the Target file size.'],
    ];

    /** Smoothness labels (BENCHMARK.md §4.4). */
    public const SMOOTHNESS = [
        'smooth' => ['label' => 'Smooth', 'help' => '95% of frames within 1.25 × the goal frame time, and at most 1% stutter.'],
        'mostly_smooth' => ['label' => 'Mostly smooth', 'help' => '95% of frames within 2 × the goal frame time.'],
        'choppy' => ['label' => 'Choppy', 'help' => 'Slower than 2 × the goal frame time for more than 5% of frames.'],
    ];

    /** Motion scenarios (BENCHMARK.md §4.2), in run order. */
    public const MOTIONS = [
        'static' => ['label' => 'Static', 'help' => 'Nothing moves; the camera is fixed.'],
        'spin' => ['label' => 'Spin', 'help' => 'Each object turns around its own vertical axis at 180° per second.'],
        'physics' => ['label' => 'Physics', 'help' => 'The objects fall into an open box under gravity; then the box shakes.'],
    ];

    /** Technical metrics shown in the collapsed Technical details (BENCHMARK.md §3.2, §3.3). */
    public const METRICS = [
        'interval_p95_ms' => ['label' => 'p95 frame interval', 'unit' => 'ms', 'help' => 'Time between frames that 95% of frames do not exceed. Shows stutter that an average hides.'],
        'interval_p50_ms' => ['label' => 'p50 frame interval', 'unit' => 'ms', 'help' => 'The typical time between frames (median). Capped by the display refresh rate.'],
        'work_p95_ms' => ['label' => 'Render work p95', 'unit' => 'ms', 'help' => 'CPU time spent in one render call (95th percentile). Not capped by the display, so it shows cost.'],
        'physics_p95_ms' => ['label' => 'Physics time p95', 'unit' => 'ms', 'help' => 'CPU time of one physics step (95th percentile). Physics scenario only.'],
        'gpu_p95_ms' => ['label' => 'GPU time p95', 'unit' => 'ms', 'help' => 'GPU time per frame from a WebGL timer query. Only some browsers expose it; never estimated.'],
        'jank_pct' => ['label' => 'Stutter', 'unit' => '%', 'help' => 'Share of frames slower than twice the goal frame time.'],
        'ttff_cold_ms' => ['label' => 'Time to first frame, cold', 'unit' => 'ms', 'help' => 'Download + parse + GPU upload + first frame, with an empty cache.'],
        'draw_calls' => ['label' => 'Draw calls', 'unit' => '', 'help' => 'Draw commands per frame (browser-reported by the renderer).'],
        'interval_p99_ms' => ['label' => 'p99 frame interval', 'unit' => 'ms', 'help' => 'Time between frames that 99% of frames do not exceed.'],
        'fps_avg' => ['label' => 'Average frame rate', 'unit' => 'fps', 'help' => '1000 ÷ the mean frame interval. Hides stutter; use the p95 values for smoothness.'],
        'fps_1pct_low' => ['label' => '1% low frame rate', 'unit' => 'fps', 'help' => 'Frame rate of the slowest 1% of frames.'],
        'work_p50_ms' => ['label' => 'Render work p50', 'unit' => 'ms', 'help' => 'Typical CPU time spent in one render call.'],
        'rendered_triangles' => ['label' => 'Rendered triangles', 'unit' => '', 'help' => 'Triangles drawn per frame (browser-reported by the renderer).'],
        'textures_in_memory' => ['label' => 'Textures in memory', 'unit' => '', 'help' => 'Textures the renderer holds (browser-reported by the renderer).'],
        'js_heap_used' => ['label' => 'JS heap', 'unit' => 'bytes', 'help' => 'Browser-reported JavaScript heap (Chromium only). Excludes GPU memory.'],
        'frames' => ['label' => 'Frames in the window', 'unit' => '', 'help' => 'Number of frames rendered during the measurement window.'],
    ];
}

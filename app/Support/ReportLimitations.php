<?php

namespace App\Support;

/** Standard limitations shown in every report (BENCHMARK.md §9). Authors can add more; they cannot remove these. */
class ReportLimitations
{
    public const STANDARD = [
        'Results apply to the recorded device and browser only.',
        'Browsers limit timer precision; very short intervals have coarse resolution.',
        'Frame interval is capped by the display refresh rate.',
        'GPU time is available on some browsers only.',
        'Mobile devices can reduce performance when hot or on battery saver.',
        'Texture memory is an estimate, not a measured GPU value.',
        'JS heap is browser-reported and excludes GPU memory.',
        'Physics uses a simplified convex hull per asset, not the exact mesh; concave parts are filled. Physics time depends partly on the hull, not only on the asset.',
    ];

    /** Author lines: one limitation per non-empty line. */
    public static function authorLines(?string $text): array
    {
        return collect(preg_split('/\R/', (string) $text))->map(fn ($line) => trim($line))->filter()->values()->all();
    }
}

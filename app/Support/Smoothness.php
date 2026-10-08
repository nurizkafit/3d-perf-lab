<?php

namespace App\Support;

/**
 * Smoothness label of one measurement (BENCHMARK.md §4.4, decision D44). Arithmetic on submitted values,
 * not measurement. resources/js/three/smoothness.js applies the same rule in the browser (Crowd search).
 */
class Smoothness
{
    /** p95 frame interval limit for Smooth, as a multiple of the goal frame time. */
    public const SMOOTH_FACTOR = 1.25;

    /** p95 frame interval limit for Mostly smooth. Also the stutter (jank) threshold per frame. */
    public const MOSTLY_FACTOR = 2.0;

    /** Maximum share of stutter frames for Smooth, in percent. */
    public const MAX_JANK_PCT = 1.0;

    public static function goalFrameMs(int|float $targetFps): float
    {
        return 1000 / $targetFps;
    }

    /** @return 'smooth'|'mostly_smooth'|'choppy'|null  null when the values are missing */
    public static function label(?float $intervalP95Ms, ?float $jankPct, int|float $targetFps): ?string
    {
        if ($intervalP95Ms === null || $jankPct === null) {
            return null;
        }
        $goal = self::goalFrameMs($targetFps);

        return match (true) {
            $intervalP95Ms <= self::SMOOTH_FACTOR * $goal && $jankPct <= self::MAX_JANK_PCT => 'smooth',
            $intervalP95Ms <= self::MOSTLY_FACTOR * $goal => 'mostly_smooth',
            default => 'choppy',
        };
    }
}

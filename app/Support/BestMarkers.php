<?php

namespace App\Support;

/**
 * Best markers (BENCHMARK.md §4.5, decision D39): the best item for one metric. No marker combines metrics, and a
 * marker never re-sorts the results. Calculated when results are shown, from the stored aggregates.
 */
class BestMarkers
{
    /** Markers and their metrics. "Lower" is better for all Side-by-Side markers. */
    public const MARKERS = [
        'fastest_load' => ['label' => 'Fastest load', 'help' => 'Lowest median cold time to first frame.'],
        'smallest_file' => ['label' => 'Smallest file', 'help' => 'Smallest model file.'],
        'most_copies' => ['label' => 'Most copies', 'help' => 'Highest median number of Smooth copies in this motion scenario.'],
        'lightest' => ['label' => 'Lightest to draw', 'help' => 'Lowest median render work time (p95) in this motion scenario. Frame interval is capped by the display, so two Smooth assets can still differ in cost.'],
    ];

    /**
     * Repeated measurement, lower is better. The best item gets the marker only when its min–max spread does not
     * overlap the spread of any other item; otherwise the result is "Too close to call".
     *
     * @param  array<int|string, array{median: float|int, min: float|int, max: float|int}|null>  $summaries  per item
     * @return array{winners: list<int|string>, too_close: bool}
     */
    public static function lowestSpread(array $summaries): array
    {
        $items = array_filter($summaries, fn ($summary) => $summary !== null);
        if (count($items) < 2) {
            return ['winners' => [], 'too_close' => false];
        }
        uasort($items, fn ($a, $b) => $a['median'] <=> $b['median']);
        $bestKey = array_key_first($items);
        $best = $items[$bestKey];
        foreach ($items as $key => $other) {
            if ($key !== $bestKey && $best['max'] >= $other['min']) {
                return ['winners' => [], 'too_close' => true];
            }
        }

        return ['winners' => [$bestKey], 'too_close' => false];
    }

    /**
     * Repeated measurement, higher is better (Most copies). Same spread rule as lowestSpread(). With one repetition
     * the spread is the value itself: equal counts, also two results at the cap, are "Too close to call" (D65).
     *
     * @param  array<int|string, array{median: float|int, min: float|int, max: float|int}|null>  $summaries
     * @return array{winners: list<int|string>, too_close: bool}
     */
    public static function highestSpread(array $summaries): array
    {
        $negated = array_map(fn ($s) => $s === null ? null : ['median' => -$s['median'], 'min' => -$s['max'], 'max' => -$s['min']], $summaries);

        return self::lowestSpread($negated);
    }

    /**
     * Static value, lower is better: the lowest value wins; equal values share the marker.
     *
     * @param  array<int|string, float|int|null>  $values
     * @return array{winners: list<int|string>, too_close: bool}
     */
    public static function lowestStatic(array $values): array
    {
        $values = array_filter($values, fn ($value) => $value !== null);
        if (count($values) < 2) {
            return ['winners' => [], 'too_close' => false];
        }
        $lowest = min($values);

        return ['winners' => array_keys(array_filter($values, fn ($value) => $value == $lowest)), 'too_close' => false];
    }
}

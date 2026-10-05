<?php

namespace App\Support;

/**
 * Arithmetic on submitted summaries (BENCHMARK.md §4.1 step 7, §5): median of repetitions, min–max spread,
 * and the "Unstable" note. This is not measurement; the browser measures.
 */
class Aggregate
{
    /** Spread of interval_p95_ms above this share of the median marks a result as unstable. */
    public const UNSTABLE_SPREAD = 0.25;

    /** @param list<float|int|null> $values */
    public static function summary(array $values): ?array
    {
        $values = array_values(array_filter($values, fn ($value) => $value !== null));
        if ($values === []) {
            return null;
        }
        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);
        $median = $count % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;

        return ['median' => $median, 'min' => $values[0], 'max' => $values[$count - 1], 'n' => $count];
    }

    /**
     * Aggregate many repetitions of one metrics record (same asset and step).
     *
     * @param  list<array<string, mixed>>  $records
     * @return array<string, array{median: float|int, min: float|int, max: float|int, n: int}|null>
     */
    public static function records(array $records): array
    {
        $keys = collect($records)->flatMap(fn ($record) => array_keys(array_filter($record, 'is_numeric')))->unique();

        return $keys->mapWithKeys(fn ($key) => [$key => self::summary(array_column($records, $key))])->all();
    }

    public static function unstable(?array $p95): bool
    {
        return $p95 !== null && $p95['median'] > 0 && ($p95['max'] - $p95['min']) > self::UNSTABLE_SPREAD * $p95['median'];
    }
}

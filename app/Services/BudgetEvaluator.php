<?php

namespace App\Services;

use App\Support\MetricDefinitions;

/**
 * Pass / Fail / Not measured for each enabled budget item (BENCHMARK.md §6.1).
 * Input values are the aggregated (median) results of one asset, or one asset set for mixed scenarios.
 * Disabled items and items that do not apply to the method produce no row.
 */
class BudgetEvaluator
{
    /** Budget item → measured value key, and the factor that converts the value to the item's unit. */
    private const SOURCES = [
        'max_file_size_mb' => ['file_size_bytes', 1 / 1048576],
        'max_ttff_cold_ms' => ['ttff_cold_ms', 1],
        'max_ttff_warm_ms' => ['ttff_warm_ms', 1],
        'max_p95_frame_ms' => ['interval_p95_ms', 1],
        'max_jank_pct' => ['jank_pct', 1],
        'max_draw_calls' => ['draw_calls', 1],
        'max_texture_memory_mb' => ['texture_memory_estimate_bytes', 1 / 1048576],
        'min_headroom_instances' => ['headroom_instances', 1],
        'min_instances_at_budget' => ['instances_at_budget', 1],
    ];

    /**
     * @param  array<string, array{enabled: bool, limit: float|int|null}>  $items  budget snapshot
     * @param  array<string, float|int|null>  $measured
     * @return list<array{item_key: string, limit_value: float, measured_value: float|null, outcome: string}>
     */
    public function evaluate(array $items, string $method, array $measured): array
    {
        $rows = [];

        foreach (MetricDefinitions::BUDGET_ITEMS as $key => $definition) {
            $item = $items[$key] ?? null;
            if (! ($item['enabled'] ?? false) || $item['limit'] === null || ! in_array($method, $definition['methods'], true)) {
                continue;
            }

            [$source, $factor] = self::SOURCES[$key];
            $raw = $measured[$source] ?? null;
            // Compare the exact value; only the stored copy is rounded.
            $value = $raw === null ? null : $raw * $factor;
            $limit = (float) $item['limit'];

            $rows[] = [
                'item_key' => $key,
                'limit_value' => $limit,
                'measured_value' => $value === null ? null : round($value, 3),
                'outcome' => match (true) {
                    $value === null => 'not_measured',
                    $definition['rule'] === '≤' => $value <= $limit ? 'pass' : 'fail',
                    default => $value >= $limit ? 'pass' : 'fail',
                },
            ];
        }

        return $rows;
    }

    /** "Meets all enabled budget items": every row passes. Not a score (BENCHMARK.md §6.1). */
    public static function meetsAll(array $rows): bool
    {
        return $rows !== [] && collect($rows)->every(fn ($row) => $row['outcome'] === 'pass');
    }
}

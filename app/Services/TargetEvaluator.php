<?php

namespace App\Services;

use App\Support\Smoothness;

/**
 * Target checks of one asset (BENCHMARK.md §6.1): Loads in time, Runs smooth (one per motion scenario), and
 * File size. Input values are the aggregated (median) results; the server never measures.
 */
class TargetEvaluator
{
    /**
     * @param  array{max_load_s: float|int, target_fps: int, max_file_size_mb: float|int}  $target  snapshot
     * @param  array{ttff_cold_ms?: float|null, file_size_bytes?: int|null, motions?: array<string, array{interval_p95_ms: float|null, jank_pct: float|null}>}  $measured
     * @return list<array{check_key: string, motion: string|null, limit_value: float, measured_value: float|null, label: string|null, outcome: string}>
     */
    public function evaluate(array $target, array $measured): array
    {
        $rows = [];

        if (array_key_exists('ttff_cold_ms', $measured)) {
            $seconds = $measured['ttff_cold_ms'] === null ? null : $measured['ttff_cold_ms'] / 1000;
            $rows[] = $this->row('load', null, (float) $target['max_load_s'], $seconds, null);
        }

        foreach ($measured['motions'] ?? [] as $motion => $values) {
            $label = Smoothness::label($values['interval_p95_ms'] ?? null, $values['jank_pct'] ?? null, $target['target_fps']);
            $rows[] = [
                'check_key' => 'smooth',
                'motion' => $motion,
                'limit_value' => (float) $target['target_fps'],
                'measured_value' => isset($values['interval_p95_ms']) && $values['interval_p95_ms'] > 0 ? round(1000 / $values['interval_p95_ms'], 3) : null,
                'label' => $label,
                'outcome' => $label === null ? 'not_measured' : ($label === 'smooth' ? 'pass' : 'fail'),
            ];
        }

        if (array_key_exists('file_size_bytes', $measured)) {
            $megabytes = $measured['file_size_bytes'] === null ? null : $measured['file_size_bytes'] / 1048576;
            $rows[] = $this->row('file_size', null, (float) $target['max_file_size_mb'], $megabytes, null);
        }

        return $rows;
    }

    /** "Meets all Target checks": every row passes. Not a score (BENCHMARK.md §6.1). */
    public static function meetsAll(array $rows): bool
    {
        return $rows !== [] && collect($rows)->every(fn ($row) => $row['outcome'] === 'pass');
    }

    private function row(string $key, ?string $motion, float $limit, ?float $value, ?string $label): array
    {
        return [
            'check_key' => $key,
            'motion' => $motion,
            'limit_value' => $limit,
            // Compare the exact value; only the stored copy is rounded.
            'measured_value' => $value === null ? null : round($value, 3),
            'label' => $label,
            'outcome' => $value === null ? 'not_measured' : ($value <= $limit ? 'pass' : 'fail'),
        ];
    }
}

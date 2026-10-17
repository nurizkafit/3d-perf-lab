<?php

namespace App\Support;

/**
 * Estimated run time (BENCHMARK.md §4.1 step 1) and the run expiry that follows from it (§5, decision D59).
 * resources/js/features/run-estimate.js uses the same rule on the configuration page.
 */
class RunEstimate
{
    /** Cold load, warm load, and renderer set-up per measured asset, in seconds (an allowance, not a measurement). */
    public const LOAD_ALLOWANCE_S = 4;

    /** Physics warm-up lasts at least this long (BENCHMARK.md §4.2). */
    public const PHYSICS_MIN_WARMUP_S = 2;

    /** Cool-down between measured assets by device category (BENCHMARK.md §4.1 step 4). */
    public static function coolDownSeconds(?string $deviceCategory): int
    {
        return in_array($deviceCategory, ['phone', 'tablet'], true) ? 10 : 3;
    }

    /** @param  array{motions?: list<string>, repetitions?: int, warmup_s?: float|int, measurement_s?: float|int}  $configuration */
    public static function sideBySideSeconds(array $configuration, int $assetCount, ?string $deviceCategory): int
    {
        $warmup = (float) ($configuration['warmup_s'] ?? 2);
        $window = (float) ($configuration['measurement_s'] ?? 10);
        $perMotion = collect($configuration['motions'] ?? [])
            ->sum(fn ($motion) => ($motion === 'physics' ? max($warmup, self::PHYSICS_MIN_WARMUP_S) : $warmup) + $window);
        $perAsset = self::LOAD_ALLOWANCE_S + $perMotion + self::coolDownSeconds($deviceCategory);

        return (int) ceil(($configuration['repetitions'] ?? 3) * $assetCount * $perAsset);
    }

    /** Probe build and auto-frame allowance in seconds (Crowd). */
    public const PROBE_BUILD_S = 0.5;

    /** Binary-search probes after the doubling phase, for the typical estimate (BENCHMARK.md §4.6 step 3). */
    public const TYPICAL_BINARY_PROBES = 4;

    /**
     * Crowd estimate (decision D62): typical = every search doubles up to the cap and then needs a few binary-search
     * probes; maximum = every search runs to the 3-minute time limit. Each search is clipped to the time limit.
     *
     * @param  array{motions?: list<string>, repetitions?: int, cap?: int, time_limit_s?: int}  $configuration
     * @return array{typical: int, max: int}
     */
    public static function crowdSeconds(array $configuration, int $groupCount, int $objectsPerGroup, ?string $deviceCategory): array
    {
        $capCopies = max(1, intdiv((int) ($configuration['cap'] ?? 1024), max(1, $objectsPerGroup)));
        $doublings = 0;
        while ((1 << $doublings) < $capCopies) {
            $doublings++;
        }
        $probes = $doublings + 1 + self::TYPICAL_BINARY_PROBES;
        $limit = (int) ($configuration['time_limit_s'] ?? 180);
        $typical = 0;
        $max = 0;
        foreach ($configuration['motions'] ?? [] as $motion) {
            $probe = ($motion === 'physics' ? 2 : 1) + 4 + self::PROBE_BUILD_S;
            $typical += min($limit, $probes * $probe);
            $max += $limit + $probe; // the probe that crosses the limit still finishes
        }
        $perGroup = self::LOAD_ALLOWANCE_S + self::coolDownSeconds($deviceCategory) * max(1, count($configuration['motions'] ?? []));
        $runs = ($configuration['repetitions'] ?? 1) * $groupCount;

        return ['typical' => (int) ceil($runs * ($typical + $perGroup)), 'max' => (int) ceil($runs * ($max + $perGroup))];
    }

    /**
     * Minutes after start when a running run expires: the configured minimum, or the estimate plus 15 minutes
     * when that is longer (decision D59).
     */
    public static function expiryMinutes(int $estimateSeconds): int
    {
        return max((int) config('lab.run_expiry_minutes'), (int) ceil($estimateSeconds / 60) + 15);
    }
}

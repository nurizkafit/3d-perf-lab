<?php

namespace Tests\Unit;

use App\Support\BestMarkers;
use App\Support\RunEstimate;
use PHPUnit\Framework\TestCase;

/** Best markers (BENCHMARK.md §4.5) and the run estimate (§4.1, decision D59). */
class BestMarkersTest extends TestCase
{
    private function spread(float $median, float $min, float $max): array
    {
        return ['median' => $median, 'min' => $min, 'max' => $max, 'n' => 3];
    }

    public function test_the_best_item_wins_only_when_its_spread_does_not_overlap_any_other(): void
    {
        $this->assertSame(['winners' => ['a'], 'too_close' => false], BestMarkers::lowestSpread([
            'a' => $this->spread(1.0, 0.9, 1.1), 'b' => $this->spread(2.0, 1.2, 2.5), 'c' => $this->spread(3.0, 2.9, 3.1),
        ]));
    }

    public function test_overlapping_spreads_are_too_close_to_call(): void
    {
        $this->assertSame(['winners' => [], 'too_close' => true], BestMarkers::lowestSpread([
            'a' => $this->spread(1.0, 0.9, 1.3), 'b' => $this->spread(2.0, 1.3, 2.5),
        ]), 'Touching spreads overlap');
        $this->assertSame(['winners' => [], 'too_close' => true], BestMarkers::lowestSpread([
            'a' => $this->spread(1.0, 1.0, 1.0), 'b' => $this->spread(1.0, 1.0, 1.0),
        ]), 'Equal single values are too close to call');
    }

    public function test_a_marker_needs_two_measured_items(): void
    {
        $this->assertSame(['winners' => [], 'too_close' => false], BestMarkers::lowestSpread(['a' => $this->spread(1, 1, 1), 'b' => null]));
        $this->assertSame(['winners' => [], 'too_close' => false], BestMarkers::lowestStatic(['a' => 5]));
    }

    public function test_static_values_share_the_marker_when_equal(): void
    {
        $this->assertSame(['winners' => ['a', 'c'], 'too_close' => false], BestMarkers::lowestStatic(['a' => 100, 'b' => 200, 'c' => 100]));
        $this->assertSame(['winners' => ['b'], 'too_close' => false], BestMarkers::lowestStatic(['a' => 100, 'b' => 50, 'c' => null]));
    }

    public function test_the_estimate_counts_loads_motions_physics_warm_up_and_cool_down(): void
    {
        // 3 × 2 × (4 + (1 + 10) + (1 + 10) + (2 + 10) + 3) = 6 × 41
        $this->assertSame(246, RunEstimate::sideBySideSeconds(['motions' => ['static', 'spin', 'physics'], 'repetitions' => 3, 'warmup_s' => 1, 'measurement_s' => 10], 2, 'desktop'));
        // Phone: 10 s cool-down
        $this->assertSame(1 * 2 * (4 + 12 + 10), RunEstimate::sideBySideSeconds(['motions' => ['static'], 'repetitions' => 1, 'warmup_s' => 2, 'measurement_s' => 10], 2, 'phone'));
    }

    public function test_most_copies_is_the_highest_and_equal_counts_are_too_close(): void
    {
        $one = fn (int $n) => ['median' => $n, 'min' => $n, 'max' => $n, 'n' => 1];
        $this->assertSame(['winners' => ['b'], 'too_close' => false], BestMarkers::highestSpread(['a' => $one(6), 'b' => $one(64)]));
        $this->assertSame(['winners' => [], 'too_close' => true], BestMarkers::highestSpread(['a' => $one(64), 'b' => $one(64)]), 'Two results at the cap');
        $this->assertSame(['winners' => [], 'too_close' => true], BestMarkers::highestSpread(['a' => $this->spread(50, 40, 60), 'b' => $this->spread(55, 58, 70)]));
    }
}

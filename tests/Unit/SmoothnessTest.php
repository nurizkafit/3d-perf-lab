<?php

namespace Tests\Unit;

use App\Support\Smoothness;
use PHPUnit\Framework\TestCase;

/** Smoothness label rule (BENCHMARK.md §4.4). Same edge cases as tests/js/smoothness.test.mjs. */
class SmoothnessTest extends TestCase
{
    private const GOAL_60 = 1000 / 60;

    public function test_smooth_up_to_one_and_a_quarter_goal_frame_time_with_at_most_one_percent_stutter(): void
    {
        $this->assertSame('smooth', Smoothness::label(1.25 * self::GOAL_60, 1.0, 60));
        $this->assertSame('smooth', Smoothness::label(16.9, 0.0, 60), 'A 60 Hz display with timer jitter is Smooth');
    }

    public function test_more_than_one_percent_stutter_is_not_smooth(): void
    {
        $this->assertSame('mostly_smooth', Smoothness::label(16.7, 1.01, 60));
    }

    public function test_mostly_smooth_up_to_twice_the_goal_frame_time(): void
    {
        $this->assertSame('mostly_smooth', Smoothness::label(1.25 * self::GOAL_60 + 0.001, 0.0, 60));
        $this->assertSame('mostly_smooth', Smoothness::label(2 * self::GOAL_60, 30.0, 60));
    }

    public function test_choppy_above_twice_the_goal_frame_time(): void
    {
        $this->assertSame('choppy', Smoothness::label(2 * self::GOAL_60 + 0.001, 0.0, 60));
    }

    public function test_the_goal_frame_time_follows_the_target_frame_rate(): void
    {
        $this->assertSame('smooth', Smoothness::label(40.0, 0.0, 30));
        $this->assertSame('choppy', Smoothness::label(40.0, 0.0, 60));
    }

    public function test_missing_values_give_no_label(): void
    {
        $this->assertNull(Smoothness::label(null, 0.0, 60));
        $this->assertNull(Smoothness::label(16.0, null, 60));
    }
}

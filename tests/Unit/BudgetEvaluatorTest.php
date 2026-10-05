<?php

namespace Tests\Unit;

use App\Services\BudgetEvaluator;
use App\Support\Aggregate;
use App\Support\TestResultPresenter;
use PHPUnit\Framework\TestCase;

class BudgetEvaluatorTest extends TestCase
{
    private function evaluate(array $items, array $measured, string $method = 'comparison'): array
    {
        return collect((new BudgetEvaluator)->evaluate($items, $method, $measured))->keyBy('item_key')->all();
    }

    public function test_pass_when_the_value_is_inside_the_limit(): void
    {
        $rows = $this->evaluate(['max_p95_frame_ms' => ['enabled' => true, 'limit' => 16.7]], ['interval_p95_ms' => 16.7]);

        $this->assertSame('pass', $rows['max_p95_frame_ms']['outcome']);
        $this->assertSame(16.7, $rows['max_p95_frame_ms']['measured_value']);
    }

    public function test_fail_when_the_value_is_outside_the_limit(): void
    {
        $rows = $this->evaluate(['max_ttff_cold_ms' => ['enabled' => true, 'limit' => 3000]], ['ttff_cold_ms' => 3000.5]);

        $this->assertSame('fail', $rows['max_ttff_cold_ms']['outcome']);
    }

    public function test_minimum_items_pass_at_or_above_the_limit(): void
    {
        $items = ['min_headroom_instances' => ['enabled' => true, 'limit' => 20]];

        $this->assertSame('pass', $this->evaluate($items, ['headroom_instances' => 20], 'headroom')['min_headroom_instances']['outcome']);
        $this->assertSame('fail', $this->evaluate($items, ['headroom_instances' => 19], 'headroom')['min_headroom_instances']['outcome']);
    }

    public function test_not_measured_when_the_browser_gave_no_value(): void
    {
        $rows = $this->evaluate(['max_jank_pct' => ['enabled' => true, 'limit' => 1]], ['jank_pct' => null]);

        $this->assertSame('not_measured', $rows['max_jank_pct']['outcome']);
        $this->assertNull($rows['max_jank_pct']['measured_value']);
    }

    public function test_disabled_items_produce_no_row(): void
    {
        $rows = $this->evaluate([
            'max_draw_calls' => ['enabled' => false, 'limit' => 100],
            'max_jank_pct' => ['enabled' => true, 'limit' => null],
        ], ['draw_calls' => 5000, 'jank_pct' => 50]);

        $this->assertSame([], $rows);
    }

    public function test_items_that_do_not_apply_to_the_method_produce_no_row(): void
    {
        $rows = $this->evaluate([
            'max_ttff_cold_ms' => ['enabled' => true, 'limit' => 3000],
            'max_p95_frame_ms' => ['enabled' => true, 'limit' => 16.7],
        ], ['ttff_cold_ms' => 100, 'interval_p95_ms' => 10], 'headroom');

        $this->assertSame(['max_p95_frame_ms'], array_keys($rows));
    }

    public function test_bytes_are_converted_to_megabytes(): void
    {
        $rows = $this->evaluate([
            'max_file_size_mb' => ['enabled' => true, 'limit' => 8],
            'max_texture_memory_mb' => ['enabled' => true, 'limit' => 1],
        ], ['file_size_bytes' => 8 * 1048576 + 1, 'texture_memory_estimate_bytes' => 524288]);

        $this->assertSame('fail', $rows['max_file_size_mb']['outcome']);
        $this->assertSame(0.5, $rows['max_texture_memory_mb']['measured_value']);
        $this->assertSame('pass', $rows['max_texture_memory_mb']['outcome']);
    }

    public function test_meets_all_needs_every_row_to_pass(): void
    {
        $this->assertTrue(BudgetEvaluator::meetsAll([['outcome' => 'pass'], ['outcome' => 'pass']]));
        $this->assertFalse(BudgetEvaluator::meetsAll([['outcome' => 'pass'], ['outcome' => 'not_measured']]));
        $this->assertFalse(BudgetEvaluator::meetsAll([]));
    }

    public function test_median_and_spread_of_repetitions(): void
    {
        $this->assertSame(['median' => 17.0, 'min' => 16.0, 'max' => 30.0, 'n' => 3], Aggregate::summary([30.0, 16.0, 17.0]));
        $this->assertSame(16.5, Aggregate::summary([16, 17])['median']);
        $this->assertNull(Aggregate::summary([null, null]));
        $this->assertSame(2, Aggregate::summary([null, 4, 2])['n']);
    }

    public function test_unstable_when_the_p95_spread_exceeds_a_quarter_of_the_median(): void
    {
        $this->assertTrue(Aggregate::unstable(Aggregate::summary([16, 17, 22])));
        $this->assertFalse(Aggregate::unstable(Aggregate::summary([16, 17, 18])));
    }

    public function test_number_formatting_keeps_integer_zeros_and_small_values(): void
    {
        $this->assertSame('5,800', TestResultPresenter::number(5800, 0));
        $this->assertSame('10,064,960', TestResultPresenter::number(10064960, 0));
        $this->assertSame('16.5', TestResultPresenter::number(16.50, 1));
        $this->assertSame('3', TestResultPresenter::number(3.0, 2));
        $this->assertSame('0.01', TestResultPresenter::measured(0.01));
        $this->assertSame('63.1', TestResultPresenter::measured(63.1));
    }
}

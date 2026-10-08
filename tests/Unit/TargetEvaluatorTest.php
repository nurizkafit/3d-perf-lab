<?php

namespace Tests\Unit;

use App\Services\TargetEvaluator;
use PHPUnit\Framework\TestCase;

/** Target checks (BENCHMARK.md §6.1): Pass, Fail, Not measured; one smooth check per motion scenario. */
class TargetEvaluatorTest extends TestCase
{
    private const DESKTOP = ['max_load_s' => 3, 'target_fps' => 60, 'max_file_size_mb' => 20];

    private function rows(array $measured, array $target = self::DESKTOP): array
    {
        return collect((new TargetEvaluator)->evaluate($target, $measured))->keyBy(fn ($row) => $row['check_key'].($row['motion'] ? ".{$row['motion']}" : ''))->all();
    }

    public function test_load_passes_at_the_limit_and_fails_above_it(): void
    {
        $this->assertSame('pass', $this->rows(['ttff_cold_ms' => 3000])['load']['outcome']);
        $fail = $this->rows(['ttff_cold_ms' => 3000.5])['load'];
        $this->assertSame(['fail', 3.0, 3.001], [$fail['outcome'], $fail['limit_value'], $fail['measured_value']]);
    }

    public function test_file_size_is_compared_in_megabytes(): void
    {
        $this->assertSame('pass', $this->rows(['file_size_bytes' => 20 * 1048576])['file_size']['outcome']);
        $this->assertSame('fail', $this->rows(['file_size_bytes' => 20 * 1048576 + 1])['file_size']['outcome']);
    }

    public function test_each_motion_scenario_gets_a_smooth_check_with_its_label(): void
    {
        $rows = $this->rows(['motions' => [
            'static' => ['interval_p95_ms' => 16.7, 'jank_pct' => 0],
            'spin' => ['interval_p95_ms' => 25, 'jank_pct' => 0],
            'physics' => ['interval_p95_ms' => 50, 'jank_pct' => 12],
        ]]);

        $this->assertSame(['smooth', 'pass'], [$rows['smooth.static']['label'], $rows['smooth.static']['outcome']]);
        $this->assertSame(['mostly_smooth', 'fail'], [$rows['smooth.spin']['label'], $rows['smooth.spin']['outcome']]);
        $this->assertSame(['choppy', 'fail'], [$rows['smooth.physics']['label'], $rows['smooth.physics']['outcome']]);
        $this->assertSame(60.0, $rows['smooth.static']['limit_value']);
        $this->assertSame(20.0, $rows['smooth.physics']['measured_value'], 'FPS-equivalent of the p95 frame interval');
    }

    public function test_missing_values_are_not_measured(): void
    {
        $rows = $this->rows(['ttff_cold_ms' => null, 'file_size_bytes' => null, 'motions' => ['static' => ['interval_p95_ms' => null, 'jank_pct' => null]]]);

        $this->assertSame(['not_measured', 'not_measured', 'not_measured'], [$rows['load']['outcome'], $rows['file_size']['outcome'], $rows['smooth.static']['outcome']]);
        $this->assertNull($rows['smooth.static']['label']);
    }

    public function test_checks_without_input_produce_no_row(): void
    {
        $this->assertSame([], (new TargetEvaluator)->evaluate(self::DESKTOP, []));
    }

    public function test_meets_all_needs_every_row_to_pass(): void
    {
        $pass = (new TargetEvaluator)->evaluate(self::DESKTOP, ['ttff_cold_ms' => 1000, 'file_size_bytes' => 1000, 'motions' => ['static' => ['interval_p95_ms' => 16.7, 'jank_pct' => 0]]]);
        $notMeasured = (new TargetEvaluator)->evaluate(self::DESKTOP, ['ttff_cold_ms' => 1000, 'file_size_bytes' => null]);

        $this->assertTrue(TargetEvaluator::meetsAll($pass));
        $this->assertFalse(TargetEvaluator::meetsAll($notMeasured));
        $this->assertFalse(TargetEvaluator::meetsAll([]));
    }
}

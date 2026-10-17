<?php

namespace App\Support;

use App\Models\TestRun;
use App\Models\TestSession;
use App\Services\TargetEvaluator;

/** One-line result summary of a test for the history and the dashboard (PRD.md §10.1). Not a score. */
class TestSummary
{
    public static function line(TestSession $test, ?TestRun $completed): string
    {
        if ($completed === null) {
            return '—';
        }
        if ($test->method === 'crowd') {
            $motion = $test->configuration_json['motions'][0] ?? 'static';
            $best = $completed->results->whereNull('probe_copies')->where('motion', $motion)->max(fn ($row) => $row->metrics_json['smooth_copies'] ?? 0);

            return 'Up to '.number_format((int) $best).' Smooth copies ('.MetricDefinitions::MOTIONS[$motion]['label'].')';
        }
        $byAsset = $completed->checks->groupBy('asset_id');
        $meeting = $byAsset->filter(fn ($checks) => TargetEvaluator::meetsAll($checks->map->only(['outcome'])->all()))->count();

        return "{$meeting} of {$byAsset->count()} assets meet all Target checks";
    }
}

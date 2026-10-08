<?php

namespace App\Support;

use App\Models\Target;
use Illuminate\Http\Request;

/**
 * Data for components/performance/target-step: active Targets with their values, and the suggested Target
 * (linked to the confirmed device class, else the default) — BENCHMARK.md §6.2.
 */
class TargetStep
{
    public static function data(Request $request): array
    {
        $targets = Target::where('is_active', true)->orderBy('sort_order')->orderBy('id')->with('deviceClasses:id')->get();
        $deviceClassId = $request->session()->get('device_class_id');
        $linked = $deviceClassId ? $targets->first(fn (Target $t) => $t->deviceClasses->contains('id', $deviceClassId)) : null;
        $suggested = $linked ?? $targets->firstWhere('is_default', true) ?? $targets->first();

        return [
            'fields' => MetricDefinitions::TARGET_FIELDS,
            'targets' => $targets->map(fn (Target $t) => $t->snapshot())->values(),
            'suggestedId' => $suggested?->id,
            'suggestedForDevice' => $linked !== null,
        ];
    }
}

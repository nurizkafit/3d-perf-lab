<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use App\Models\BudgetPreset;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Tool;
use App\Support\MetricDefinitions;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Performance Lab configuration pages (DESIGN.md §9.2). */
class LabController extends Controller
{
    public function comparison(Request $request): View
    {
        return $this->configure($request, 'comparison');
    }

    public function stress(Request $request): View
    {
        return $this->configure($request, 'stress');
    }

    /** One configuration page for every method; method-specific steps are switched in the view. */
    private function configure(Request $request, string $method): View
    {
        $presets = OptionPreset::where('is_active', true)->whereIn('type', ['resolution', 'camera', 'lighting', 'background', 'instance_steps'])
            ->orderBy('sort_order')->get()->groupBy('type')->toBase();
        $deviceClassId = $request->session()->get('device_class_id');
        $stepPresets = ($presets['instance_steps'] ?? collect())->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'steps' => $p->values_json['steps'] ?? []])->values();

        return view('pages.lab.configure', [
            'method' => $method,
            'presets' => $presets->except('instance_steps')->map(fn ($group) => [
                'options' => $group->pluck('name', 'id')->all(),
                'default' => $group->firstWhere('is_default', true)?->id ?? $group->first()?->id,
            ]),
            'stepPresets' => $stepPresets,
            'tools' => Tool::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all(),
            'budget' => self::budgetStepData($request, $method),
            'config' => [
                'method' => $method,
                'maxAssets' => 8,
                'maxInstances' => 500,
                'stepPresets' => $stepPresets,
                'defaultSteps' => ($presets['instance_steps'] ?? collect())->firstWhere('is_default', true)?->values_json['steps'] ?? [1, 5, 10, 25, 50],
                'deviceClass' => $deviceClassId ? DeviceClass::find($deviceClassId)?->name : null,
                'endpoints' => [
                    'tests' => route('api.tests.store'),
                    'runs' => route('api.tests.runs.store', '__TEST__'),
                ],
            ],
        ]);
    }

    /**
     * Data for components/performance/budget-step: active presets with their items and device classes,
     * the items that apply to the method, and the suggested preset (linked to the confirmed device class,
     * else the default) — BENCHMARK.md §6.2.
     */
    public static function budgetStepData(Request $request, string $method): array
    {
        $presets = BudgetPreset::where('is_active', true)->orderBy('sort_order')->with('deviceClasses:id')->get();
        $deviceClassId = $request->session()->get('device_class_id');
        $suggested = $presets->first(fn ($p) => $deviceClassId && $p->deviceClasses->contains('id', $deviceClassId))
            ?? $presets->firstWhere('is_default', true)
            ?? $presets->first();

        return [
            'method' => $method,
            'items' => MetricDefinitions::budgetItemsFor($method),
            'presets' => $presets->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'items' => $p->items_json])->values(),
            'suggestedId' => $suggested?->id,
            'suggestedForDevice' => $deviceClassId && $suggested?->deviceClasses->contains('id', $deviceClassId),
        ];
    }
}

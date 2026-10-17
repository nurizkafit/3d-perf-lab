<?php

namespace App\Support;

use App\Models\AssetObject;
use App\Models\DeviceClass;
use App\Models\OptionPreset;
use App\Models\Tool;
use Illuminate\Http\Request;

/** View data shared by the Side-by-Side and Crowd configuration pages (DESIGN.md §9.2). */
class LabPage
{
    public static function data(Request $request): array
    {
        $presets = OptionPreset::where('is_active', true)->whereIn('type', ['resolution', 'camera', 'lighting', 'background'])
            ->orderBy('sort_order')->orderBy('id')->get()->groupBy('type')->toBase();
        $default = fn (string $type) => ($presets[$type] ?? collect())->firstWhere('is_default', true) ?? ($presets[$type] ?? collect())->first();
        $deviceClass = ($id = $request->session()->get('device_class_id')) ? DeviceClass::find($id) : null;

        return [
            'presets' => $presets->map(fn ($group) => $group->pluck('name', 'id')->all()),
            'defaults' => collect(['resolution', 'camera', 'lighting', 'background'])->mapWithKeys(fn ($type) => [$type => $default($type)?->id])->all(),
            'tools' => Tool::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all(),
            'objects' => AssetObject::where('is_active', true)->orderBy('sort_order')->orderBy('name')->pluck('name', 'id')->all(),
            'target' => TargetStep::data($request),
            'config' => [
                'deviceClass' => $deviceClass?->name,
                'coolDownS' => RunEstimate::coolDownSeconds($deviceClass?->category),
                'loadAllowanceS' => RunEstimate::LOAD_ALLOWANCE_S,
                'physicsMinWarmupS' => RunEstimate::PHYSICS_MIN_WARMUP_S,
                'probeBuildS' => RunEstimate::PROBE_BUILD_S,
                'typicalBinaryProbes' => RunEstimate::TYPICAL_BINARY_PROBES,
                // The look phase uses the selected presets (values per preset id).
                'presetValues' => $presets->flatten()->mapWithKeys(fn ($p) => [$p->id => $p->values_json])->all(),
                'endpoints' => [
                    'tests' => route('api.tests.store'),
                    'runs' => route('api.tests.runs.store', '__TEST__'),
                    'commonObjects' => route('api.objects.common'),
                ],
            ],
        ];
    }
}

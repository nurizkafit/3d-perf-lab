<?php

namespace App\Http\Requests\Lab;

use App\Models\Asset;
use App\Models\TestSession;
use App\Models\Tool;
use App\Support\MetricDefinitions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Test configuration (BENCHMARK.md §4.3 Side-by-Side, §4.6 Crowd). */
class CreateTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TestSession::class);
    }

    private function isCrowd(): bool
    {
        return $this->input('method') === 'crowd';
    }

    private function isPerTool(): bool
    {
        return $this->isCrowd() && $this->input('scenario') === 'per_tool';
    }

    public function rules(): array
    {
        $preset = fn (string $type) => ['nullable', 'integer', Rule::exists('option_presets', 'id')->where('type', $type)->where('is_active', true)];
        $field = fn (string $key) => ['required', 'numeric', 'between:'.MetricDefinitions::TARGET_FIELDS[$key]['min'].','.MetricDefinitions::TARGET_FIELDS[$key]['max']];
        $liveAsset = Rule::exists('assets', 'id')->whereNull('deleted_at');

        return [
            'name' => ['required', 'string', 'max:150'],
            'method' => ['required', Rule::in(['side_by_side', 'crowd'])],
            'scenario' => [Rule::requiredIf($this->isCrowd()), 'nullable', Rule::in(['per_asset', 'per_tool'])],
            // Side-by-Side: 2–4 assets; Crowd Per asset: 1–8 assets (BENCHMARK.md §4.3, §4.6).
            'asset_ids' => [Rule::requiredIf(! $this->isPerTool()), 'array', $this->isCrowd() ? 'min:1' : 'min:2', $this->isCrowd() ? 'max:8' : 'max:4'],
            'asset_ids.*' => ['integer', 'distinct', $liveAsset],
            // Crowd Per tool: two or more tools and up to 30 objects that exist in every tool (D48).
            'tool_ids' => [Rule::requiredIf($this->isPerTool()), 'array', 'min:2', 'max:10'],
            'tool_ids.*' => ['integer', 'distinct', Rule::exists('tools', 'id')],
            'objects' => [Rule::requiredIf($this->isPerTool()), 'array', 'min:1', 'max:30'],
            'objects.*.object_id' => ['required', 'integer', 'distinct', Rule::exists('objects', 'id')],
            'objects.*.assets' => ['required', 'array'],
            'objects.*.assets.*' => ['integer', $liveAsset],
            'configuration' => ['required', 'array'],
            // At least one motion scenario stays selected (BENCHMARK.md §4.2, decision D51).
            'configuration.motions' => ['required', 'array', 'min:1', 'max:3'],
            'configuration.motions.*' => ['string', 'distinct', Rule::in(array_keys(MetricDefinitions::MOTIONS))],
            // Crowd: repetitions 1–3, default 1 (decision D62).
            'configuration.repetitions' => ['required', 'integer', $this->isCrowd() ? 'between:1,3' : 'between:1,5'],
            'configuration.warmup_s' => [Rule::requiredIf(! $this->isCrowd()), 'nullable', 'integer', 'between:1,10'],
            'configuration.measurement_s' => [Rule::requiredIf(! $this->isCrowd()), 'nullable', 'integer', 'between:5,30'],
            'configuration.cap' => [Rule::requiredIf($this->isCrowd()), 'nullable', 'integer', 'between:1,4096'],
            'configuration.animation' => ['boolean'],
            'configuration.resolution_id' => $preset('resolution'),
            'configuration.camera_id' => $preset('camera'),
            'configuration.lighting_id' => $preset('lighting'),
            'configuration.background_id' => $preset('background'),
            'target' => ['required', 'array'],
            'target.target_id' => ['required', 'integer', Rule::exists('targets', 'id')->where('is_active', true)],
            'target.max_load_s' => $field('max_load_s'),
            'target.target_fps' => ['required', 'integer', 'between:'.MetricDefinitions::TARGET_FIELDS['target_fps']['min'].','.MetricDefinitions::TARGET_FIELDS['target_fps']['max']],
            'target.max_file_size_mb' => $field('max_file_size_mb'),
        ];
    }

    public function messages(): array
    {
        $range = $this->isCrowd() ? 'A Crowd Test Per asset uses 1 to 8 assets.' : 'A Side-by-Side Test compares 2 to 4 assets.';

        return [
            'asset_ids.min' => $range,
            'asset_ids.max' => $range,
            'tool_ids.min' => 'Select at least two authoring tools.',
            'objects.max' => 'A Crowd Test uses at most 30 objects.',
            'configuration.motions.required' => 'Select at least one motion scenario.',
        ];
    }

    /**
     * Per tool: every chosen object must exist in every selected tool, with one chosen asset per tool that has that
     * tool and that object. A forced request with a missing object is rejected (PROJECT.md M4.4).
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->isPerTool() || $validator->errors()->isNotEmpty()) {
                return;
            }
            $toolIds = array_map('intval', $this->input('tool_ids'));
            $toolNames = Tool::whereIn('id', $toolIds)->pluck('name', 'id');
            $assetIds = collect($this->input('objects'))->flatMap(fn ($object) => array_values($object['assets']))->all();
            $assets = Asset::whereIn('id', $assetIds)->get()->keyBy('id');

            foreach ($this->input('objects') as $index => $object) {
                foreach ($toolIds as $toolId) {
                    $asset = $assets->get((int) ($object['assets'][$toolId] ?? 0));
                    if ($asset === null) {
                        $validator->errors()->add("objects.{$index}", "This object is missing in {$toolNames[$toolId]}. Only objects that exist in every selected tool can be compared.");
                    } elseif ($asset->tool_id !== $toolId || $asset->object_id !== (int) $object['object_id']) {
                        $validator->errors()->add("objects.{$index}", "The chosen {$toolNames[$toolId]} asset does not match this object and tool.");
                    }
                }
                if (array_diff(array_map('intval', array_keys($object['assets'])), $toolIds) !== []) {
                    $validator->errors()->add("objects.{$index}", 'An asset is chosen for a tool that is not selected.');
                }
            }
        }];
    }
}

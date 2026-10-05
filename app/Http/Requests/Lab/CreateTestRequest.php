<?php

namespace App\Http\Requests\Lab;

use App\Models\TestSession;
use App\Support\MetricDefinitions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Shared test configuration (BENCHMARK.md §4). Method-specific settings are added in M4.2–M4.4. */
class CreateTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TestSession::class);
    }

    public function rules(): array
    {
        $preset = fn (string $type) => ['nullable', 'integer', Rule::exists('option_presets', 'id')->where('type', $type)->where('is_active', true)];

        return [
            'name' => ['required', 'string', 'max:150'],
            'method' => ['required', Rule::in(['comparison', 'stress', 'headroom'])],
            'scenario' => [Rule::requiredIf(fn () => $this->input('method') !== 'comparison'), 'nullable', Rule::in(['repeated', 'mixed'])],
            'asset_ids' => ['required', 'array', 'min:1', 'max:8'],
            'asset_ids.*' => ['integer', 'distinct', Rule::exists('assets', 'id')->whereNull('deleted_at')],
            'configuration' => ['required', 'array'],
            'configuration.repetitions' => ['required', 'integer', 'between:1,5'],
            'configuration.warmup_s' => ['required', 'numeric', 'between:1,10'],
            'configuration.measurement_s' => ['required', 'numeric', 'between:5,30'],
            'configuration.animation' => ['boolean'],
            'configuration.resolution_id' => $preset('resolution'),
            'configuration.camera_id' => $preset('camera'),
            'configuration.lighting_id' => $preset('lighting'),
            'configuration.background_id' => $preset('background'),
            'configuration.instance_steps_id' => $preset('instance_steps'),
            // Stress: the editable step list (BENCHMARK.md §4.3), ascending, at most 500 instances.
            'configuration.steps' => [Rule::requiredIf(fn () => $this->input('method') === 'stress'), 'nullable', 'array', 'min:1', 'max:12'],
            'configuration.steps.*' => ['integer', 'between:1,500', 'distinct'],
            'budget' => ['required', 'array'],
            'budget.preset_id' => ['required', 'integer', Rule::exists('budget_presets', 'id')->where('is_active', true)],
            'budget.items' => ['array'],
            'budget.items.*.enabled' => ['required', 'boolean'],
            'budget.items.*.limit' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            $steps = $this->input('configuration.steps');
            if (is_array($steps) && $steps !== [] && array_values($steps) !== collect($steps)->sort()->values()->all()) {
                $validator->errors()->add('configuration.steps', 'List the instance counts in ascending order.');
            }
            // Stress scenarios (BENCHMARK.md §4.3): repeated = one asset; mixed = 2–8 assets.
            $count = count((array) $this->input('asset_ids', []));
            if ($this->input('method') === 'stress' && $this->input('scenario') === 'repeated' && $count !== 1) {
                $validator->errors()->add('asset_ids', 'A repeated-asset Stress Test uses exactly one asset.');
            }
            if ($this->input('scenario') === 'mixed' && $count < 2) {
                $validator->errors()->add('asset_ids', 'A mixed scenario needs at least two assets.');
            }
            foreach (array_keys($this->input('budget.items', [])) as $key) {
                if (! array_key_exists($key, MetricDefinitions::BUDGET_ITEMS)) {
                    $validator->errors()->add("budget.items.{$key}", 'Unknown budget item.');
                }
            }
        }];
    }
}

<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Result payload of a run (BENCHMARK.md §3, §4.3, §4.6, §5). Only the run creator, only while the run is running and
 * not expired. Side-by-Side: each asset and repetition has one load row (motion null) and one row per motion scenario.
 * Crowd: each group (asset, or tool for Per tool), motion scenario, and repetition has one search row and 1–64 probes.
 */
class SubmitResultsRequest extends FormRequest
{
    public const SUMMARY_KEYS = [
        'frames', 'interval_p50_ms', 'interval_p95_ms', 'interval_p99_ms', 'fps_avg', 'fps_1pct_low',
        'jank_count', 'jank_pct', 'work_p50_ms', 'work_p95_ms', 'physics_p50_ms', 'physics_p95_ms', 'gpu_p50_ms', 'gpu_p95_ms',
        'draw_calls', 'rendered_triangles', 'programs', 'geometries', 'textures_in_memory', 'js_heap_used',
    ];

    public const LOAD_KEYS = ['transfer_size', 'fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms', 'ttff_ms'];

    /** Maximum raw samples per window (BENCHMARK.md §3.3). */
    public const MAX_SAMPLES = 3600;

    public function authorize(): bool
    {
        return $this->route('run')->created_by === $this->user()->id;
    }

    public function rules(): array
    {
        $rules = [
            'environment' => ['array'],
            'results' => ['required', 'array', 'min:1', 'max:3000'],
            'results.*.asset_id' => ['nullable', 'integer'],
            'results.*.group_tool_id' => ['nullable', 'integer'],
            'results.*.probe_copies' => ['nullable', 'integer', 'min:1', 'max:4096'],
            'results.*.search' => ['nullable', 'array'],
            'results.*.search.cap_reached' => ['boolean'],
            'results.*.search.time_limit_reached' => ['boolean'],
            'results.*.motion' => ['nullable', 'string'],
            'results.*.repetition' => ['required', 'integer', 'min:1'],
            'results.*.metrics' => ['nullable', 'array'],
            'results.*.metrics.frames' => ['nullable', 'integer', 'min:1'],
            'results.*.load' => ['nullable', 'array'],
            'results.*.collider' => ['nullable', 'array'],
            'results.*.collider.points' => ['nullable', 'integer', 'min:0'],
            'results.*.collider.faces' => ['nullable', 'integer', 'min:0'],
            'results.*.collider.collider_build_ms' => ['nullable', 'numeric', 'min:0'],
            'results.*.samples' => ['nullable', 'array'],
            'results.*.samples.truncated' => ['boolean'],
        ];
        foreach (self::SUMMARY_KEYS as $key) {
            $rules["results.*.metrics.{$key}"] ??= ['nullable', 'numeric', 'min:0'];
        }
        foreach (['cold', 'warm'] as $phase) {
            foreach (self::LOAD_KEYS as $key) {
                $rules["results.*.load.{$phase}.{$key}"] = ['nullable', 'numeric', 'min:0'];
            }
        }
        foreach (['interval', 'work', 'physics', 'gpu'] as $series) {
            $rules["results.*.samples.{$series}"] = ['nullable', 'array', 'max:'.self::MAX_SAMPLES];
            $rules["results.*.samples.{$series}.*"] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $run = $this->route('run');
            if ($run->status !== 'running') {
                $validator->errors()->add('run', "This run is {$run->status}; results can no longer be submitted.");

                return;
            }
            if ($run->isExpired()) {
                $validator->errors()->add('run', 'This run expired. Start a new run.');

                return;
            }
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $test = $run->session;
            $test->method === 'crowd' ? $this->checkCrowd($validator, $test) : $this->checkSideBySide($validator, $test);
        }];
    }

    /** The payload must match the configuration exactly: every asset × repetition × (load + each motion), once. */
    private function checkSideBySide(Validator $validator, $test): void
    {
        $configuration = $test->configuration_json;
        $expected = [];
        foreach ($test->assets()->pluck('assets.id') as $assetId) {
            for ($repetition = 1; $repetition <= $configuration['repetitions']; $repetition++) {
                foreach (['load', ...$configuration['motions']] as $motion) {
                    $expected["{$assetId}:{$repetition}:{$motion}"] = true;
                }
            }
        }
        $seen = [];
        foreach ($this->input('results') as $index => $result) {
            $key = ($result['asset_id'] ?? 'none').":{$result['repetition']}:".($result['motion'] ?? 'load');
            if (! isset($expected[$key])) {
                $validator->errors()->add("results.{$index}", 'This result does not belong to the test configuration (asset, repetition, or motion scenario).');
            } elseif (isset($seen[$key])) {
                $validator->errors()->add("results.{$index}", 'This result is a duplicate.');
            } elseif (($result['motion'] ?? null) === null && empty($result['load']['cold'])) {
                $validator->errors()->add("results.{$index}.load", 'A load result needs the cold load metrics.');
            } elseif (($result['motion'] ?? null) !== null && empty($result['metrics']['frames'])) {
                $validator->errors()->add("results.{$index}.metrics.frames", 'A measurement result needs its frame count.');
            }
            $seen[$key] = true;
        }
        if ($validator->errors()->isEmpty() && count($seen) !== count($expected)) {
            $validator->errors()->add('results', 'The results are incomplete: every asset needs a load result and a result per motion scenario in every repetition.');
        }
    }

    /**
     * Crowd: every group × motion × repetition has exactly one search row and 1–64 probes. A group is an asset
     * (Per asset) or a tool (Per tool). Probe counts stay within the cap in copies.
     */
    private function checkCrowd(Validator $validator, $test): void
    {
        $configuration = $test->configuration_json;
        $perTool = $test->scenario === 'per_tool';
        $groups = $perTool ? $configuration['tool_ids'] : $test->assets()->pluck('assets.id')->all();
        [, $objects] = $test->crowdShape();
        $capCopies = max(1, intdiv($configuration['cap'], max(1, $objects)));
        $searches = [];
        $probes = [];
        foreach ($groups as $group) {
            foreach ($configuration['motions'] as $motion) {
                for ($repetition = 1; $repetition <= $configuration['repetitions']; $repetition++) {
                    $searches["{$group}:{$motion}:{$repetition}"] = 0;
                    $probes["{$group}:{$motion}:{$repetition}"] = 0;
                }
            }
        }
        foreach ($this->input('results') as $index => $result) {
            $group = $perTool ? ($result['group_tool_id'] ?? null) : ($result['asset_id'] ?? null);
            $key = "{$group}:".($result['motion'] ?? '').":{$result['repetition']}";
            if (! isset($searches[$key]) || ($perTool && ($result['asset_id'] ?? null) !== null)) {
                $validator->errors()->add("results.{$index}", 'This result does not belong to the test configuration (group, motion scenario, or repetition).');

                continue;
            }
            if (isset($result['search'])) {
                $searches[$key]++;
            } elseif (($result['probe_copies'] ?? null) !== null && ($result['metrics']['frames'] ?? null) !== null) {
                $probes[$key]++;
                if ($result['probe_copies'] > $capCopies) {
                    $validator->errors()->add("results.{$index}.probe_copies", "A probe exceeds the cap ({$capCopies} copies).");
                }
            } else {
                $validator->errors()->add("results.{$index}", 'A Crowd result is either a probe (copies and metrics) or a search summary.');
            }
        }
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        foreach ($searches as $key => $count) {
            if ($count !== 1 || $probes[$key] < 1 || $probes[$key] > 64) {
                $validator->errors()->add('results', 'The results are incomplete: every group needs one search with 1 to 64 probes per motion scenario and repetition.');

                return;
            }
        }
    }

    /** Validated payload with only the known metric keys. */
    public function payload(): array
    {
        $data = $this->validated();
        foreach ($data['results'] as &$result) {
            $result['metrics'] = array_intersect_key($result['metrics'] ?? [], array_flip(self::SUMMARY_KEYS));
        }

        return $data;
    }
}

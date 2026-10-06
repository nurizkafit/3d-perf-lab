<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Result payload schema (BENCHMARK.md §3, §5). Only the run creator, only while running and not expired. */
class SubmitResultsRequest extends FormRequest
{
    public const SUMMARY_KEYS = [
        'frames', 'interval_p50_ms', 'interval_p95_ms', 'interval_p99_ms', 'fps_avg', 'fps_1pct_low',
        'jank_count', 'jank_pct', 'work_p50_ms', 'work_p95_ms', 'gpu_p50_ms', 'gpu_p95_ms',
        'draw_calls', 'rendered_triangles', 'programs', 'geometries', 'textures_in_memory', 'js_heap_used',
        'refresh_hz_estimate', 'headroom_instances',
    ];

    public const LOAD_KEYS = ['transfer_size', 'fetch_ms', 'parse_ms', 'gpu_upload_ms', 'first_frame_ms', 'ttff_ms'];

    /** Maximum raw samples per window (BENCHMARK.md §3.3). */
    public const MAX_SAMPLES = 3600;

    public function authorize(): bool
    {
        $run = $this->route('run');

        return $run->created_by === $this->user()->id;
    }

    public function rules(): array
    {
        $rules = [
            'environment' => ['array'],
            'results' => ['required', 'array', 'min:1', 'max:2000'],
            'results.*.asset_id' => ['nullable', 'integer'],
            'results.*.step_instances' => ['nullable', 'integer', 'between:1,4096'],
            'results.*.repetition' => ['required', 'integer', 'min:1'],
            'results.*.metrics' => ['required', 'array'],
            'results.*.metrics.frames' => ['required', 'integer', 'min:1'],
            'results.*.load' => ['nullable', 'array'],
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
        // Headroom (BENCHMARK.md §4.4): the probe sequence and the stop flags of each search.
        $rules['results.*.probes'] = ['nullable', 'array', 'max:64'];
        $rules['results.*.probes.*.instances'] = ['required', 'integer', 'between:1,4096'];
        $rules['results.*.probes.*.pass'] = ['required', 'boolean'];
        $rules['results.*.probes.*.interval_p95_ms'] = ['nullable', 'numeric', 'min:0'];
        $rules['results.*.probes.*.jank_pct'] = ['nullable', 'numeric', 'min:0'];
        $rules['results.*.flags'] = ['nullable', 'array'];
        $rules['results.*.flags.cap_reached'] = ['boolean'];
        $rules['results.*.flags.time_limit_reached'] = ['boolean'];
        foreach (['interval', 'work', 'gpu'] as $series) {
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
            } elseif ($run->isExpired()) {
                $validator->errors()->add('run', 'This run expired. Start a new run.');
            }
        }];
    }

    /** Validated payload with only the known metric keys. */
    public function payload(): array
    {
        $data = $this->validated();
        foreach ($data['results'] as &$result) {
            $result['metrics'] = array_intersect_key($result['metrics'], array_flip(self::SUMMARY_KEYS));
        }

        return $data;
    }
}

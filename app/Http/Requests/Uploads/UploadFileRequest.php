<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

/** One model file (plus GLTF dependents), with the browser-derived metrics and thumbnail. */
class UploadFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('session');

        return $this->user()->can('update', $session) && $session->status === 'open';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('metrics'))) {
            $this->merge(['metrics' => json_decode($this->input('metrics'), true)]);
        }
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'dependents' => ['array', 'max:19'],
            'dependents.*' => ['file'],
            'metrics' => ['nullable', 'array'],
            'metrics.vertices' => ['required_with:metrics', 'integer', 'min:0'],
            'metrics.triangles' => ['required_with:metrics', 'integer', 'min:0'],
            'metrics.meshes' => ['required_with:metrics', 'integer', 'min:0'],
            'metrics.materials' => ['required_with:metrics', 'integer', 'min:0'],
            'metrics.textures' => ['required_with:metrics', 'integer', 'min:0'],
            'metrics.texture_dimensions' => ['array', 'max:256'],
            'metrics.texture_dimensions.*' => ['array', 'size:2'],
            'metrics.texture_dimensions.*.*' => ['integer', 'min:0', 'max:65536'],
            'metrics.texture_memory_estimate' => ['integer', 'min:0'],
            'thumbnail' => ['nullable', 'file', 'mimetypes:image/webp,image/jpeg', 'max:512', 'dimensions:max_width=1024,max_height=1024'],
        ];
    }

    /** Metrics that fail validation are dropped instead of failing the upload (they are optional). */
    public function metrics(): ?array
    {
        return $this->validated('metrics');
    }
}

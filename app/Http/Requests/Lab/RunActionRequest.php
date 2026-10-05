<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;

/** Invalidate or cancel a run: only its creator, only while it is running. */
class RunActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $run = $this->route('run');

        return $run->created_by === $this->user()->id && $run->status === 'running';
    }

    public function rules(): array
    {
        return $this->routeIs('api.runs.invalidate') ? ['reason' => ['required', 'string', 'max:250']] : [];
    }
}

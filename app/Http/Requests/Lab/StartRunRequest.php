<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Start a run on this device. The device class is the one confirmed on /device (BENCHMARK.md §7.1). */
class StartRunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('test'));
    }

    public function rules(): array
    {
        return ['environment' => ['required', 'array']];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->session()->get('device_class_id')) {
                $validator->errors()->add('device_class', 'Confirm the device class of this device first.');
            }
        }];
    }
}

<?php

namespace App\Http\Requests\Lab;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any signed-in, active user (route middleware)
    }

    public function rules(): array
    {
        return [
            'device_class_id' => ['required', 'integer', Rule::exists('device_classes', 'id')->where('is_active', true)],
            // Only paths inside this app; never an external URL.
            'return_to' => ['nullable', 'string', 'max:500', 'regex:#^/(?!/)#'],
        ];
    }

    public function messages(): array
    {
        return ['device_class_id.required' => 'Choose the class of this device.'];
    }
}

<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

/** Rows keyed by upload item id. Per-row rules are applied in AssetRegistrationService. */
class RegisterUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('session'));
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*' => ['array'],
        ];
    }
}

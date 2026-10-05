<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

class RestoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('restore', $this->route('asset'));
    }

    public function rules(): array
    {
        return [];
    }
}

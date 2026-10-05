<?php

namespace App\Http\Requests\Assets;

use Illuminate\Foundation\Http\FormRequest;

class ForceDeleteAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('forceDelete', $this->route('asset'));
    }

    public function rules(): array
    {
        return [];
    }
}

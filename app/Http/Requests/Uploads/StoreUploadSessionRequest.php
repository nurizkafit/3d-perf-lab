<?php

namespace App\Http\Requests\Uploads;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;

class StoreUploadSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Asset::class);
    }

    public function rules(): array
    {
        return [];
    }
}

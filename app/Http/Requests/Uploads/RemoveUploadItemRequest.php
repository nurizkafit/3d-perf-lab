<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

class RemoveUploadItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $this->user()->can('update', $item->session) && in_array($item->status, ['staged', 'invalid'], true);
    }

    public function rules(): array
    {
        return [];
    }
}

<?php

namespace App\Http\Requests\Uploads;

use Illuminate\Foundation\Http\FormRequest;

/** Saves the row's metadata while the user types, so a session can be resumed. Validation happens at register. */
class UpdateUploadItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $this->user()->can('update', $item->session) && $item->status === 'staged';
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:150'],
            'author' => ['nullable', 'string', 'max:100'],
            'tool_id' => ['nullable', 'integer'],
            'production_time_hours' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}

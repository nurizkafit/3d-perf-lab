<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/** Deactivate, activate, set default, reorder, or delete an option-list item. */
class OptionActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-option-lists');
    }

    public function rules(): array
    {
        return $this->routeIs('admin.options.reorder')
            ? ['direction' => ['required', 'in:up,down']]
            : [];
    }
}

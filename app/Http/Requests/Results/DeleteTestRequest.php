<?php

namespace App\Http\Requests\Results;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Delete a test (SITEMAP.md §3.4): owner or admin; blocked while its report is published. */
class DeleteTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', $this->route('test'));
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->route('test')->report?->isPublished()) {
                $validator->errors()->add('test', 'Unpublish the report first. A test with a published report cannot be deleted.');
            }
        }];
    }
}

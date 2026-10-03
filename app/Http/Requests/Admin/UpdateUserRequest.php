<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', Rule::in(['admin', 'user'])],
        ];
    }

    /** At least one active admin must remain (SITEMAP.md §3.5). */
    public function after(): array
    {
        return [function (Validator $validator) {
            $target = $this->route('user');
            if ($target->isAdmin() && $target->is_active && $this->input('role') === 'user' && $target->otherActiveAdminCount() === 0) {
                $validator->errors()->add('role', 'This is the last active admin. Make another account an admin first.');
            }
        }];
    }
}

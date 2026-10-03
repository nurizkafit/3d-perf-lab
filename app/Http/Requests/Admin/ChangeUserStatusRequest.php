<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Deactivate or activate an account. Admins cannot deactivate themselves (UserPolicy). */
class ChangeUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->routeIs('admin.users.deactivate') ? 'deactivate' : 'activate';

        return $this->user()->can($ability, $this->route('user'));
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $target = $this->route('user');
            if ($this->routeIs('admin.users.deactivate') && $target->isAdmin() && $target->otherActiveAdminCount() === 0) {
                $validator->errors()->add('user', 'This is the last active admin, so it cannot be deactivated.');
            }
        }];
    }
}

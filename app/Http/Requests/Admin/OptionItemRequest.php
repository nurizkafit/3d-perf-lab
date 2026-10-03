<?php

namespace App\Http\Requests\Admin;

use App\Support\OptionLists;
use Illuminate\Foundation\Http\FormRequest;

/** Create or edit one option-list item. Rules depend on the list (OptionLists::rules). */
class OptionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-option-lists');
    }

    public function rules(): array
    {
        $list = $this->route('list');
        $item = $this->route('item') ? OptionLists::find($list, (int) $this->route('item')) : null;

        return OptionLists::rules($list, $item);
    }

    public function attributes(): array
    {
        return OptionLists::attributes($this->route('list'));
    }
}

<?php

namespace App\Http\Requests\Assets;

use App\Models\Asset;
use App\Models\Tool;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Metadata only; the model file is never replaced (PRD.md §7.4). */
class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('asset'));
    }

    public function rules(): array
    {
        $asset = $this->route('asset');

        return [
            'name' => ['required', 'string', 'max:150'],
            // An inactive tool stays valid for the asset that already uses it.
            'tool_id' => ['required', 'integer', Rule::exists(Tool::class, 'id')->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $asset->tool_id))],
            'author' => [$this->user()->can('setAuthor', Asset::class) ? 'required' : 'nullable', 'string', 'max:100'],
            'production_time_hours' => ['required', 'numeric', 'min:0', 'max:10000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['tool_id' => 'tool', 'production_time_hours' => 'production time'];
    }

    /** Users never change Author: the submitted value is ignored (PRD.md §4). */
    public function assetAttributes(): array
    {
        $data = $this->validated();
        if (! $this->user()->can('setAuthor', Asset::class)) {
            unset($data['author']);
        }

        return $data;
    }
}

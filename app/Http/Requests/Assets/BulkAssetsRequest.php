<?php

namespace App\Http\Requests\Assets;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

/** Selected assets for a bulk action. Per-asset permission is checked in AssetDeletionService. */
class BulkAssetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Asset::class);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return ['ids.required' => 'Select at least one asset.'];
    }

    /** @return Collection<int, Asset> */
    public function assets(bool $withTrashed = false): Collection
    {
        return Asset::query()->when($withTrashed, fn ($q) => $q->withTrashed())->whereIn('id', $this->validated('ids'))->get();
    }
}

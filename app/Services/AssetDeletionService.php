<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use App\Support\AssetFiles;
use Illuminate\Support\Facades\DB;

/**
 * Soft and permanent deletion of assets, one or many (PRD.md §7.4). Every asset is checked on its own:
 * soft delete needs the owner or an admin; permanent delete needs an admin and no test using the asset.
 */
class AssetDeletionService
{
    /** @param iterable<Asset> $assets */
    public function softDelete(User $user, iterable $assets): array
    {
        $result = ['deleted' => 0, 'not_allowed' => 0];
        foreach ($assets as $asset) {
            if ($asset->trashed()) {
                continue;
            }
            if (! $user->can('delete', $asset)) {
                $result['not_allowed']++;

                continue;
            }
            $asset->delete();
            $result['deleted']++;
        }

        return $result;
    }

    /** @param iterable<Asset> $assets */
    public function forceDelete(User $user, iterable $assets): array
    {
        $result = ['deleted' => 0, 'used' => 0, 'not_allowed' => 0, 'names' => []];
        foreach ($assets as $asset) {
            if (! $user->can('forceDelete', $asset)) {
                $result['not_allowed']++;

                continue;
            }
            if ($asset->testUsageCount() > 0) {
                $result['used']++;

                continue;
            }
            $this->forceDeleteOne($asset);
            $result['deleted']++;
            $result['names'][] = $asset->name;
        }

        return $result;
    }

    /** Removes the record, its metrics, the upload-item link, and the files. Caller checks test usage. */
    private function forceDeleteOne(Asset $asset): void
    {
        DB::transaction(function () use ($asset) {
            DB::table('upload_items')->where('asset_id', $asset->id)->update(['asset_id' => null]);
            $asset->metrics()->delete();
            $asset->forceDelete();
        });
        AssetFiles::delete($asset);
    }
}

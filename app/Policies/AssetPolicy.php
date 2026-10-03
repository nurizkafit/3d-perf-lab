<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;

/** PRD.md §4. Every signed-in active user can browse, upload, download, and test with assets. */
class AssetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Asset $asset): bool
    {
        return true;
    }

    public function download(User $user, Asset $asset): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Only an admin may set or change Author; for users the system sets it. */
    public function setAuthor(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Asset $asset): bool
    {
        return $user->isAdmin() || $user->owns($asset->uploaded_by);
    }

    public function delete(User $user, Asset $asset): bool
    {
        return $user->isAdmin() || $user->owns($asset->uploaded_by);
    }

    public function restore(User $user, Asset $asset): bool
    {
        return $user->isAdmin() || $user->owns($asset->uploaded_by);
    }

    /** Admin only. Blocking when a published report uses the asset is checked in M3.3. */
    public function forceDelete(User $user, Asset $asset): bool
    {
        return $user->isAdmin();
    }
}

<?php

namespace App\Policies;

use App\Models\User;

/** PRD.md §4: account management is admin only. An admin cannot deactivate their own account. */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    public function resetPassword(User $user, User $target): bool
    {
        return $user->isAdmin();
    }

    /** The "at least one active admin" rule is enforced in M2.2. */
    public function deactivate(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->id !== $target->id;
    }

    public function activate(User $user, User $target): bool
    {
        return $user->isAdmin();
    }
}

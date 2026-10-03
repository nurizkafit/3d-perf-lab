<?php

namespace App\Policies;

use App\Models\TestSession;
use App\Models\User;

/** PRD.md §4: users see and manage their own tests; admins see all. Any user can create and run tests. */
class TestSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TestSession $test): bool
    {
        return $user->isAdmin() || $user->owns($test->created_by);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TestSession $test): bool
    {
        return $this->view($user, $test);
    }

    public function delete(User $user, TestSession $test): bool
    {
        return $this->view($user, $test);
    }

    public function export(User $user, TestSession $test): bool
    {
        return $this->view($user, $test);
    }
}

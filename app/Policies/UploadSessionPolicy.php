<?php

namespace App\Policies;

use App\Models\UploadSession;
use App\Models\User;

/** SITEMAP.md §3.2: an upload session and its staged files belong to the account that created it. */
class UploadSessionPolicy
{
    public function view(User $user, UploadSession $session): bool
    {
        return $user->owns($session->created_by);
    }

    public function update(User $user, UploadSession $session): bool
    {
        return $user->owns($session->created_by);
    }
}

<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/** PRD.md §4: owners manage, publish, and unpublish their own reports; admins any report. */
class ReportPolicy
{
    public function view(User $user, Report $report): bool
    {
        return $user->isAdmin() || $user->owns($report->created_by);
    }

    public function update(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    public function publish(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    public function unpublish(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }
}

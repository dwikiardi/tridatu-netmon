<?php

namespace App\Policies;

use App\Models\ReportFilter;
use App\Models\User;

class ReportFilterPolicy
{
    /**
     * Determine whether the user can delete the report filter.
     */
    public function delete(User $user, ReportFilter $reportFilter): bool
    {
        return $user->id === $reportFilter->user_id;
    }

    /**
     * Determine whether the user can update the report filter.
     */
    public function update(User $user, ReportFilter $reportFilter): bool
    {
        return $user->id === $reportFilter->user_id;
    }
}

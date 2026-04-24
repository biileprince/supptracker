<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any activities.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the activity.
     */
    public function view(User $user, Activity $activity): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create activities.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the activity.
     */
    public function update(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the activity.
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }
}

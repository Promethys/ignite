<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;

class MilestonePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Milestone $milestone): bool
    {
        return $user->can('view', $milestone->goal);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Goal $goal): bool
    {
        return $user->can('update', $goal);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Milestone $milestone): bool
    {
        return $user->can('update', $milestone->goal);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Milestone $milestone): bool
    {
        return $user->can('update', $milestone->goal)
            || $user->can('delete', $milestone->goal);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Milestone $milestone): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Milestone $milestone): bool
    {
        return false;
    }
}

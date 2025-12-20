<?php

namespace App\Policies;

use App\Models\GoalEntry;
use App\Models\User;

class GoalEntryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GoalEntry $goalEntry): bool
    {
        return $user->can('view', $goalEntry->goal);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GoalEntry $goalEntry): bool
    {
        return $user->can('update', $goalEntry->goal);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GoalEntry $goalEntry): bool
    {
        return $user->can('delete', $goalEntry->goal);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GoalEntry $goalEntry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GoalEntry $goalEntry): bool
    {
        return false;
    }
}

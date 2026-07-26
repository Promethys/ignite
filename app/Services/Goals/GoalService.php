<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

/**
 * Goal read and write operations. The acting user is always passed
 * explicitly; this service never calls `auth()`. Authorization is enforced
 * via `Gate::forUser($actor)`, keeping the policy as the single authority.
 */
class GoalService
{
    /**
     * Return the actor's goals with the `streak` appended.
     *
     * Mirrors the data shape of `GoalController::index`, minus the Inertia
     * response.
     *
     * @return Collection<int, Goal>
     */
    public function listForUser(User $actor): Collection
    {
        return $actor->goals()
            ->get()
            ->append('streak');
    }

    /**
     * Load a single goal for the actor, eager-loading its recent entries,
     * ordered milestones, and appended `streak`.
     *
     * Authorizes `view` via `Gate::forUser($actor)`. A missing id throws a
     * `ModelNotFoundException` (404); a foreign id the actor may not view
     * throws an `AuthorizationException` (403).
     *
     * Accepts either a primary key (used by MCP tools, which receive a
     * `goal_id`) or an already-resolved model (used by route-model-bound
     * controllers, avoiding a redundant lookup).
     */
    public function find(User $actor, Goal|int $goal): Goal
    {
        $goal = $goal instanceof Goal ? $goal : Goal::findOrFail($goal);

        Gate::forUser($actor)->authorize('view', $goal);

        return $goal->load([
            'entries' => fn ($query) => $query->orderBy('entry_date', 'desc')->take(20),
            'milestones' => fn ($query) => $query->orderBy('order', 'asc'),
        ])->append('streak');
    }

    /**
     * Create a goal owned by the actor.
     *
     * The caller runs validation (the controller's `$rules`, or the MCP
     * tool's schema). The service owns the invariants: it authorizes
     * `create`, assigns the owner from the actor (never trusting a
     * client-supplied `user_id`), and computes the display `order` as the
     * owner's goal count plus one.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $actor, array $attributes): Goal
    {
        Gate::forUser($actor)->authorize('create', Goal::class);

        $order = $actor->goals()->count() + 1;

        return Goal::create([
            ...$attributes,
            'user_id' => $actor->id,
            'order' => $order,
        ]);
    }

    /**
     * Update a goal with the given (already-validated) attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $actor, Goal $goal, array $attributes): Goal
    {
        Gate::forUser($actor)->authorize('update', $goal);

        $goal->update($attributes);

        return $goal;
    }

    /**
     * Mark a goal as completed (sets `status` and `completed_at`).
     */
    public function complete(User $actor, Goal $goal): Goal
    {
        Gate::forUser($actor)->authorize('update', $goal);

        $goal->markAsCompleted();

        return $goal;
    }

    /**
     * Revert a completed goal back to the given status, clearing
     * `completed_at`. Runs without events so the observer cannot re-complete
     * the goal mid-revert.
     */
    public function uncomplete(User $actor, Goal $goal, string $status): Goal
    {
        Gate::forUser($actor)->authorize('update', $goal);

        Goal::withoutEvents(function () use ($goal, $status): void {
            $goal->update([
                'status' => $status,
                'completed_at' => null,
            ]);
        });

        return $goal;
    }

    /**
     * Set a goal's status.
     */
    public function setStatus(User $actor, Goal $goal, string $status): Goal
    {
        Gate::forUser($actor)->authorize('update', $goal);

        $goal->updateStatus($status);

        return $goal;
    }

    /**
     * Delete a goal. Consumed by the controller now and by the Phase 3
     * delete MCP tool.
     */
    public function delete(User $actor, Goal $goal): void
    {
        Gate::forUser($actor)->authorize('delete', $goal);

        $goal->delete();
    }
}

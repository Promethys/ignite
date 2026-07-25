<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

/**
 * Goal read operations. The acting user is always passed explicitly; this
 * service never calls `auth()`. Authorization is enforced via
 * `Gate::forUser($actor)`, keeping the policy as the single authority.
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
}

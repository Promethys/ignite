<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Goal read and write operations. The acting user is always passed
 * explicitly; this service never calls `auth()`. Authorization is enforced
 * via `Gate::forUser($actor)`, keeping the policy as the single authority.
 */
class GoalService
{
    /**
     * Return the actor's goals, with filters, milestone counts, and streak.
     *
     * Milestone counts feed `GoalResource::milestone_summary` so multi_step
     * progress is visible in the lean list without loading the relation.
     *
     * Never silently truncates: returns the total matching the filters
     * alongside the (capped) slice, so a caller knows whether more exist.
     * A null `limit` means every match was returned.
     *
     * @param  array<string, mixed>  $filters
     * @return array{goals: Collection<int, Goal>, total: int, limit: int|null}
     */
    public function listForUser(User $actor, array $filters = []): array
    {
        $query = $actor->goals();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['search'])) {
            $term = '%'.strtolower($filters['search']).'%';

            $query->where(function ($query) use ($term): void {
                $query->whereRaw('LOWER(title) like ?', [$term])
                    ->orWhereRaw('LOWER(description) like ?', [$term]);
            });
        }

        $total = $query->count();

        $query->withCount([
            'milestones',
            'milestones as completed_milestones_count' => fn ($query) => $query->whereNotNull('completed_at'),
        ]);

        $limit = isset($filters['limit'])
            ? min(max((int) $filters['limit'], 1), 100)
            : null;

        if ($limit !== null) {
            $query->limit($limit);
        }

        return [
            'goals' => $query->get()->append('streak'),
            'total' => $total,
            'limit' => $limit,
        ];
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
     * Create a goal owned by the actor. Operational fields default server-side.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(User $actor, array $attributes): Goal
    {
        Gate::forUser($actor)->authorize('create', Goal::class);

        $attributes['current_value'] ??= 0;
        $attributes['direction'] ??= 'ascending';
        $attributes['status'] ??= 'not_started';
        $attributes['priority'] ??= 'medium';
        $attributes['polarity'] ??= 'positive';
        $attributes['points'] ??= 0;
        $attributes['is_public'] ??= false;

        $order = $actor->goals()->count() + 1;

        $goal = Goal::create([
            ...$attributes,
            'user_id' => $actor->id,
            'order' => $order,
        ]);

        return $goal->load('category');
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
     *
     * Rejects a goal that was never completed, so this cannot be used as a
     * back door onto the status transitions `setStatus` owns.
     */
    public function uncomplete(User $actor, Goal $goal, string $status): Goal
    {
        Gate::forUser($actor)->authorize('update', $goal);

        if ($goal->status !== 'completed' && $goal->completed_at === null) {
            throw ValidationException::withMessages([
                'goal' => __('validation.custom.goal.uncomplete_not_completed'),
            ]);
        }

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
    public function delete(User $actor, Goal $goal): ?bool
    {
        Gate::forUser($actor)->authorize('delete', $goal);

        return $goal->delete();
    }
}

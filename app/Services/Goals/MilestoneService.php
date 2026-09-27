<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MilestoneService
{
    public function find(User $actor, Milestone|int $milestone): Milestone
    {
        $milestone = $milestone instanceof Milestone ? $milestone : Milestone::findOrFail($milestone);

        Gate::forUser($actor)->authorize('view', $milestone);

        return $milestone;
    }

    /**
     * Append a milestone, or insert it at a 1-based position on a multi-step goal.
     */
    public function add(User $actor, Goal $goal, array $attributes, ?int $position = null): Milestone
    {
        Gate::forUser($actor)->authorize('create', [Milestone::class, $goal]);

        return DB::transaction(function () use ($goal, $attributes, $position) {
            $milestone = $goal->milestones()->create([
                ...$attributes,
                'order' => $goal->milestones()->max('order') + 1,
            ]);

            if ($goal->type === 'multi_step' && $position !== null) {
                $others = $goal->milestones()->whereKeyNot($milestone->id)->get();
                $index = min($position, $others->count() + 1) - 1;

                $others->splice($index, 0, [$milestone]);

                $this->writeOrder($others);
            } elseif ($goal->type === 'quantifiable') {
                $this->resequence($goal);
            }

            return $milestone->refresh();
        });
    }

    public function update(User $actor, Milestone $milestone, array $attributes): Milestone
    {
        Gate::forUser($actor)->authorize('update', $milestone);

        $milestone->update($attributes);

        if ($milestone->wasChanged('target_value') && $milestone->goal->type === 'quantifiable') {
            $this->resequence($milestone->goal);
        }

        return $milestone;
    }

    /**
     * Put a multi-step goal's steps in the given order. The ids must be all of the goal's steps.
     *
     * @param  array<int, int>  $milestoneIds
     */
    public function reorder(User $actor, Goal $goal, array $milestoneIds): void
    {
        Gate::forUser($actor)->authorize('update', $goal);

        DB::transaction(function () use ($goal, $milestoneIds) {
            $milestones = $goal->milestones()->get()->keyBy('id');

            $this->writeOrder(collect($milestoneIds)->map(fn (int $id) => $milestones[$id]));
        });
    }

    public function complete(User $actor, Milestone $milestone): Milestone
    {
        Gate::forUser($actor)->authorize('update', $milestone);

        $milestone->markAsCompleted();

        return $milestone;
    }

    /**
     * Number a goal's milestones from 1. Quantifiable milestones follow their target value.
     */
    public function resequence(Goal $goal): void
    {
        $milestones = $goal->milestones()->get();

        if ($goal->type === 'quantifiable') {
            $milestones = $this->sortByTarget($milestones, $goal->direction);
        }

        $this->writeOrder($milestones);
    }

    /**
     * @param  Collection<int, Milestone>  $milestones
     * @return Collection<int, Milestone>
     */
    private function sortByTarget(Collection $milestones, string $direction): Collection
    {
        $withTarget = $milestones->whereNotNull('target_value');

        $sorted = $direction === 'descending'
            ? $withTarget->sortByDesc(fn (Milestone $milestone) => (float) $milestone->target_value)
            : $withTarget->sortBy(fn (Milestone $milestone) => (float) $milestone->target_value);

        return $sorted->concat($milestones->whereNull('target_value'))->values();
    }

    /**
     * @param  Collection<int, Milestone>  $milestones
     */
    private function writeOrder(Collection $milestones): void
    {
        $milestones->values()->each(function (Milestone $milestone, int $index) {
            if ($milestone->order !== $index + 1) {
                $milestone->update(['order' => $index + 1]);
            }
        });
    }
}

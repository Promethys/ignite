<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MilestoneService
{
    public function add(User $actor, Goal $goal, array $attributes): Milestone
    {
        Gate::forUser($actor)->authorize('create', [Milestone::class, $goal]);

        $order = $goal->milestones()->max('order') + 1;

        $milestone = $goal->milestones()->create([
            ...$attributes,
            'order' => $order,
        ]);

        return $milestone;
    }

    public function complete(User $actor, Milestone $milestone): Milestone
    {
        Gate::forUser($actor)->authorize('update', $milestone);

        $milestone->markAsCompleted();

        return $milestone;
    }
}

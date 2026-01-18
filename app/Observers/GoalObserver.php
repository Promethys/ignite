<?php

namespace App\Observers;

use App\Models\Goal;

class GoalObserver
{
    /**
     * Handle the Goal "created" event.
     */
    public function creating(Goal $goal): void
    {
        if ($goal->current_value !== null) {
            $goal->initial_value = $goal->current_value;
        }

        if (
            $goal->status !== 'completed'
            && $goal->target_value
            && (
                ($goal->direction === 'descending' && $goal->current_value <= $goal->target_value) 
                || ($goal->direction === 'ascending' && $goal->current_value >= $goal->target_value)
            )
        ) {
            $this->markAsCompleted($goal);
        }
    }

    /**
     * Handle the Goal "created" event.
     */
    public function created(Goal $goal): void
    {
        //
    }

    /**
     * Handle the Goal "updated" event.
     */
    public function updating(Goal $goal): void
    {
        if (
            $goal->status !== 'completed'
            && $goal->target_value
            && (
                ($goal->direction === 'descending' && $goal->current_value <= $goal->target_value) 
                || ($goal->direction === 'ascending' && $goal->current_value >= $goal->target_value)
            )
        ) {
            $this->markAsCompleted($goal);
        }
    }

    /**
     * Handle the Goal "updated" event.
     */
    public function updated(Goal $goal): void
    {
        //
    }

    /**
     * Handle the Goal "deleted" event.
     */
    public function deleted(Goal $goal): void
    {
        //
    }

    /**
     * Handle the Goal "restored" event.
     */
    public function restored(Goal $goal): void
    {
        //
    }

    /**
     * Handle the Goal "force deleted" event.
     */
    public function forceDeleted(Goal $goal): void
    {
        //
    }

    protected function markAsCompleted(Goal $goal): void
    {
        $goal->status = 'completed';
        $goal->completed_at = now();
    }
}

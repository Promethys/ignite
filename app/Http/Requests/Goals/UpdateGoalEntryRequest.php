<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Rules\GoalEntryRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A recurring goal is tracked by dated check-ins, every other type by a
 * numeric increment, so the goal decides which rule set applies.
 */
class UpdateGoalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->entry());
    }

    /**
     * The goal in the URL must be the one that owns the entry, and that has to
     * settle before the rules are read, since the goal decides which rules apply.
     */
    protected function prepareForValidation(): void
    {
        if ($this->goal()->id !== $this->entry()->goal_id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->goal()->type === 'recurring'
            ? GoalEntryRules::checkInRules($this->goal())
            : GoalEntryRules::progressRules($this->goal());
    }

    protected function goal(): Goal
    {
        return $this->route('goal');
    }

    protected function entry(): GoalEntry
    {
        return $this->route('goalEntry');
    }
}

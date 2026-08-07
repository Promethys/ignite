<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;
use App\Rules\GoalEntryRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A recurring goal is tracked by dated check-ins, every other type by a
 * numeric increment, so the goal decides which rule set applies.
 */
class StoreGoalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->goal());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->goal()->type === 'recurring'
            ? GoalEntryRules::checkInRules($this->goal())
            : GoalEntryRules::progressRules();
    }

    protected function goal(): Goal
    {
        return $this->route('goal');
    }
}

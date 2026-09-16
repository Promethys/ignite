<?php

namespace App\Rules;

use App\Models\User;
use App\Traits\Rules\HandlesPartialRules;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Milestone validation rules shared by the web controller and the MCP tools.
 */
class MilestoneRules
{
    use HandlesPartialRules;

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'target_value' => 'nullable|numeric',
            'description' => 'nullable|string',
            // 'deadline' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'points_reward' => 'nullable|numeric',
            'order' => 'nullable|integer',
        ];
    }

    public static function ownerIdRule(User $user): Exists
    {
        return Rule::exists('milestones', 'id')->where(
            fn (Builder $query) => $query->whereIn(
                'goal_id',
                fn (Builder $goals) => $goals->select('id')->from('goals')->where('user_id', $user->id),
            ),
        );
    }
}

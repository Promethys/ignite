<?php

namespace App\Rules;

use App\Models\Goal;
use App\Models\User;
use App\Traits\Rules\HandlesPartialRules;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Milestone validation rules shared by the web controller and the MCP tools.
 *
 * Pass the goal so a deadline must fall within the goal's own dates.
 */
class MilestoneRules
{
    use HandlesPartialRules;

    /**
     * @return array<string, mixed>
     */
    public static function rules(?Goal $goal = null): array
    {
        return [
            'title' => 'required|string|max:255',
            'target_value' => 'nullable|numeric',
            'description' => 'nullable|string',
            'deadline' => self::deadlineRules(
                $goal?->start_date?->toDateString(),
                $goal?->deadline?->toDateString(),
            ),
            'completed_at' => 'nullable|date',
            'points_reward' => 'nullable|numeric',
        ];
    }

    /**
     * Each bound is a date or the name of another field under validation.
     *
     * @return array<int, string>
     */
    public static function deadlineRules(?string $notBefore, ?string $notAfter): array
    {
        return array_values(array_filter([
            'nullable',
            'date',
            $notBefore ? 'after_or_equal:'.$notBefore : null,
            $notAfter ? 'before_or_equal:'.$notAfter : null,
        ]));
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

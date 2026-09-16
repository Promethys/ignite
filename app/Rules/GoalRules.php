<?php

namespace App\Rules;

use App\Models\User;
use App\Traits\Rules\HandlesPartialRules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Goal validation rules shared by the web controller and the MCP tools.
 *
 * Pass the acting user so `category_id` only accepts categories they own and
 * the completion date bound resolves in their timezone.
 */
class GoalRules
{
    use HandlesPartialRules;

    /**
     * @return array<string, mixed>
     */
    public static function rules(?User $user = null): array
    {
        return [
            'category_id' => [
                'nullable',
                $user === null
                    ? Rule::exists('categories', 'id')
                    : Rule::exists('categories', 'id')->where('user_id', $user->id),
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'type' => 'required|in:simple,quantifiable,recurring,multi_step',
            'direction' => 'required|in:ascending,descending',
            'target_value' => 'nullable|numeric',
            'current_value' => 'required|numeric',
            'unit' => 'nullable|string|max:50',
            'recurrence' => 'nullable|in:daily,weekly,monthly,annually',
            'start_date' => 'nullable|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'completed_at' => 'nullable|date|after_or_equal:start_date|before_or_equal:'.self::todayForUser($user),
            'status' => 'required|in:not_started,in_progress,completed,paused,abandoned',
            'priority' => 'required|in:low,medium,high',
            'polarity' => 'nullable|in:positive,negative',
            'points' => 'required|integer|min:0',
            'is_public' => 'required|boolean',
            'order' => 'nullable|integer',
        ];
    }

    public static function ownerIdRule(User $user): Exists
    {
        return Rule::exists('goals', 'id')->where('user_id', $user->id);
    }

    protected static function todayForUser(?User $user): string
    {
        return GoalEntryRules::todayForTimezone($user?->timezone);
    }
}

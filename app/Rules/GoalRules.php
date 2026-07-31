<?php

namespace App\Rules;

use App\Traits\Rules\HandlesPartialRules;
use Illuminate\Validation\Rule;

/**
 * Goal validation rules shared by the web controller and the MCP tools.
 *
 * Pass the acting user's id so `category_id` only accepts categories they own.
 * Omitting it leaves the rule unscoped, which lets one user attach, and then
 * read back, another user's category.
 */
class GoalRules
{
    use HandlesPartialRules;

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $userId = null): array
    {
        return [
            'category_id' => [
                'nullable',
                $userId === null
                    ? Rule::exists('categories', 'id')
                    : Rule::exists('categories', 'id')->where('user_id', $userId),
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
            'completed_at' => 'nullable|date|after:start_date',
            'status' => 'required|in:not_started,in_progress,completed,paused,abandoned',
            'priority' => 'required|in:low,medium,high',
            'polarity' => 'nullable|in:positive,negative',
            'points' => 'required|integer|min:0',
            'is_public' => 'required|boolean',
            'order' => 'nullable|integer',
        ];
    }
}

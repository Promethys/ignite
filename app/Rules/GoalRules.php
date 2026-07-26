<?php

namespace App\Rules;

/**
 * Goal validation rules shared by the web controller and the MCP tools.
 */
class GoalRules
{
    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'category_id' => 'nullable|exists:categories,id',
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

    /**
     * Same as `rules()`, but nothing is required. For partial updates.
     *
     * @return array<string, string>
     */
    public static function partialRules(): array
    {
        return array_map(
            static fn (string $rule) => str_replace('required|', 'nullable|', $rule),
            self::rules(),
        );
    }
}

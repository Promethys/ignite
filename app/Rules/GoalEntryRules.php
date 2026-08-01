<?php

namespace App\Rules;

use App\Models\Goal;
use Illuminate\Support\Carbon;

/**
 * Goal entry validation rules shared by the web controller and the MCP tools.
 */
class GoalEntryRules
{
    /**
     * Rules for a progress entry on a non-recurring goal.
     *
     * @return array<string, string>
     */
    public static function progressRules(): array
    {
        return [
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:2000',
        ];
    }

    /**
     * Rules for a check-in on a recurring goal.
     *
     * Bound to the goal rather than static: the upper bound is today in the
     * owner's timezone, and the lower bound is the goal's own start date.
     * Creating and editing share these, so a check-in can never be moved onto
     * a date it could not have been created on.
     *
     * @return array<string, array<int, string>>
     */
    public static function checkInRules(Goal $goal): array
    {
        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $today = Carbon::now()->timezone($timezone)->toDateString();

        $rules = [
            'entry_date' => ['required', 'date', "before_or_equal:{$today}"],
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        if ($goal->start_date) {
            $rules['entry_date'][] = 'after_or_equal:'.$goal->start_date->toDateString();
        }

        return $rules;
    }
}

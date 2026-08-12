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
     * @return array<string, array<int, string>>
     */
    public static function progressRules(Goal $goal): array
    {
        return [
            'increment' => ['required', 'numeric'],
            'entry_date' => ['nullable', 'date', 'before_or_equal:'.self::todayFor($goal)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Rules for a check-in on a recurring goal.
     *
     * @return array<string, array<int, string>>
     */
    public static function checkInRules(Goal $goal): array
    {
        return [
            'entry_date' => ['required', 'date', 'before_or_equal:'.self::todayFor($goal)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected static function todayFor(Goal $goal): string
    {
        $timezone = $goal->user?->timezone ?? config('app.timezone');

        return Carbon::now()->timezone($timezone)->toDateString();
    }
}

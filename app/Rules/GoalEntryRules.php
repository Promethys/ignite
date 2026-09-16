<?php

namespace App\Rules;

use App\Models\Goal;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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

    public static function ownerIdRule(User $user): Exists
    {
        return Rule::exists('goal_entries', 'id')->where(
            fn (Builder $query) => $query->whereIn(
                'goal_id',
                fn (Builder $goals) => $goals->select('id')->from('goals')->where('user_id', $user->id),
            ),
        );
    }

    protected static function todayFor(Goal $goal): string
    {
        return self::todayForTimezone($goal->user?->timezone);
    }

    public static function todayForTimezone(?string $timezone): string
    {
        return self::dateForTimezone(Carbon::now(), $timezone);
    }

    public static function dateForTimezone(DateTimeInterface|string $moment, ?string $timezone): string
    {
        return Carbon::parse($moment)
            ->timezone($timezone ?? config('app.timezone'))
            ->toDateString();
    }
}

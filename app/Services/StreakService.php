<?php

namespace App\Services;

use App\DTOs\StreakData;
use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StreakService
{
    public static function for(Goal $goal): ?StreakData
    {
        $recurrence = $goal->recurrence;

        if (! $recurrence) {
            return null;
        }

        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $cadence = match ($recurrence) {
            'daily' => ['unit' => 'day', 'format' => 'Y-m-d'],
            'weekly' => ['unit' => 'week', 'format' => 'o-W'],
            'monthly' => ['unit' => 'month', 'format' => 'Y-m'],
            'annually' => ['unit' => 'year', 'format' => 'Y'],
        };

        $entryDates = GoalEntry::select('entry_date')
            ->where('goal_id', $goal->id)
            ->orderBy('entry_date')
            ->distinct()
            ->get()
            ->map(fn (GoalEntry $entry) => $entry->entry_date);

        $now = Carbon::now()->timezone($timezone);
        $currentStreak = self::evaluateCurrentStreak($now->copy(), $cadence, $entryDates);
        $longestStreak = self::evaluateLongestStreak($entryDates, $cadence['unit']);

        return new StreakData(
            $currentStreak['streak'],
            $longestStreak,
            $cadence['unit'],
            $currentStreak['is_current_period_satisfied'],
            $now
        );
    }

    private static function evaluateCurrentStreak(Carbon $cursor, array $cadence, Collection $entryDates): array
    {
        $count = 0;
        $isCurrentPeriodSatisfied = false;
        $cadences = $entryDates
            ->map(fn ($date) => $date->format($cadence['format']))
            ->unique()
            ->values()
            ->toArray();

        if (in_array($cursor->format($cadence['format']), $cadences)) {
            $count++;
            $isCurrentPeriodSatisfied = true;
        }

        $cursor->sub("1 {$cadence['unit']}");

        while (in_array($cursor->format($cadence['format']), $cadences)) {
            $count++;
            $cursor->sub("1 {$cadence['unit']}");
        }

        return [
            'streak' => $count,
            'is_current_period_satisfied' => $isCurrentPeriodSatisfied,
        ];
    }

    private static function evaluateLongestStreak(Collection $entryDates, string $unit): int
    {
        if ($entryDates->isEmpty()) {
            return 0;
        }

        $periodStarts = $entryDates->map(function ($period) use ($unit) {
            return match ($unit) {
                'day' => $period->copy()->startOfDay(),
                'week' => $period->copy()->startOfWeek(),
                'month' => $period->copy()->startOfMonth(),
                'year' => $period->copy()->startOfYear(),
            };
        })
            ->unique()
            ->sort();

        $currentRun = 1;
        $longest = 1;

        $periodStarts
            ->sliding(2)
            ->each(function ($pair) use (&$currentRun, &$longest, $unit) {
                $isAdjacent = $pair->first()->copy()->add("1 $unit")->equalTo($pair->last());
                $currentRun = $isAdjacent ? ($currentRun + 1) : 1;
                $longest = max($longest, $currentRun);
            });

        return $longest;
    }
}

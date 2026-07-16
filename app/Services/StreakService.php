<?php

namespace App\Services;

use App\DTOs\StreakData;
use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class StreakService
{
    /**
     * The period-bucket format for each recurrence cadence.
     *
     * @return array<string, string>
     */
    public static function cadenceFormats(): array
    {
        return [
            'daily' => 'Y-m-d',
            'weekly' => 'o-W',
            'monthly' => 'Y-m',
            'annually' => 'Y',
        ];
    }

    /**
     * Bucket a date into its recurrence period key, resolved in the given timezone.
     */
    public static function periodKey(string $recurrence, Carbon $date, string $timezone): string
    {
        $format = self::cadenceFormats()[$recurrence] ?? 'Y-m-d';

        return $date->copy()->timezone($timezone)->format($format);
    }

    public static function for(Goal $goal): ?StreakData
    {
        $recurrence = $goal->recurrence;

        if (! $recurrence) {
            return null;
        }

        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $cadence = match ($recurrence) {
            'daily' => ['unit' => 'day', 'format' => self::cadenceFormats()['daily']],
            'weekly' => ['unit' => 'week', 'format' => self::cadenceFormats()['weekly']],
            'monthly' => ['unit' => 'month', 'format' => self::cadenceFormats()['monthly']],
            'annually' => ['unit' => 'year', 'format' => self::cadenceFormats()['annually']],
        };

        $entryDates = GoalEntry::select('entry_date')
            ->where('goal_id', $goal->id)
            ->orderBy('entry_date')
            ->distinct()
            ->get()
            ->map(fn (GoalEntry $entry) => $entry->entry_date);

        $now = Carbon::now()->timezone($timezone);

        if ($goal->polarity === 'negative') {
            return self::evaluateNegativeStreak($goal, $cadence, $entryDates, $now);
        }

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

    private static function evaluateNegativeStreak(Goal $goal, array $cadence, Collection $entryDates, Carbon $now): StreakData
    {
        $unit = $cadence['unit'];
        $timezone = $now->timezone;

        $anchor = $entryDates->isNotEmpty()
            ? Carbon::parse($entryDates->last())->timezone($timezone)
            : Carbon::parse($goal->start_date ?? $goal->created_at)->timezone($timezone);

        $current = self::elapsedUnits($anchor, $now, $unit);
        $longest = self::longestNegativeGap($goal, $entryDates, $now, $unit);

        $currentPeriodKey = $now->format($cadence['format']);
        $relapseInCurrentPeriod = $entryDates->contains(
            fn ($date) => Carbon::parse($date)->timezone($timezone)->format($cadence['format']) === $currentPeriodKey
        );

        return new StreakData(
            $current,
            $longest,
            $unit,
            ! $relapseInCurrentPeriod,
            $anchor
        );
    }

    private static function longestNegativeGap(Goal $goal, Collection $entryDates, Carbon $now, string $unit): int
    {
        $historyStart = Carbon::parse($goal->start_date ?? $goal->created_at)->timezone($now->timezone);

        $points = $entryDates
            ->map(fn ($date) => Carbon::parse($date)->timezone($now->timezone))
            ->prepend($historyStart)
            ->push($now);

        $longest = 0;

        $points->sliding(2)->each(function ($pair) use (&$longest, $unit) {
            $gap = self::elapsedUnits($pair->first(), $pair->last(), $unit);
            $longest = max($longest, $gap);
        });

        return $longest;
    }

    private static function elapsedUnits(Carbon $anchor, Carbon $now, string $unit): int
    {
        $from = self::startOfPeriod($anchor->copy(), $unit);
        $to = self::startOfPeriod($now->copy(), $unit);

        return (int) match ($unit) {
            'day' => $from->diffInDays($to),
            'week' => intdiv((int) $from->diffInDays($to), 7),
            'month' => $from->diffInMonths($to),
            'year' => $from->diffInYears($to),
        };
    }

    private static function startOfPeriod(Carbon $date, string $unit): Carbon
    {
        return match ($unit) {
            'day' => $date->startOfDay(),
            'week' => $date->startOfWeek(),
            'month' => $date->startOfMonth(),
            'year' => $date->startOfYear(),
        };
    }

    public static function isDeadlineCompletionEligible(Goal $goal): bool
    {
        if ($goal->polarity !== 'negative' || $goal->status === 'completed') {
            return false;
        }

        if (! $goal->deadline || $goal->deadline->isFuture()) {
            return false;
        }

        $anchor = $goal->start_date ?? $goal->created_at;

        return ! GoalEntry::where('goal_id', $goal->id)
            ->whereBetween('entry_date', [$anchor, $goal->deadline])
            ->exists();
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

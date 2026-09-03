<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GoalHeatmapService
{
    /**
     * Rows of cells for a recurring goal's activity grid, one cell per
     * recurrence period across a rolling window.
     *
     * Returns null for any goal that is not recurring, which is how the page
     * decides whether to render the grid at all.
     *
     * @return array{cadence: string, cells: array<int, array{date: string, value: int}>, total: int}|null
     */
    public static function for(Goal $goal): ?array
    {
        $recurrence = $goal->type === 'recurring' ? $goal->recurrence : null;

        if (! $recurrence || ! isset(StreakService::cadenceFormats()[$recurrence])) {
            return null;
        }

        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $now = Carbon::now()->timezone($timezone);

        $anchors = self::windowAnchors($goal, $recurrence, $now);
        $entryPeriods = self::entryPeriods($goal, $recurrence, $anchors->first());

        $cells = $anchors
            ->map(fn (Carbon $anchor) => [
                'date' => $anchor->format('Y-m-d'),
                'value' => $entryPeriods[$anchor->format(StreakService::cadenceFormats()[$recurrence])] ?? 0,
            ])
            ->values()
            ->all();

        return [
            'cadence' => $recurrence,
            'cells' => $cells,
            'total' => collect($cells)->filter(fn (array $cell) => $cell['value'] > 0)->count(),
        ];
    }

    /**
     * The anchor date of every period in the window, oldest first.
     *
     * The window opens at the goal's earliest activity, so a young goal is not
     * padded with a year of empty cells, and slides to a rolling year once the
     * history is longer than that. Daily and weekly windows open on a Monday so
     * the frontend can flow a contiguous run of cells down seven-row columns
     * without index arithmetic.
     *
     * An annual goal has no rolling bound: twelve months is one cell, so it
     * always shows its whole history.
     *
     * @return Collection<int, Carbon>
     */
    private static function windowAnchors(Goal $goal, string $recurrence, Carbon $now): Collection
    {
        $earliest = self::earliestActivity($goal, $now);

        [$unit, $start, $end] = match ($recurrence) {
            'daily' => [
                'day',
                $earliest->copy()->startOfWeek()->max($now->copy()->startOfWeek()->subWeeks(52)),
                $now->copy()->startOfDay(),
            ],
            'weekly' => [
                'week',
                $earliest->copy()->startOfWeek()->max($now->copy()->startOfWeek()->subWeeks(51)),
                $now->copy()->startOfWeek(),
            ],
            'monthly' => [
                'month',
                $earliest->copy()->startOfMonth()->max($now->copy()->startOfMonth()->subMonths(11)),
                $now->copy()->startOfMonth(),
            ],
            'annually' => [
                'year',
                $earliest->copy()->startOfYear(),
                $now->copy()->startOfYear(),
            ],
        };

        $anchors = collect();
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($end)) {
            $anchors->push($cursor->copy());
            $cursor->add("1 {$unit}");
        }

        return $anchors;
    }

    /**
     * The date the goal's history opens on.
     *
     * Reads the earliest entry_date rather than the earliest created_at, since
     * an entry can be backdated and the grid answers "when does this count
     * for", not "when was the row written". Falls back to the goal's own dates
     * so a goal with no entries still has a window, and is clamped to today so
     * a future start_date cannot produce a window that ends before it begins.
     */
    private static function earliestActivity(Goal $goal, Carbon $now): Carbon
    {
        $earliest = Carbon::parse(
            $goal->entries()->min('entry_date')
                ?? $goal->start_date
                ?? $goal->created_at
        );

        return $earliest->greaterThan($now) ? $now->copy() : $earliest;
    }

    /**
     * Entry counts keyed by recurrence period.
     *
     * The date is bucketed by formatting it directly. Converting it to a
     * timezone first would shift the day for negative-offset users, the same
     * reasoning that governs GoalEntryService::guardPeriodIsFree.
     *
     * @return array<string, int>
     */
    private static function entryPeriods(Goal $goal, string $recurrence, Carbon $windowStart): array
    {
        $format = StreakService::cadenceFormats()[$recurrence];

        return $goal->entries()
            ->whereDate('entry_date', '>=', $windowStart->format('Y-m-d'))
            ->pluck('entry_date')
            ->countBy(fn ($date) => Carbon::parse($date)->format($format))
            ->all();
    }
}

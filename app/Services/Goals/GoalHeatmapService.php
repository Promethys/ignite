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
     * Daily and weekly windows start on a Monday so the frontend can flow a
     * contiguous run of cells down seven-row columns without index arithmetic.
     *
     * @return Collection<int, Carbon>
     */
    private static function windowAnchors(Goal $goal, string $recurrence, Carbon $now): Collection
    {
        [$start, $unit] = match ($recurrence) {
            'daily' => [$now->copy()->startOfWeek()->subWeeks(52), 'day'],
            'weekly' => [$now->copy()->startOfWeek()->subWeeks(51), 'week'],
            'monthly' => [$now->copy()->startOfMonth()->subMonths(11), 'month'],
            'annually' => [self::annualWindowStart($goal, $now), 'year'],
        };

        $end = match ($recurrence) {
            'daily' => $now->copy()->startOfDay(),
            'weekly' => $now->copy()->startOfWeek(),
            'monthly' => $now->copy()->startOfMonth(),
            'annually' => $now->copy()->startOfYear(),
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
     * A rolling twelve months is exactly one year, so an annual goal would get a
     * single cell. It shows its whole history instead, back to the first entry.
     */
    private static function annualWindowStart(Goal $goal, Carbon $now): Carbon
    {
        $firstEntryDate = $goal->entries()->min('entry_date');

        $earliest = $firstEntryDate ?? $goal->start_date ?? $goal->created_at;

        $start = Carbon::parse($earliest)->startOfYear();

        return $start->greaterThan($now) ? $now->copy()->startOfYear() : $start;
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

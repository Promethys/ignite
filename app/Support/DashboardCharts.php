<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class DashboardCharts
{
    public static function monthlyCompletions(User $user): array
    {
        $timezone = $user->timezone ?? config('app.timezone');
        $startDate = now()->subMonths(11)->startOfMonth();
        $endDate = now();
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);

        $counts = $user->goals()
            ->whereNotNull('completed_at')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->orderBy('completed_at')
            ->select('id', 'completed_at')
            ->get()
            ->groupBy(fn (Goal $goal) => $goal->completed_at->timezone($timezone)->format('Y-m'))
            ->map(fn ($group) => $group->count());
        $keys = $counts->keys();

        foreach ($period as $date) {
            $formatted = $date->timezone($timezone)->format('Y-m');

            if (! $keys->contains($formatted)) {
                $counts->put($formatted, 0);
            }
        }

        return $counts->sortKeys()
            ->map(fn ($item, $key) => ['month' => $key, 'count' => $item])
            ->values()
            ->toArray();
    }

    public static function categoryBreakdown(User $user): array
    {
        $activeGoals = $user->goals()
            ->without(['milestones'])
            ->where('status', 'in_progress')
            ->whereNull('completed_at')
            ->select('id', 'category_id')
            ->get()
            ->groupBy('category_id')
            ->map(function (Collection $items, string $key) {
                /** @var Goal|null $goal */
                $goal = $items->first();

                /** @var Category|null $category */
                $category = filled($key) ? $goal->category : null;

                return [
                    'name' => $category?->name ?? 'Uncategorized',
                    'color' => $category?->color ?? '#888888',
                    'count' => $items->count(),
                ];
            })
            ->sortByDesc('count')
            ->values();

        return $activeGoals->toArray();
    }
}

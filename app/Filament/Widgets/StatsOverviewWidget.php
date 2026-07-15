<?php

namespace App\Filament\Widgets;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $newUsers = User::query()->recent()->count();
        $goalsCreated = Goal::query()->recent()->count();
        $entriesLogged = GoalEntry::query()->recent()->count();
        $totalGoalCreated = Goal::count();
        $completionRate = $totalGoalCreated > 0
            ? round(Goal::where('status', 'completed')->count() / $totalGoalCreated, 2)
            : 0;
        $abandonmentRate = $totalGoalCreated > 0
            ? round(Goal::where('status', 'abandoned')->count() / $totalGoalCreated, 2)
            : 0;

        return [
            Stat::make('Total users', $totalUsers),
            Stat::make('New users', $newUsers),
            Stat::make('Goals created', $goalsCreated),
            Stat::make('Entries logged', $entriesLogged),
            Stat::make('Completion rate', $completionRate),
            Stat::make('Abandonment rate', $abandonmentRate),
        ];
    }
}

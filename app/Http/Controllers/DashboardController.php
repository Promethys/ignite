<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Support\DashboardCharts;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $pinnedGoals = $user->goals()
            ->with(['category', 'entries', 'milestones'])
            ->whereNotNull('pin_order')
            ->orderBy('pin_order')
            ->get()
            ->append('streak');
        $otherGoals = $user->goals()
            ->with(['category', 'entries', 'milestones'])
            ->whereNull('pin_order')
            ->whereNull('completed_at')
            ->where('status', 'in_progress')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(max(0, Goal::MAX_PINNED - $pinnedGoals->count()))
            ->get()
            ->append('streak');
        $activeGoalsCount = $user->goals()->whereNull('completed_at')->where('status', 'in_progress')->count();
        $totalGoalsCount = $user->goals()->count();
        $completedGoalsCount = $user->goals()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->count();
        $completionRate = $totalGoalsCount > 0 ? ($completedGoalsCount / $totalGoalsCount) * 100 : 0;

        return Inertia::render('Dashboard', [
            'pinnedGoals' => $pinnedGoals,
            'otherGoals' => $otherGoals,
            'activeGoalsCount' => $activeGoalsCount,
            'totalGoalsCount' => $totalGoalsCount,
            'completedGoalsCount' => $completedGoalsCount,
            'completionRate' => (int) $completionRate,
            'monthlyCompletions' => DashboardCharts::monthlyCompletions($user),
            'categoryBreakdown' => DashboardCharts::categoryBreakdown($user),
        ]);
    }
}

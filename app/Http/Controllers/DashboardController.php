<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $activeGoalsList = $user->goals()
            ->with(['category', 'entries'])
            ->whereNull('completed_at')
            ->where('status', 'in_progress')
            ->get()
            ->append('streak');
        $activeGoalsCount = $activeGoalsList->count();
        $totalGoalsCount = $user->goals()->count();
        $completedGoalsCount = $user->goals()
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->count();
        $completionRate = $totalGoalsCount > 0 ? ($completedGoalsCount / $totalGoalsCount) * 100 : 0;

        return Inertia::render('Dashboard', [
            'activeGoalsList' => $activeGoalsList,
            'activeGoalsCount' => $activeGoalsCount,
            'totalGoalsCount' => $totalGoalsCount,
            'completedGoalsCount' => $completedGoalsCount,
            'completionRate' => (int) $completionRate,
        ]);
    }
}

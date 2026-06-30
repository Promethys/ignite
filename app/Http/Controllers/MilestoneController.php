<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MilestoneController extends Controller
{
    protected $rules = [
        'title' => 'required|string|max:255',
        'target_value' => 'nullable|numeric',
        'description' => 'nullable|string',
        // 'deadline' => 'nullable|date',
        'completed_at' => 'nullable|date',
        'points_reward' => 'nullable|numeric',
        'order' => 'nullable|integer',
    ];

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate($this->rules);

        $order = $goal->milestones()->max('order') + 1;

        $goal->milestones()->create([
            ...$validated,
            'order' => $order,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone added.']);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Goal $goal, Milestone $milestone)
    {
        Gate::authorize('update', $milestone);

        $validated = $request->validate($this->rules);

        if ($goal->isNot($milestone->goal)) {
            abort(403);
        }

        $milestone->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone updated.']);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Goal $goal, Milestone $milestone)
    {
        Gate::authorize('delete', $milestone);

        if ($goal->isNot($milestone->goal)) {
            abort(403);
        }

        $milestone->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone deleted.']);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function complete(Request $request, Goal $goal, Milestone $milestone)
    {
        Gate::authorize('update', $milestone);

        if ($goal->isNot($milestone->goal)) {
            abort(403);
        }

        $milestone->markAsCompleted();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Milestone completed.']);

        return redirect()->back();
    }
}

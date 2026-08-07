<?php

namespace App\Http\Controllers;

use App\Http\Requests\Goals\StoreMilestoneRequest;
use App\Http\Requests\Goals\UpdateMilestoneRequest;
use App\Models\Goal;
use App\Models\Milestone;
use App\Services\Goals\MilestoneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MilestoneController extends Controller
{
    public function __construct(
        private readonly MilestoneService $milestoneService
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMilestoneRequest $request, Goal $goal)
    {
        $this->milestoneService->add(
            $request->user(),
            $goal,
            $request->validated()
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.'.$this->toastNoun($goal).'.added')]);

        return redirect()->back();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMilestoneRequest $request, Goal $goal, Milestone $milestone)
    {
        $milestone->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.'.$this->toastNoun($goal).'.updated')]);

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

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.'.$this->toastNoun($goal).'.deleted')]);

        return redirect()->back();
    }

    /**
     * Mark the milestone as completed.
     */
    public function complete(Request $request, Goal $goal, Milestone $milestone)
    {
        if ($goal->isNot($milestone->goal)) {
            abort(403);
        }

        $this->milestoneService->complete(
            $request->user(),
            $milestone
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.'.$this->toastNoun($goal).'.completed'), 'action' => [
            'label' => __('toasts.undo'),
            'method' => 'patch',
            'url' => route('milestones.uncomplete', [
                'goal' => $goal,
                'milestone' => $milestone,
            ]),
        ]]);

        return redirect()->back();
    }

    /**
     * Mark the milestone as incomplete.
     */
    public function uncomplete(Request $request, Goal $goal, Milestone $milestone)
    {
        Gate::authorize('update', $milestone);

        if ($goal->isNot($milestone->goal)) {
            abort(403);
        }

        $milestone->markAsIncomplete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.'.$this->toastNoun($goal).'.uncompleted'), 'action' => [
            'label' => __('toasts.undo'),
            'method' => 'patch',
            'url' => route('milestones.complete', [
                'goal' => $goal,
                'milestone' => $milestone,
            ]),
        ]]);

        return redirect()->back();
    }

    /**
     * The translation noun for toasts: "step" for multi-step goals, else "milestone".
     */
    private function toastNoun(Goal $goal): string
    {
        return $goal->type === 'multi_step' ? 'step' : 'milestone';
    }
}

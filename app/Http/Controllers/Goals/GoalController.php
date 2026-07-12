<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\User;
use App\Services\StreakService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GoalController extends Controller
{
    protected $rules = [
        'user_id' => 'required|exists:users,id',
        'category_id' => 'nullable|exists:categories,id',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'icon' => 'nullable|string|max:50',
        'type' => 'required|in:simple,quantifiable,recurring,multi_step',
        'direction' => 'required|in:ascending,descending',
        'target_value' => 'nullable|numeric',
        'current_value' => 'required|numeric',
        'unit' => 'nullable|string|max:50',
        'recurrence' => 'nullable|in:daily,weekly,monthly,annually',
        'start_date' => 'nullable|date',
        'deadline' => 'nullable|date|after_or_equal:start_date',
        'completed_at' => 'nullable|date|after:start_date',
        'status' => 'required|in:not_started,in_progress,completed,paused,abandoned',
        'priority' => 'required|in:low,medium,high',
        'polarity' => 'nullable|in:positive,negative',
        'points' => 'required|integer|min:0',
        'is_public' => 'required|boolean',
        'order' => 'nullable|integer',
    ];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|integer|min:1|exists:categories,id',
        ]);

        return Inertia::render('Goals/Index', [
            'items' => auth()->user()->goals()->with('user')->get()->append('streak'),
            'categories' => auth()->user()->categories,
            'category_id' => $validated['category'] ?? null,
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user()->load('categories');

        $requested = $request->query('category');
        $selectedCategory = is_numeric($requested) && $user->categories->contains('id', (int) $requested)
            ? (string) $requested
            : null;

        return Inertia::render('Goals/Create', [
            'user' => [
                'id' => $user->id,
                'categories' => $user->categories->pluck('name', 'id'),
            ],
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Goal::class);

        $rules = $this->rules;
        if ($request->input('type') === 'quantifiable') {
            $rules['target_value'] = 'required|numeric';
        }

        $validated = $request->validate($rules);

        $order = User::find($validated['user_id'])->goals()->count() + 1;

        Goal::create([
            ...$validated,
            'order' => $order,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.created')]);

        return to_route('goals.index');
    }

    public function show(Goal $goal)
    {
        Gate::authorize('view', $goal);

        if (StreakService::isDeadlineCompletionEligible($goal)) {
            $previousStatus = $goal->status;
            $goal->markAsCompleted();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.completed'), 'action' => [
                'label' => __('toasts.undo'),
                'method' => 'patch',
                'url' => route('goals.uncomplete', [
                    'goal' => $goal,
                ]),
                'data' => [
                    'status' => $previousStatus,
                ],
            ]]);
        }

        $chartEntries = $goal->entries->map(fn ($entry) => [
            'entry_date' => $entry->entry_date,
            'value' => $entry->value,
        ]);

        $goal->load([
            'entries' => fn ($query) => $query->orderBy('entry_date', 'desc')->take(20),
            'milestones' => fn ($query) => $query->orderBy('order', 'asc'),
        ])
            ->append('streak');

        return Inertia::render('Goals/Show', compact('goal', 'chartEntries'));
    }

    public function edit(Goal $goal)
    {
        Gate::authorize('view', $goal);

        $user = auth()->user()->load('categories');

        $goal->load([
            'milestones' => fn ($query) => $query->orderBy('order', 'asc'),
        ]);

        return Inertia::render('Goals/Edit', [
            'goal' => $goal,
            'user' => [
                'id' => $user->id,
                'categories' => $user->categories->pluck('name', 'id'),
            ],
        ]);
    }

    public function update(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate($this->rules);

        $goal->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.updated')]);

        return back(303);
    }

    public function destroy(Goal $goal)
    {
        Gate::authorize('delete', $goal);

        $goal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.deleted')]);

        return back(303);
    }

    public function updateStatus(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate([
            'status' => $this->rules['status'],
        ]);

        $goal->updateStatus($validated['status']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.status_updated')]);

        return back(303);
    }

    public function complete(Goal $goal)
    {
        Gate::authorize('update', $goal);

        $oldStatus = $goal->status;
        $goal->markAsCompleted();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.completed'), 'action' => [
            'label' => __('toasts.undo'),
            'method' => 'patch',
            'url' => route('goals.uncomplete', [
                'goal' => $goal,
            ]),
            'data' => [
                'status' => $oldStatus,
            ],
        ]]);

        return back(303);
    }

    public function uncomplete(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,paused,abandoned',
        ]);

        Goal::withoutEvents(function () use ($goal, $validated) {
            $goal->update([
                'status' => $validated['status'],
                'completed_at' => null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.completion_reverted')]);

        return back(303);
    }
}

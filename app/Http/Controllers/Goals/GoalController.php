<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\User;
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
        'deadline' => 'nullable|date|after:start_date',
        'completed_at' => 'nullable|date|after:start_date',
        'status' => 'required|in:not_started,in_progress,completed,paused,abandoned',
        'priority' => 'required|in:low,medium,high',
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
            'items' => auth()->user()->goals,
            'categories' => auth()->user()->categories,
            'category_id' => $validated['category'] ?? null,
        ]);
    }

    public function create()
    {
        $user = auth()->user()->load('categories');

        return Inertia::render('Goals/Create', [
            'user' => [
                'id' => $user->id,
                'categories' => $user->categories->pluck('name', 'id'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Goal::class);

        $validated = $request->validate($this->rules);

        $order = User::find($validated['user_id'])->goals()->count() + 1;

        Goal::create([
            ...$validated,
            'order' => $order,
        ]);

        return to_route('goals.index');
    }

    public function show(Goal $goal)
    {
        Gate::authorize('view', $goal);

        $chartEntries = $goal->entries->map(fn ($entry) => [
            'entry_date' => $entry->entry_date,
            'value' => $entry->value,
        ]);

        $goal->load([
            'entries' => fn ($query) => $query->orderBy('entry_date', 'desc')->take(20),
        ]);

        return Inertia::render('Goals/Show', compact('goal', 'chartEntries'));
    }

    public function edit(Goal $goal)
    {
        Gate::authorize('view', $goal);

        $user = auth()->user()->load('categories');

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

        return back(303);
    }

    public function destroy(Goal $goal)
    {
        Gate::authorize('delete', $goal);

        $goal->delete();

        return back(303);
    }

    public function updateStatus(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate([
            'status' => $this->rules['status'],
        ]);

        $goal->updateStatus($validated['status']);

        return back(303);
    }

    public function complete(Goal $goal)
    {
        Gate::authorize('update', $goal);

        $goal->markAsCompleted();

        return back(303);
    }
}

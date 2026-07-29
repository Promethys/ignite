<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Rules\GoalRules;
use App\Services\Goals\GoalService;
use App\Services\StreakService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GoalController extends Controller
{
    protected array $rules;

    public function __construct(private readonly GoalService $goalService)
    {
        $this->rules = [
            'user_id' => 'required|exists:users,id',
            ...GoalRules::rules(),
        ];
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'category' => 'nullable|integer|min:1|exists:categories,id',
        ]);

        $actor = $request->user();

        return Inertia::render('Goals/Index', [
            'items' => $this->goalService->listForUser($actor)['goals'],
            'categories' => $actor->categories,
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
        $rules = $this->rules;
        if ($request->input('type') === 'quantifiable') {
            $rules['target_value'] = 'required|numeric';
        }

        $validated = $request->validate($rules);

        $this->goalService->create($request->user(), $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.created')]);

        return to_route('goals.index');
    }

    public function show(Request $request, Goal $goal)
    {
        $goal = $this->goalService->find($request->user(), $goal);

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

        $chartEntries = $goal->entries()
            ->get()
            ->map(fn ($entry) => [
                'entry_date' => $entry->entry_date,
                'value' => $entry->value,
            ]);

        $today = Carbon::now()->timezone($goal->user?->timezone ?? config('app.timezone'))->toDateString();

        return Inertia::render('Goals/Show', compact('goal', 'chartEntries', 'today'));
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
        $validated = $request->validate($this->rules);

        $this->goalService->update($request->user(), $goal, $validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.updated')]);

        return back(303);
    }

    public function destroy(Request $request, Goal $goal)
    {
        $this->goalService->delete($request->user(), $goal);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.deleted')]);

        return to_route('goals.index', status: 303);
    }

    public function updateStatus(Request $request, Goal $goal)
    {
        $validated = $request->validate([
            'status' => $this->rules['status'],
        ]);

        $this->goalService->setStatus($request->user(), $goal, $validated['status']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.status_updated')]);

        return back(303);
    }

    public function complete(Request $request, Goal $goal)
    {
        $previousStatus = $goal->status;

        $this->goalService->complete($request->user(), $goal);

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

        return back(303);
    }

    public function uncomplete(Request $request, Goal $goal)
    {
        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,paused,abandoned',
        ]);

        $this->goalService->uncomplete($request->user(), $goal, $validated['status']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.goal.completion_reverted')]);

        return back(303);
    }
}

<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Services\Goals\GoalEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GoalEntryController extends Controller
{
    public function __construct(
        private readonly GoalEntryService $goalEntryService
    ) {}

    public function index(Request $request, Goal $goal)
    {
        Gate::authorize('view', $goal);

        $validated = $request->validate([
            'search' => 'nullable|string|max:191',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after:from',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = $goal->entries();

        if (isset($validated['search']) && ! empty($validated['search'])) {
            $query->whereRaw('LOWER(note) like ?', ['%'.strtolower($validated['search']).'%']);
        }

        if (isset($validated['from']) && ! empty($validated['from'])) {
            $query->whereDate('entry_date', '>=', $validated['from']);
        }

        if (isset($validated['to']) && ! empty($validated['to'])) {
            $query->whereDate('entry_date', '<=', $validated['to']);
        }

        $query->orderBy('entry_date', 'desc');

        $entries = Inertia::scroll(fn () => $query->paginate(20));

        return Inertia::render('GoalEntries/Index', compact('goal', 'entries'));
    }

    public function update(Request $request, Goal $goal, GoalEntry $goalEntry)
    {
        Gate::authorize('update', $goalEntry);

        $validated = $request->validate([
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $this->goalEntryService->updateEntry(
            $request->user(),
            $goalEntry,
            $validated['increment'],
            $validated['note'] ?? null
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    public function store(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        if ($goal->type === 'recurring') {
            return $this->storeCheckIn($request, $goal);
        }

        $validated = $request->validate([
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $this->goalEntryService->logProgress(
            $request->user(),
            $goal,
            $validated['increment'],
            $validated['note'] ?? null
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    /**
     * Record a dated check-in for a recurring goal without touching current_value.
     */
    protected function storeCheckIn(Request $request, Goal $goal)
    {
        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $today = Carbon::now()->timezone($timezone)->toDateString();

        $rules = [
            'entry_date' => ['required', 'date', "before_or_equal:{$today}"],
            'note' => ['nullable', 'string', 'max:500'],
        ];

        if ($goal->start_date) {
            $rules['entry_date'][] = 'after_or_equal:'.$goal->start_date->toDateString();
        }

        $validated = $request->validate($rules);

        $this->goalEntryService->recordCheckIn(
            $request->user(),
            $goal,
            $validated['entry_date'],
            $validated['note'] ?? null
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    public function destroy(Request $request, Goal $goal, GoalEntry $goalEntry)
    {
        $user = $request->user();

        if ($goal->id !== $goalEntry->goal_id) {
            abort(404);
        }

        $this->goalEntryService->deleteEntry(
            $user,
            $goalEntry,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.deleted')]);

        return back();
    }
}

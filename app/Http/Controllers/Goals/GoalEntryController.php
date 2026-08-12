<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Http\Requests\Goals\StoreGoalEntryRequest;
use App\Http\Requests\Goals\UpdateGoalEntryRequest;
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

        $today = Carbon::now()->timezone($goal->user?->timezone ?? config('app.timezone'))->toDateString();

        return Inertia::render('GoalEntries/Index', compact('goal', 'entries', 'today'));
    }

    public function update(UpdateGoalEntryRequest $request, Goal $goal, GoalEntry $goalEntry)
    {
        $validated = $request->validated();

        if ($goal->type === 'recurring') {
            $this->goalEntryService->updateCheckIn(
                $request->user(),
                $goalEntry,
                $validated['entry_date'],
                $validated['note'] ?? null
            );
        } else {
            $this->goalEntryService->updateEntry(
                $request->user(),
                $goalEntry,
                $validated['increment'],
                $validated['note'] ?? null
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    public function store(StoreGoalEntryRequest $request, Goal $goal)
    {
        $validated = $request->validated();

        if ($goal->type === 'recurring') {
            $this->goalEntryService->recordCheckIn(
                $request->user(),
                $goal,
                $validated['entry_date'],
                $validated['note'] ?? null
            );
        } else {
            $this->goalEntryService->logProgress(
                $request->user(),
                $goal,
                $validated['increment'],
                $validated['note'] ?? null,
                $validated['entry_date'] ?? null
            );
        }

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

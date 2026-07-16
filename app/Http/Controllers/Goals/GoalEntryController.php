<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class GoalEntryController extends Controller
{
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
        $newEntryValue = $goalEntry->previous_value + $validated['increment'];
        $entryData = [
            'value' => $newEntryValue,
            'note' => $validated['note'] ?? null,
        ];

        \DB::transaction(function () use ($goal, $goalEntry, $entryData, $validated) {
            $newValue = $goal->current_value + $validated['increment'] - $goalEntry->increment_value;

            $goalEntry->update($entryData);
            $goal->update([
                'current_value' => $newValue,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    public function store(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate([
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:500',
        ]);
        $newEntryValue = $goal->current_value + $validated['increment'];
        $entryData = [
            'value' => $newEntryValue,
            'previous_value' => $goal->current_value,
            'note' => $validated['note'] ?? null,
            'entry_date' => now()->toDateString(),
        ];

        \DB::transaction(function () use ($goal, $entryData, $newEntryValue) {
            $goal->entries()->create($entryData);
            $goal->update([
                'current_value' => $newEntryValue,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    public function destroy(Request $request, Goal $goal, GoalEntry $goalEntry)
    {
        Gate::authorize('delete', $goalEntry);

        $newEntryValue = $goal->current_value - $goalEntry->increment_value;

        \DB::transaction(function () use ($goal, $newEntryValue, $goalEntry) {
            $goalEntry->delete();
            $goal->update([
                'current_value' => $newEntryValue,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.deleted')]);

        return back();
    }
}

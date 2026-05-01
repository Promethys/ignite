<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Database\Eloquent\Builder;
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
            $query->where(function (Builder $query) use ($validated) {
                $query->whereRaw('LOWER(note) like ?', ['%'.strtolower($validated['search']).'%'])
                    ->orWhereRaw('LOWER(value) like ?', ['%'.strtolower($validated['search']).'%'])
                    ->orWhereRaw('LOWER(entry_date) like ?', ['%'.strtolower($validated['search']).'%']);
            });
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

    public function store(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate([
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:500',
        ]);
        $newValue = $goal->current_value + $validated['increment'];
        $entryData = [
            'value' => $newValue,
            'previous_value' => $goal->current_value,
            'note' => $validated['note'] ?? null,
            'entry_date' => now()->toDateString(),
        ];

        \DB::transaction(function () use ($goal, $entryData, $newValue) {
            $goal->entries()->create($entryData);
            $goal->update([
                'current_value' => $newValue,
            ]);
        });

        return to_route('goals.show', ['goal' => $goal])
            ->with('notification', [
                'type' => 'success',
                'title' => 'Success',
                'body' => 'Goal entry saved successfully!',
            ]);
    }

    public function destroy(Request $request, Goal $goal, GoalEntry $goalEntry)
    {
        Gate::authorize('delete', $goalEntry);

        $newValue = $goal->current_value - $goalEntry->increment_value;

        \DB::transaction(function () use ($goal, $newValue, $goalEntry) {
            $goalEntry->delete();
            $goal->update([
                'current_value' => $newValue,
            ]);
        });

        return back()
            ->with('notification', [
                'type' => 'success',
                'title' => 'Success',
                'body' => 'Goal entry deleted successfully!',
            ]);
    }
}

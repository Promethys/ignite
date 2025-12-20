<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GoalEntryController extends Controller
{
    public function store(Request $request, Goal $goal)
    {
        Gate::authorize('update', $goal);

        $validated = $request->validate([
            'increment' => 'required|numeric',
            'note' => 'nullable|string|max:500'
        ]);
        $newValue = $goal->current_value + $validated['increment'];
        $entryData = [
            'value' => $newValue,
            'previous_value' => $goal->current_value,
            'note' => $validated['note'] ?? null,
            'entry_date' => now()->toDateString(),
        ];

        \DB::transaction(function () use($goal, $entryData, $newValue) {
            $goal->entries()->create($entryData);
            $goal->update([
                'current_value' => $newValue
            ]);
        });

        return to_route('goals.show', ['goal' => $goal])
            ->with('notification', [
                'type' => 'success',
                'title' => 'Success',
                'body' => 'Goal entry saved successfully!'
            ]);
    }

    public function destroy(Request $request, Goal $goal, GoalEntry $goalEntry)
    {
        Gate::authorize('delete', $goalEntry);

        $newValue = $goal->current_value - $goalEntry->increment_value;

        \DB::transaction(function () use($goal, $newValue, $goalEntry) {
            $goalEntry->delete();
            $goal->update([
                'current_value' => $newValue
            ]);
        });

        return to_route('goals.show', ['goal' => $goal])
            ->with('notification', [
                'type' => 'success',
                'title' => 'Success',
                'body' => 'Goal entry deleted successfully!'
            ]);
    }
}

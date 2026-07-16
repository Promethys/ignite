<?php

namespace App\Http\Controllers\Goals;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Services\StreakService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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

        if ($goal->type === 'recurring') {
            return $this->storeCheckIn($request, $goal);
        }

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

    /**
     * Record a dated check-in for a recurring goal without touching current_value.
     */
    protected function storeCheckIn(Request $request, Goal $goal)
    {
        $timezone = $goal->user?->timezone ?? config('app.timezone');
        $recurrence = $goal->recurrence ?? 'daily';
        $today = Carbon::now()->timezone($timezone)->toDateString();

        $rules = [
            'entry_date' => ['required', 'date', "before_or_equal:{$today}"],
            'note' => ['nullable', 'string', 'max:500'],
        ];

        if ($goal->start_date) {
            $rules['entry_date'][] = 'after_or_equal:'.$goal->start_date->toDateString();
        }

        $validated = $request->validate($rules);

        $format = StreakService::cadenceFormats()[$recurrence] ?? 'Y-m-d';

        // Calendar dates (the stored entries and the user-typed check-in date)
        // are bucketed by formatting directly, mirroring how the streak logic
        // buckets stored entry dates. A timezone conversion only applies to
        // the live "now" instant used for the upper bound above.
        $newKey = Carbon::parse($validated['entry_date'])->format($format);
        $periodTaken = $goal->entries()
            ->orderBy('entry_date')
            ->pluck('entry_date')
            ->map(fn ($date) => Carbon::parse($date)->format($format))
            ->unique()
            ->contains($newKey);

        if ($periodTaken) {
            throw ValidationException::withMessages([
                'entry_date' => __('validation.custom.entry_date.check_in_period_taken'),
            ]);
        }

        $goal->entries()->create([
            'entry_date' => $validated['entry_date'],
            'note' => $validated['note'] ?? null,
            'value' => 1,
            'previous_value' => 0,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.saved')]);

        return back();
    }

    public function destroy(Request $request, Goal $goal, GoalEntry $goalEntry)
    {
        Gate::authorize('delete', $goalEntry);

        if ($goal->type === 'recurring') {
            $goalEntry->delete();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('toasts.entry.deleted')]);

            return back();
        }

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

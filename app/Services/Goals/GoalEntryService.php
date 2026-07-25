<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GoalEntryService
{
    public function logProgress(User $actor, Goal $goal, float $increment, ?string $note = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $goal);

        $newValue = $goal->current_value + $increment;

        return \DB::transaction(function () use ($goal, $newValue, $note): GoalEntry {
            $entry = $goal->entries()->create([
                'value' => $newValue,
                'previous_value' => $goal->current_value,
                'note' => $note,
                'entry_date' => now()->toDateString(),
            ]);
            $goal->update(['current_value' => $newValue]);

            return $entry;
        });
    }

    public function recordCheckIn(User $actor, Goal $goal, string $entryDate, ?string $note = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $goal);

        $recurrence = $goal->recurrence ?? 'daily';
        $format = StreakService::cadenceFormats()[$recurrence] ?? 'Y-m-d';

        $newKey = Carbon::parse($entryDate)->format($format);
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

        $entry = $goal->entries()->create([
            'entry_date' => $entryDate,
            'note' => $note ?? null,
            'value' => 1,
            'previous_value' => 0,
        ]);

        return $entry;
    }

    public function updateEntry(User $actor, GoalEntry $entry, float $increment, ?string $note = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $entry);

        $goal = $entry->goal;
        $newEntryValue = $entry->previous_value + $increment;
        $entryData = [
            'value' => $newEntryValue,
            'note' => $note ?? null,
        ];

        \DB::transaction(function () use ($goal, $entry, $entryData, $increment) {
            $newValue = $goal->current_value + $increment - $entry->increment_value;

            $entry->update($entryData);
            $goal->update([
                'current_value' => $newValue,
            ]);
        });

        return $entry->fresh();
    }
}

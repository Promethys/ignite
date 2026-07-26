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

        if ($goal->type === 'recurring') {
            throw ValidationException::withMessages([
                'goal' => __('validation.custom.goal.log_progress_on_recurring'),
            ]);
        }

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

        if ($goal->type !== 'recurring') {
            throw ValidationException::withMessages([
                'goal' => __('validation.custom.goal.check_in_on_non_recurring'),
            ]);
        }

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

        $data = [
            'entry_date' => $entryDate,
            'value' => 1,
            'previous_value' => 0,
        ];

        if ($note !== null) {
            $data['note'] = $note;
        }

        $entry = $goal->entries()->create($data);

        return $entry;
    }

    public function updateEntry(User $actor, GoalEntry $entry, float $increment, ?string $note = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $entry);

        $goal = $entry->goal;
        $newEntryValue = $entry->previous_value + $increment;
        $entryData = [
            'value' => $newEntryValue,
        ];

        if ($note !== null) {
            $entryData['note'] = $note;
        }

        \DB::transaction(function () use ($goal, $entry, $entryData, $increment) {
            $newValue = $goal->current_value + $increment - $entry->increment_value;

            $entry->update($entryData);
            $goal->update([
                'current_value' => $newValue,
            ]);
        });

        return $entry->fresh();
    }

    public function deleteEntry(User $actor, GoalEntry $entry): ?bool
    {
        Gate::forUser($actor)->authorize('delete', $entry);

        $goal = $entry->goal;

        if ($goal->type === 'recurring') {
            return $entry->delete();
        }

        $newValue = $goal->current_value - $entry->increment_value;

        return \DB::transaction(function () use ($goal, $newValue, $entry): bool {
            $entry->delete();
            $goal->update([
                'current_value' => $newValue,
            ]);

            return true;
        });
    }
}

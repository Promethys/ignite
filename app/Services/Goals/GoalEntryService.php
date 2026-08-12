<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GoalEntryService
{
    /**
     * List a goal's entries with optional filters, newest first.
     *
     * Never silently truncates: returns the total matching the filters
     * alongside the (capped) slice, so a caller knows whether more exist.
     *
     * @param  array<string, mixed>  $filters
     * @return array{entries: Collection<int, GoalEntry>, total: int, limit: int}
     */
    public function listEntries(User $actor, Goal $goal, array $filters = []): array
    {
        Gate::forUser($actor)->authorize('view', $goal);

        $query = $goal->entries();

        if (! empty($filters['search'])) {
            $query->whereRaw('LOWER(note) like ?', ['%'.strtolower($filters['search']).'%']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('entry_date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('entry_date', '<=', $filters['to']);
        }

        $total = $query->count();

        $limit = min((int) ($filters['limit'] ?? 50), 200);

        $entries = (clone $query)
            ->orderBy('entry_date', 'desc')
            ->limit($limit)
            ->get();

        return [
            'entries' => $entries,
            'total' => $total,
            'limit' => $limit,
        ];
    }

    public function logProgress(User $actor, Goal $goal, float $increment, ?string $note = null, ?string $entryDate = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $goal);

        if ($goal->type === 'recurring') {
            throw ValidationException::withMessages([
                'goal' => __('validation.custom.goal.log_progress_on_recurring'),
            ]);
        }

        $entryDate ??= now()->toDateString();
        $newValue = $goal->current_value + $increment;

        return \DB::transaction(function () use ($goal, $increment, $newValue, $note, $entryDate): GoalEntry {
            $laterEntries = $goal->entries()
                ->whereDate('entry_date', '>', $entryDate)
                ->get();

            $previousValue = $laterEntries->isEmpty()
                ? (float) $goal->current_value
                : $this->cumulativeValueOn($goal, $entryDate);

            $entry = $goal->entries()->create([
                'value' => $previousValue + $increment,
                'previous_value' => $previousValue,
                'note' => $note,
                'entry_date' => $entryDate,
            ]);

            foreach ($laterEntries as $later) {
                $later->update([
                    'previous_value' => $later->previous_value + $increment,
                    'value' => $later->value + $increment,
                ]);
            }

            $goal->update(['current_value' => $newValue]);

            return $entry->fresh();
        });
    }

    /**
     * The goal's running total as it stood at the end of the given date.
     */
    protected function cumulativeValueOn(Goal $goal, string $entryDate): float
    {
        $latest = $goal->entries()
            ->whereDate('entry_date', '<=', $entryDate)
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        return (float) ($latest?->value ?? $goal->initial_value ?? 0);
    }

    public function recordCheckIn(User $actor, Goal $goal, string $entryDate, ?string $note = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $goal);

        if ($goal->type !== 'recurring') {
            throw ValidationException::withMessages([
                'goal' => __('validation.custom.goal.check_in_on_non_recurring'),
            ]);
        }

        $this->guardPeriodIsFree($goal, $entryDate);

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

    /**
     * Move an existing check-in to another date, or change its note.
     *
     * A check-in carries no increment, so this never touches the goal's
     * current value. The one-per-period rule still applies, ignoring the
     * entry being edited so re-saving it on its own date is not a conflict.
     */
    public function updateCheckIn(User $actor, GoalEntry $entry, string $entryDate, ?string $note = null): GoalEntry
    {
        Gate::forUser($actor)->authorize('update', $entry);

        $goal = $entry->goal;

        if ($goal->type !== 'recurring') {
            throw ValidationException::withMessages([
                'goal' => __('validation.custom.goal.check_in_on_non_recurring'),
            ]);
        }

        $this->guardPeriodIsFree($goal, $entryDate, $entry->id);

        $data = ['entry_date' => $entryDate];

        if ($note !== null) {
            $data['note'] = $note;
        }

        $entry->update($data);

        return $entry->fresh();
    }

    /**
     * Reject a check-in date whose recurrence period already holds one.
     *
     * The calendar date is bucketed by formatting it directly. Converting it
     * to a timezone first would shift the day for negative-offset users.
     */
    protected function guardPeriodIsFree(Goal $goal, string $entryDate, ?int $ignoreEntryId = null): void
    {
        $recurrence = $goal->recurrence ?? 'daily';
        $format = StreakService::cadenceFormats()[$recurrence] ?? 'Y-m-d';

        $periodTaken = $goal->entries()
            ->when($ignoreEntryId !== null, fn ($query) => $query->whereKeyNot($ignoreEntryId))
            ->pluck('entry_date')
            ->map(fn ($date) => Carbon::parse($date)->format($format))
            ->unique()
            ->contains(Carbon::parse($entryDate)->format($format));

        if ($periodTaken) {
            throw ValidationException::withMessages([
                'entry_date' => __('validation.custom.entry_date.check_in_period_taken'),
            ]);
        }
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

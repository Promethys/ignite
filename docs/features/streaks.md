# Streaks

## What it is

A streak measures consistency on a `recurring` goal: how many consecutive periods (day, week, month, or year, depending on the goal's `recurrence`) the user has logged a check-in for. It's exposed through `Goal::streak()`, an accessor that returns a `StreakData` object (`current`, `longest`, `unit`, `current_period_satisfied`) only when the goal's `type` is `recurring`; every other goal type gets `null`. Streaks apply only to recurring goals; there is no separate switch to enable them.

## Computed on read, not stored

There is no `streak` column anywhere. `StreakService::for(Goal $goal)` recomputes the streak every time it's called, from the goal's `GoalEntry` rows (`entry_date`, ordered and de-duplicated). Controllers opt into this with `->append('streak')` (used by `GoalController::index` and `GoalController::show`), which invokes the accessor and serializes the result. Nothing about a streak is cached or persisted; it's always derived fresh from the entry history at request time.

## Cadence buckets

Each `recurrence` value maps to a bucketing unit and date format (`StreakService::cadenceFormats()`):

| `recurrence` | unit | bucket format |
| --- | --- | --- |
| `daily` | day | `Y-m-d` |
| `weekly` | week | `o-W` (ISO week) |
| `monthly` | month | `Y-m` |
| `annually` | year | `Y` |

An entry "counts" for a period if its `entry_date`, formatted with that bucket format, matches the period being checked. All streak math is done in the goal owner's timezone (`$goal->user->timezone`, falling back to `config('app.timezone')`).

## Positive streaks (default polarity)

For goals with `polarity !== 'negative'` (the default), a streak counts periods where the user showed up.

**Current streak** (`evaluateCurrentStreak`): starting from "now", check whether the current period has a matching entry.
- If it does, count starts at `1` and `current_period_satisfied` is `true`.
- If it doesn't, count starts at `0` and `current_period_satisfied` is `false`, but the streak isn't reset yet: the cursor still steps back one period and keeps counting.
- From there, the cursor steps backward one period at a time, incrementing the count for every consecutive period (working backward) that has a matching entry, and stops at the first period (going backward) with no entry.

The practical effect: **not having logged the current, still-open period does not break the streak on its own.** The streak only actually breaks once there are two consecutive missing periods (the current one and the one before it) with no entry, because at that point the backward walk finds nothing on its very first step and the count stays `0`.

**Longest streak** (`evaluateLongestStreak`): takes the distinct period-start dates across all of a goal's entries, sorted chronologically, and finds the longest run of periods that are each exactly one unit apart from the next (no gaps). Returns `0` only if there are no entries at all; otherwise the minimum is `1`.

## Negative streaks (`polarity: negative`)

Negative polarity flips the meaning: an entry represents a lapse (e.g. a relapse on a goal you're trying to avoid), and the "streak" is time spent clean, not time spent checking in.

**Anchor point**: the most recent entry's date if any entries exist, otherwise the goal's `start_date` (or `created_at` if no `start_date`).

**Current streak** (`elapsedUnits`): the number of whole periods elapsed between the anchor's period-start and now's period-start. It's `0` if the anchor and now fall in the same period, and increases as more full periods pass without a new entry.

**Longest streak** (`longestNegativeGap`): builds a timeline of points (the goal's start, or `created_at`; every distinct entry date in order; and now) and takes the largest gap (in whole periods) between any two consecutive points. This is the longest clean stretch the goal has ever had, including the stretch currently in progress (since "now" is always the last point).

**Reset rule**: logging a new entry on a negative-polarity goal moves the anchor forward to that entry's date, which drops the current streak back toward `0` for whatever period that entry falls in. There's no other reset path; the current streak only shrinks when a new (later) entry is recorded.

`current_period_satisfied` is inverted for negative streaks: it's `true` when there is **no** entry (no lapse) in the current period, i.e. the user is currently "clean" for this period.

## Deadline-based auto-completion for negative-polarity goals

`StreakService::isDeadlineCompletionEligible(Goal $goal)` returns `true` only when all of:
- `polarity === 'negative'` and the goal isn't already `completed`,
- the goal has a `deadline` that is not in the future (today or past),
- and there are zero entries between the goal's start (`start_date` or `created_at`) and its `deadline`.

In other words: an avoidance goal that reached its deadline with no logged lapses is eligible to be auto-completed. `GoalController::show` checks this on every view and, if eligible, calls `markAsCompleted()` immediately (with an undo action surfaced in the success toast).

## How to use it

- Set a goal's `type` to `recurring` and pick a `recurrence` (`daily`, `weekly`, `monthly`, or `annually`) to get a streak.
- Log a check-in through the recurring goal's entry form; check-ins are dated entries with a fixed `value` of `1` that never touch `current_value` (see [Goal Types](/features/goal-types)). Only one check-in is allowed per period; the server rejects a second one for a period already covered.
- Set `polarity` to `negative` on a recurring goal to track "time since last lapse" instead of "periods checked in."

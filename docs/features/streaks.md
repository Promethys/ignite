# Streaks

## What it is

A streak measures consistency on a `recurring` goal: how many consecutive periods (day, week, month, or year, depending on the goal's `recurrence`) the user has logged a check-in for. It's exposed through `Goal::streak()`, an accessor that returns a `StreakData` object (`current`, `longest`, `unit`, `current_period_satisfied`) only when the goal's `type` is `recurring`; every other goal type gets `null`. Streaks apply only to recurring goals; there is no separate switch to enable them.

## Computed on read, not stored

There is no `streak` column anywhere. `StreakService::for(Goal $goal)` recomputes the streak every time it's called, from the goal's `GoalEntry` rows (`entry_date`, ordered and de-duplicated). Controllers opt into this with `->append('streak')` (used by `GoalController::index` and `GoalController::show`), which invokes the accessor and serializes the result. Nothing about a streak is cached or persisted; it's always derived fresh from the entry history at request time.

## Cadence buckets

Each `recurrence` value maps to a bucketing unit and date format (`StreakService::cadenceFormats()`):

| `recurrence` | unit  | bucket format    |
| ------------ | ----- | ---------------- |
| `daily`      | day   | `Y-m-d`          |
| `weekly`     | week  | `o-W` (ISO week) |
| `monthly`    | month | `Y-m`            |
| `annually`   | year  | `Y`              |

An entry "counts" for a period if its `entry_date`, formatted with that bucket format, matches the period being checked. All streak math is done in the goal owner's timezone (`$goal->user->timezone`, falling back to `config('app.timezone')`).

## Positive streaks (default polarity)

For goals with `polarity !== 'negative'` (the default), a streak counts periods where the user showed up.

**Current streak** (`evaluateCurrentStreak`): starting from "now", check whether the current period has a matching entry.

- If it does, count starts at `1` and `current_period_satisfied` is `true`.
- If it doesn't, count starts at `0` and `current_period_satisfied` is `false`, but the streak isn't reset yet: the cursor still steps back one period and keeps counting.
- From there, the cursor steps backward one period at a time, incrementing the count for every consecutive period (working backward) that has a matching entry, and stops at the first period (going backward) with no entry.

::: info The current period is forgiving
Not having logged the current, still-open period does not break the streak on its own. The streak only actually breaks once there are two consecutive missing periods (the current one and the one before it) with no entry, because at that point the backward walk finds nothing on its very first step and the count stays `0`.
:::

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

## Activity heatmap

The goal page renders a grid beneath the streak card showing which periods of the recent past hold an entry. It appears for `recurring` goals only; `GoalHeatmapService::for(Goal $goal)` returns `null` for every other type, and the page omits the section when it does.

**One cell is one recurrence period**, not one day. Because the server allows at most one entry per period (see `guardPeriodIsFree`), the cell and the period are the same unit, and a fully logged goal fills the grid at every cadence.

| `recurrence` | one cell is | cell anchor | longest window           |
| ------------ | ----------- | ----------- | ------------------------ |
| `daily`      | a day       | the day     | 53 week-columns          |
| `weekly`     | an ISO week | the Monday  | 52 weeks                 |
| `monthly`    | a month     | the 1st     | 12 months                |
| `annually`   | a year      | 1 January   | unbounded, whole history |

**The window opens at the goal's earliest activity and slides once its history is longer than a year.** A goal whose first entry is a month old shows a month of cells rather than a year of empty ones; once there is more than a year of history the start moves forward so the grid stays a rolling year ending today. The annual cadence has no rolling bound, since twelve months there is a single cell.

Earliest activity means the earliest `entry_date`, not the earliest `created_at`: an entry can be backdated, and the grid answers "when does this count for" rather than "when was the row written". A goal with no entries falls back to `start_date`, then `created_at`, and a `start_date` in the future is clamped to today so the window cannot end before it begins.

Cells are anchored on Mondays, matching `Carbon::startOfWeek()` and the `o-W` bucket above, so a daily grid's columns line up with the weeks the streak counts.

A cell is filled when an entry exists for its period and muted when none does, **whatever the goal's polarity**. On a positive goal that reads as periods you showed up; on a negative one, periods you lapsed. Both are recorded the same way, so both are drawn the same way. The grid is read-only: there is no year navigation, and clicking a cell does nothing. Hovering one names its period and whether it holds an entry. When the grid is wider than the space it has, it opens scrolled to its right edge so the most recent periods are the ones on screen.

## How to use it

- Set a goal's `type` to `recurring` and pick a `recurrence` (`daily`, `weekly`, `monthly`, or `annually`) to get a streak.
- Log a check-in through the recurring goal's entry form; check-ins are dated entries with a fixed `value` of `1` that never touch `current_value` (see [Goal Types](/features/goal-types)). Only one check-in is allowed per period; the server rejects a second one for a period already covered.
- Set `polarity` to `negative` on a recurring goal to track "time since last lapse" instead of "periods checked in."

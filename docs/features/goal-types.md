# Goal Types

Every goal has a `type`, set when it's created: `simple`, `quantifiable`, `recurring`, or `multi_step`. The type determines how progress is measured and how a user logs it. There is no setting to turn this system on or off; it's the core data model every goal is built on.

## The four types

| Type | Typical shape | Progress driven by |
| --- | --- | --- |
| `simple` | A goal with no numeric target (e.g. "Write a book") | Manual status change; no `target_value` in practice |
| `quantifiable` | A goal with a numeric target and unit (e.g. "Read 50 books") | `current_value` moving toward `target_value` via logged entries |
| `recurring` | A goal repeated on a cadence (e.g. "Daily meditation") | Dated check-ins, tracked as a streak (see [Streaks](/features/streaks)) |
| `multi_step` | A goal broken into checkpoints | Milestones (see [Milestones](/features/milestones)) |

`quantifiable` is the only type where `target_value` is enforced server-side: `GoalController::store()` adds `'target_value' => 'required|numeric'` to the validation rules specifically when `type === 'quantifiable'`. For the other three types `target_value` is optional and typically left null.

## Progress formula

Progress is exposed as the `progress_percentage` accessor on the `Goal` model (`Goal::progressPercentage()`). As implemented:

- If the goal has no `target_value`, `progress_percentage` is `null`.
- If `target_value` equals `initial_value` (a zero-width range), it returns `100` when the goal is completed, otherwise `0`.
- Otherwise, it computes:

  ```
  ((current_value - initial_value) / (target_value - initial_value)) * 100
  ```

  The result is floored at `0` (never negative) but **not capped at 100**: a goal that overshoots its target can show more than 100% progress.

## Direction

Each goal also has a `direction`: `ascending` or `descending`.

- `ascending`: progress grows as `current_value` rises toward `target_value` (e.g. saving toward a savings goal).
- `descending`: progress grows as `current_value` falls toward `target_value` (e.g. paying down a debt from a starting balance to zero).

The progress formula itself doesn't branch on direction. For a descending goal, `target_value` is below `initial_value`, so both the numerator (`current_value - initial_value`) and denominator (`target_value - initial_value`) end up negative as `current_value` falls, and the ratio still comes out positive and increasing. `direction` is what auto-completion (below) and milestone "reached" checks (see [Milestones](/features/milestones)) key off of.

## initial_value is set automatically

`GoalObserver::creating()` runs before a goal is inserted: if `current_value` is present on the incoming goal, `initial_value` is overwritten to match it, regardless of any `initial_value` submitted in the request. In practice this means `initial_value` always ends up equal to whatever `current_value` the goal is created with, so progress is always measured from that starting point forward.

## Auto-completion

`GoalObserver` also runs on both `creating` and `updating` (when `current_value` changes). If all of the following hold:

- the goal's `status` isn't already `completed`,
- the goal has a `target_value`,
- and either `direction` is `descending` and `current_value <= target_value`, **or** `direction` is `ascending` and `current_value >= target_value`,

then the observer marks the goal completed in the same write: `status = 'completed'` and `completed_at = now()`. No extra save is triggered; the attributes are set on the model before it persists.

Because this depends on `target_value`, it only fires for goals that have one set, which in practice means `quantifiable` goals. `simple`, `recurring`, and `multi_step` goals (which typically have no `target_value`) are completed manually instead, through the goal's "mark complete" action.

## How to use it

- The type is chosen at goal creation and can be changed later through the same edit form/validation rules.
- For `simple`, `quantifiable`, and `multi_step` goals, logging progress means posting an `increment` that's added to `current_value`.
- For `recurring` goals, logging progress means recording a dated check-in instead; `current_value` is never touched, and progress is read from the streak (see [Streaks](/features/streaks)).
- Milestones (see [Milestones](/features/milestones)) can be attached to a goal of any type, but they're the primary structure for `multi_step` goals, which otherwise have no numeric target of their own.

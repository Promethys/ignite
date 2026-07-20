# Milestones

## What it is

A milestone is a checkpoint attached to a goal (`Milestone belongsTo Goal`), used to break a larger goal into steps. Any goal can have milestones, but they're the primary structure for `multi_step` goals, which otherwise carry no numeric target of their own (see [Goal Types](/features/goal-types)). Each milestone has a `title`, an optional `description`, an optional `target_value`, a display `order`, an optional `points_reward`, and a nullable `completed_at`.

## Reached vs completed

These are two distinct, independently computed states on the `Milestone` model:

**`is_reached`** (`Milestone::isReached()`): purely a function of the parent goal's current progress against the milestone's own `target_value`, following the goal's `direction`:

```php
ascending  => goal.current_value >= milestone.target_value
descending => goal.current_value <= milestone.target_value
```

This is recomputed live every time it's read; it has no persisted state and doesn't require the milestone to be explicitly marked as anything. A milestone with no `target_value` can't evaluate this comparison the same way (there's no third branch in the `match`), so `is_reached` is only meaningful for milestones that have a `target_value`.

**`is_completed`** (`Milestone::isCompleted()`): based entirely on the stored `completed_at` timestamp:

```php
! empty(completed_at) && completed_at->isPast()
```

This is a separate, explicit state: a milestone becomes completed only when something sets `completed_at` (via `Milestone::markAsCompleted()`, which sets it to `now()`), not automatically just because the goal's progress crossed the milestone's `target_value`. Note the `isPast()` check: as implemented, a milestone whose `completed_at` is somehow set to a future timestamp would report `is_reached` correctly but `is_completed` as `false` until that timestamp passes.

In short: `is_reached` describes whether the goal's numbers currently satisfy the milestone; `is_completed` describes whether the milestone was actually marked done.

## Timeline display

Milestones for a goal are loaded ordered by `order` (`GoalController::show` eager-loads `milestones` with `orderBy('order', 'asc')`) and rendered as a vertical timeline (`Timeline.vue`) on the goal's page:

- Each milestone shows a circular indicator: a checkmark when completed, a partial ring showing live progress toward its own `target_value` when it has one and isn't complete yet, or a plain dot otherwise.
- The first milestone that isn't yet showing as completed, in order, is highlighted as "next up."
- A milestone tied to a `target_value` displays that value and the goal's `unit`, plus a note that it will show as complete once the goal's progress reaches it.
- A milestone with `completed_at` set shows the date it was completed, struck through.
- The timeline treats `is_completed` and "target value reached" as the same completed visual state: a milestone with a `target_value` gets the checkmark, strikethrough, and success styling as soon as the goal's progress reaches that value, independently of whether `completed_at` is set. So a milestone can look done in the timeline before (or without) ever being explicitly marked complete.
- New milestones can be added inline from the end of the timeline.

## How to use it

- Add milestones to a goal through the milestone form (`MilestoneController::store`), which appends the new milestone at `(current max order) + 1`.
- Milestones can be updated or deleted (`update`/`destroy`), both scoped to the milestone's own goal; a request that targets a milestone belonging to a different goal is rejected.
- A milestone can be marked complete explicitly through `MilestoneController::complete`, which calls `markAsCompleted()` and sets `completed_at` to now. This is the only way `is_completed` becomes `true`; reaching a milestone's `target_value` on its own does not set `completed_at`. The timeline's completed styling, however, is driven by either signal (see [Timeline display](#timeline-display)), so a milestone can visually check off in the UI purely from reaching its `target_value`, ahead of any explicit completion.

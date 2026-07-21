# Milestones

## What it is

A milestone is a checkpoint attached to a goal (`Milestone belongsTo Goal`), used to break a larger goal into steps. Any goal can have milestones, but they're the primary structure for `multi_step` goals, which otherwise carry no numeric target of their own (see [Goal Types](/features/goal-types)). Each milestone has a `title`, an optional `description`, an optional `target_value`, a display `order`, an optional `points_reward`, and a nullable `completed_at`. In the UI, a `multi_step` goal labels its milestones as "steps"; a step is simply a milestone with no `target_value`.

## Reached vs completed

These are two distinct, independently computed states on the `Milestone` model:

**`is_reached`** (`Milestone::isReached()`): purely a function of the parent goal's current progress against the milestone's own `target_value`, following the goal's `direction`:

```php
target_value === null => false   // e.g. a multi_step goal's steps
ascending             => goal.current_value >= milestone.target_value
descending            => goal.current_value <= milestone.target_value
```

This is recomputed live every time it's read; it has no persisted state and doesn't require the milestone to be explicitly marked as anything. A milestone with no `target_value` returns `false` for `is_reached` (the accessor guards the null case explicitly), so `is_reached` is only meaningful for milestones that carry a `target_value`. Steps on a `multi_step` goal have no target, so they are never auto-reached and become complete only by being explicitly marked done.

**`is_completed`** (`Milestone::isCompleted()`): based entirely on the stored `completed_at` timestamp:

```php
! empty(completed_at) && completed_at->isPast()
```

This is a separate, explicit state: a milestone becomes completed only when something sets `completed_at` (via `Milestone::markAsCompleted()`, which sets it to `now()`), not automatically just because the goal's progress crossed the milestone's `target_value`. Note the `isPast()` check: as implemented, a milestone whose `completed_at` is somehow set to a future timestamp would report `is_reached` correctly but `is_completed` as `false` until that timestamp passes.

In short: `is_reached` describes whether the goal's numbers currently satisfy the milestone; `is_completed` describes whether the milestone was actually marked done.

## Timeline display

Milestones for a goal are loaded ordered by `order` (`GoalController::show` eager-loads `milestones` with `orderBy('order', 'asc')`) and rendered as a vertical timeline (`Timeline.vue`) on the goal's page. The indicator depends on whether the milestone has a `target_value`:

- A milestone **with** a `target_value` shows a non-interactive circular indicator: a checkmark once reached or completed, otherwise a partial ring showing live progress toward that value. It completes on its own once progress reaches the target, so it is not clickable.
- A **step** (a milestone with no `target_value`, used by `multi_step` goals) shows an interactive checkbox: empty at rest, previewing a check on hover; click it to mark the step complete. A completed step shows a check and, on hover, an undo icon; clicking it again marks it incomplete.
- The first milestone that isn't yet showing as completed, in order, is highlighted as "next up."
- A milestone tied to a `target_value` displays that value and the goal's `unit`.
- A milestone with `completed_at` set shows the date it was completed, struck through.
- For a milestone with a `target_value`, the completed visual state is driven by either signal (`is_completed` or `is_reached`): it gets the checkmark, strikethrough, and success styling as soon as the goal's progress reaches that value, independently of whether `completed_at` is set. A step has no target, so its completed state comes solely from explicit completion.
- New milestones can be added inline from the end of the timeline. On a `multi_step` goal, a help tooltip next to the "Steps" heading explains that clicking a step toggles it.

## How to use it

- Add milestones to a goal through the milestone form (`MilestoneController::store`), which appends the new milestone at `(current max order) + 1`.
- Milestones can be updated or deleted (`update`/`destroy`), both scoped to the milestone's own goal; a request that targets a milestone belonging to a different goal is rejected.
- A milestone can be marked complete through `MilestoneController::complete` (`markAsCompleted()` sets `completed_at` to now) and marked incomplete again through `MilestoneController::uncomplete` (`markAsIncomplete()` clears it). For a `multi_step` goal's steps, both are driven straight from the timeline by clicking the step's checkbox. Setting `completed_at` is the only way `is_completed` becomes `true`; reaching a milestone's `target_value` on its own does not set `completed_at`, though for a targeted milestone the timeline's completed styling is driven by either signal (see [Timeline display](#timeline-display)).

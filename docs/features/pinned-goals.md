# Pinned Goals

## What it is

Pinning curates the dashboard's "Your goals" card. A user can pin up to `Goal::MAX_PINNED` (6) goals; pinned goals always show on the dashboard, in the user's chosen order, ahead of anything the dashboard picks on its own. A pin is stored as a nullable `pin_order` column on `goals`: `null` means not pinned, `1..n` is the position, and positions are kept gapless per user. An index on `(user_id, pin_order)` backs the dashboard query.

## What the dashboard shows

`DashboardController::index` loads two lists for the card: the user's pinned goals ordered by `pin_order`, then other goals filling the remaining slots up to `Goal::MAX_PINNED`. The fill considers only goals that aren't pinned, have `status = 'in_progress'` and no `completed_at`, and orders them by `updated_at` descending, breaking ties by descending id. The pinned query has no status filter: completing or pausing a pinned goal doesn't unpin it, and it keeps showing on the dashboard.

## Pinning, unpinning, deleting

The logic lives in `GoalService`, and every action authorizes through the `update` gate, so only the goal's owner can pin it.

- `GoalService::pin` appends the goal at `(current max pin_order) + 1` inside a transaction that locks the user's row, so two concurrent pins can't push the count past the limit. Pinning an already-pinned goal is a no-op. When the user already has `Goal::MAX_PINNED` pins it returns `false`, and `GoalController::pin` flashes the error toast "You can pin up to 6 goals. Unpin one first." (the limit message lives in `lang/en/toasts.php` and `lang/fr/toasts.php`).
- `GoalService::unpin` sets `pin_order` to null and renumbers the remaining pins from 1.
- `GoalService::delete` also renumbers the remaining pins, so deleting a pinned goal frees its slot without leaving a gap.

## Reordering

`GoalService::reorderPins` takes the ordered ids of every pinned goal and writes positions `1..n` in one transaction. `ReorderPinnedGoalsRequest` validates that `goals` is an array sized exactly to the user's current pin count, whose entries are distinct ids of the user's own pinned goals, so a reorder always carries the full set.

## Routes

| Route                        | Name                | Handler                                                                    |
| ---------------------------- | ------------------- | -------------------------------------------------------------------------- |
| `PATCH /goals/{goal}/pin`    | `goals.pin`         | `GoalController::pin`                                                       |
| `PATCH /goals/{goal}/unpin`  | `goals.unpin`       | `GoalController::unpin`                                                     |
| `PATCH /goals/pins/reorder`  | `goals.pins.reorder`| `GoalController::reorderPins`, body `goals`: ordered ids of all pinned goals |

## How to use it

- Pin or unpin from the "..." menu on a goal card (`BaseGoalCard`, used by both the goals list and the dashboard) or from the same menu on the goal's page.
- Reorder from the dashboard: each pinned card carries a grip handle (SortableJS through `useReorderableList`), which also moves the goal with the up and down arrow keys when focused. Moves update the list immediately and send the full new order to `goals.pins.reorder` after a short pause; the list reverts to the server order if the request fails.
- Pins aren't exposed through the MCP tools; they're managed in the app's UI only.

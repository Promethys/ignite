<?php

namespace Tests\Feature\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PinnedGoalsTest extends TestCase
{
    use RefreshDatabase;

    private function goal(User $user, ?int $position = null, array $attributes = []): Goal
    {
        $goal = Goal::factory()->create(array_merge([
            'user_id' => $user->id, 'category_id' => null, 'type' => 'simple',
            'status' => 'in_progress', 'completed_at' => null, 'current_value' => 0,
        ], $attributes));
        Goal::whereKey($goal->id)->toBase()->update(['pin_order' => $position]);

        return $goal->fresh();
    }

    public function test_pin_appends_and_preserves_completed_and_paused_goals()
    {
        $user = User::factory()->create();
        $first = $this->goal($user, 1);
        $completed = $this->goal($user, null, ['status' => 'completed', 'completed_at' => now()]);
        $paused = $this->goal($user, null, ['status' => 'paused']);

        $this->actingAs($user)->patch(route('goals.pin', $completed))
            ->assertRedirect()->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', __('toasts.goal.pinned'));
        $this->actingAs($user)->patch(route('goals.pin', $paused))->assertRedirect();
        $this->assertSame(1, $first->fresh()->pin_order);
        $this->assertSame(2, $completed->fresh()->pin_order);
        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertSame(3, $paused->fresh()->pin_order);
        $this->assertSame('paused', $paused->fresh()->status);
    }

    public function test_pinning_an_existing_pin_is_a_no_op_and_a_pin_past_the_limit_is_refused()
    {
        $user = User::factory()->create();
        $pins = collect(range(1, Goal::MAX_PINNED))->map(fn ($position) => $this->goal($user, $position));
        $extra = $this->goal($user);
        $this->actingAs($user)->patch(route('goals.pin', $pins[1]))
            ->assertInertiaFlash('toast.type', 'success');
        $this->actingAs($user)->patch(route('goals.pin', $extra))
            ->assertInertiaFlash('toast.type', 'error')
            ->assertInertiaFlash('toast.message', __('toasts.goal.pin_limit', ['max' => Goal::MAX_PINNED]));
        foreach ($pins as $index => $goal) {
            $this->assertSame($index + 1, $goal->fresh()->pin_order);
        }
        $this->assertNull($extra->fresh()->pin_order);
    }

    public function test_guests_and_other_users_cannot_pin_or_unpin_a_goal()
    {
        $owner = User::factory()->create();
        $goal = $this->goal($owner, 1);
        foreach (['goals.pin', 'goals.unpin'] as $route) {
            $this->patch(route($route, $goal))->assertRedirect(route('login'));
        }
        $other = User::factory()->create();
        foreach (['goals.pin', 'goals.unpin'] as $route) {
            $this->actingAs($other)->patch(route($route, $goal))->assertForbidden();
        }
        $this->assertSame(1, $goal->fresh()->pin_order);
    }

    public function test_unpin_and_delete_close_gaps()
    {
        $user = User::factory()->create();
        $first = $this->goal($user, 1);
        $second = $this->goal($user, 2);
        $third = $this->goal($user, 3);
        $this->actingAs($user)->patch(route('goals.unpin', $second))->assertRedirect();
        $this->assertNull($second->fresh()->pin_order);
        $this->assertSame(1, $first->fresh()->pin_order);
        $this->assertSame(2, $third->fresh()->pin_order);
        $this->actingAs($user)->delete(route('goals.destroy', $first))->assertRedirect();
        $this->assertDatabaseMissing('goals', ['id' => $first->id]);
        $this->assertSame(1, $third->fresh()->pin_order);
    }

    public function test_reorder_applies_the_requested_order()
    {
        $user = User::factory()->create();
        $first = $this->goal($user, 1);
        $second = $this->goal($user, 2);
        $third = $this->goal($user, 3);
        $this->actingAs($user)->patch(route('goals.pins.reorder'), ['goals' => [$third->id, $first->id, $second->id]])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $third->fresh()->pin_order);
        $this->assertSame(2, $first->fresh()->pin_order);
        $this->assertSame(3, $second->fresh()->pin_order);
    }

    public function test_reorder_rejects_invalid_lists_without_changing_positions()
    {
        $user = User::factory()->create();
        $first = $this->goal($user, 1);
        $second = $this->goal($user, 2);
        $unpinned = $this->goal($user);
        $foreign = $this->goal(User::factory()->create(), 1);
        foreach ([
            [],
            ['goals' => [$first->id]],
            ['goals' => [$first->id, $second->id, $unpinned->id]],
            ['goals' => [$first->id, $first->id]],
            ['goals' => [$first->id, $foreign->id]],
            ['goals' => [$first->id, $unpinned->id]],
            ['goals' => [$first->id, 'invalid']],
        ] as $payload) {
            $this->actingAs($user)->patch(route('goals.pins.reorder'), $payload)->assertSessionHasErrors();
            $this->assertSame(1, $first->fresh()->pin_order);
            $this->assertSame(2, $second->fresh()->pin_order);
            $this->assertNull($unpinned->fresh()->pin_order);
            $this->assertSame(1, $foreign->fresh()->pin_order);
        }
    }

    public function test_pin_unpin_and_reorder_do_not_touch_timestamps_or_fire_goal_update_events()
    {
        $this->travelTo(now()->startOfSecond()->subDays(2));
        $user = User::factory()->create();
        $first = $this->goal($user, 1);
        $second = $this->goal($user, 2);
        $third = $this->goal($user);
        $timestamp = $first->updated_at->toDateTimeString();
        $this->travelTo(now()->addDay());
        Event::fake(['eloquent.updating: '.Goal::class, 'eloquent.updated: '.Goal::class]);
        $this->actingAs($user)->patch(route('goals.pin', $third))->assertRedirect();
        $this->actingAs($user)->patch(route('goals.pins.reorder'), ['goals' => [$third->id, $second->id, $first->id]])->assertSessionHasNoErrors();
        $this->actingAs($user)->patch(route('goals.unpin', $second))->assertRedirect();
        foreach ([$first, $second, $third] as $goal) {
            $this->assertSame($timestamp, $goal->fresh()->updated_at->toDateTimeString());
        }
        Event::assertNotDispatched('eloquent.updating: '.Goal::class);
        Event::assertNotDispatched('eloquent.updated: '.Goal::class);
        $this->assertSame(1, $third->fresh()->pin_order);
        $this->assertSame(2, $first->fresh()->pin_order);
        $this->assertNull($second->fresh()->pin_order);
    }
}

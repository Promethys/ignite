<?php

namespace Tests\Feature\Services\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use App\Services\Goals\GoalService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoalService $service;

    /** @var array<string, mixed> */
    private array $validAttributes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoalService::class);

        // A minimal, valid goal payload. The service does not validate; the
        // caller does. These fields satisfy the database and exercise the
        // service's own invariants (owner + order).
        $this->validAttributes = [
            'title' => 'Learn Rust',
            'description' => 'Work through the book',
            'type' => 'simple',
            'direction' => 'ascending',
            'current_value' => 0,
            'target_value' => null,
            'status' => 'not_started',
            'priority' => 'medium',
            'is_public' => false,
            'points' => 0,
        ];
    }

    public function test_list_for_user_returns_only_the_actors_goals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Goal::factory()->count(2)->create(['user_id' => $owner->id]);
        Goal::factory()->create(['user_id' => $other->id]);

        $result = $this->service->listForUser($owner);
        $goals = $result['goals'];

        $this->assertCount(2, $goals);
        $this->assertSame(2, $result['total']);
        $this->assertNull($result['limit']);
        $this->assertTrue($goals->every(fn (Goal $goal) => $goal->user_id === $owner->id));
    }

    public function test_list_for_user_appends_streak(): void
    {
        $owner = User::factory()->create();

        Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
        ]);

        $goals = $this->service->listForUser($owner)['goals'];

        $this->assertArrayHasKey('streak', $goals->first()->toArray());
    }

    public function test_find_returns_the_goal_with_relations_and_streak_for_the_owner(): void
    {
        $owner = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
        ]);

        GoalEntry::factory()->count(3)->create(['goal_id' => $goal->id]);
        Milestone::factory()->create(['goal_id' => $goal->id]);

        $loaded = $this->service->find($owner, $goal->id);

        $this->assertTrue($loaded->is($goal));
        $this->assertTrue($loaded->relationLoaded('entries'));
        $this->assertTrue($loaded->relationLoaded('milestones'));
        $this->assertArrayHasKey('streak', $loaded->toArray());
    }

    public function test_find_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->find($intruder, $goal->id);
    }

    public function test_find_caps_entries_at_twenty_and_orders_by_date_desc(): void
    {
        $owner = User::factory()->create();

        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        GoalEntry::factory()->count(25)->create(['goal_id' => $goal->id]);

        $loaded = $this->service->find($owner, $goal);

        $entries = $loaded->entries;

        $this->assertCount(20, $entries);

        $entryDates = $entries->pluck('entry_date')->all();

        // Entries must come back newest-first. Compare each pair so ties (same
        // day) do not make a strict equality check flaky.
        for ($i = 1; $i < count($entryDates); $i++) {
            $this->assertGreaterThanOrEqual(
                0,
                $entryDates[$i - 1]->getTimestamp() <=> $entryDates[$i]->getTimestamp(),
            );
        }
    }

    public function test_find_orders_milestones_by_order_ascending(): void
    {
        $owner = User::factory()->create();

        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        Milestone::factory()->create(['goal_id' => $goal->id, 'order' => 30]);
        Milestone::factory()->create(['goal_id' => $goal->id, 'order' => 10]);
        Milestone::factory()->create(['goal_id' => $goal->id, 'order' => 20]);

        $loaded = $this->service->find($owner, $goal);

        $this->assertSame([10, 20, 30], $loaded->milestones->pluck('order')->all());
    }

    public function test_find_accepts_a_resolved_model_without_a_second_lookup(): void
    {
        $owner = User::factory()->create();

        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $loaded = $this->service->find($owner, $goal);

        // The same model instance must be returned when one is handed in, so a
        // route-model-bound controller never pays for a second fetch.
        $this->assertSame(spl_object_id($goal), spl_object_id($loaded));
    }

    public function test_find_throws_model_not_found_for_a_missing_id(): void
    {
        $owner = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service->find($owner, 999999);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function test_create_assigns_the_actor_as_owner_and_computes_order(): void
    {
        $actor = User::factory()->create();

        $goal = $this->service->create($actor, $this->validAttributes);

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'user_id' => $actor->id,
            'title' => 'Learn Rust',
            'order' => 1,
        ]);
    }

    public function test_create_increments_order_per_existing_goal(): void
    {
        $actor = User::factory()->create();

        Goal::factory()->count(2)->create(['user_id' => $actor->id]);

        $goal = $this->service->create($actor, $this->validAttributes);

        $this->assertSame(3, $goal->order);
    }

    public function test_create_ignores_a_client_supplied_user_id_in_favour_of_the_actor(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();

        $goal = $this->service->create($actor, [
            ...$this->validAttributes,
            'user_id' => $other->id,
        ]);

        $this->assertSame($actor->id, $goal->user_id);
    }

    public function test_create_order_is_scoped_to_the_actor_not_global(): void
    {
        $actor = User::factory()->create();
        $other = User::factory()->create();

        Goal::factory()->count(3)->create(['user_id' => $other->id]);

        $goal = $this->service->create($actor, $this->validAttributes);

        // The other user's goals must not inflate this actor's order.
        $this->assertSame(1, $goal->order);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_update_persists_changes_for_the_owner(): void
    {
        $actor = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $actor->id, 'title' => 'Old']);

        $updated = $this->service->update($actor, $goal, ['title' => 'New']);

        $this->assertSame('New', $updated->fresh()->title);
    }

    public function test_update_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->update($intruder, $goal, ['title' => 'Hijacked']);
    }

    // =========================================================================
    // COMPLETE
    // =========================================================================

    public function test_complete_marks_the_goal_completed(): void
    {
        $actor = User::factory()->create();
        $goal = Goal::factory()->inProgress()->create(['user_id' => $actor->id]);

        $this->service->complete($actor, $goal);

        $fresh = $goal->fresh();

        $this->assertSame('completed', $fresh->status);
        $this->assertNotNull($fresh->completed_at);
    }

    public function test_complete_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->inProgress()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->complete($intruder, $goal);
    }

    // =========================================================================
    // UNCOMPLETE
    // =========================================================================

    public function test_uncomplete_resets_status_and_clears_completed_at(): void
    {
        $actor = User::factory()->create();
        $goal = Goal::factory()->completed()->create(['user_id' => $actor->id]);

        $this->service->uncomplete($actor, $goal, 'paused');

        $fresh = $goal->fresh();

        $this->assertSame('paused', $fresh->status);
        $this->assertNull($fresh->completed_at);
    }

    public function test_uncomplete_does_not_re_complete_an_at_target_goal(): void
    {
        // An ascending goal sitting at its target would be re-completed by the
        // observer on a normal update. uncomplete runs without events, so the
        // revert must stick.
        $actor = User::factory()->create();
        $goal = Goal::factory()->completed()->create([
            'user_id' => $actor->id,
            'type' => 'quantifiable',
            'direction' => 'ascending',
            'target_value' => 100,
            'current_value' => 100,
        ]);

        $this->service->uncomplete($actor, $goal, 'in_progress');

        $fresh = $goal->fresh();

        $this->assertSame('in_progress', $fresh->status);
        $this->assertNull($fresh->completed_at);
    }

    public function test_uncomplete_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->completed()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->uncomplete($intruder, $goal, 'paused');
    }

    // =========================================================================
    // SET STATUS
    // =========================================================================

    public function test_set_status_changes_the_goal_status(): void
    {
        $actor = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $actor->id, 'status' => 'not_started']);

        $this->service->setStatus($actor, $goal, 'in_progress');

        $this->assertSame('in_progress', $goal->fresh()->status);
    }

    public function test_set_status_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->setStatus($intruder, $goal, 'in_progress');
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function test_delete_removes_the_goal_for_the_owner(): void
    {
        $actor = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $actor->id]);

        $this->service->delete($actor, $goal);

        $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
    }

    public function test_delete_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->delete($intruder, $goal);
    }
}

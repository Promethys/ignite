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

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoalService::class);
    }

    public function test_list_for_user_returns_only_the_actors_goals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Goal::factory()->count(2)->create(['user_id' => $owner->id]);
        Goal::factory()->create(['user_id' => $other->id]);

        $goals = $this->service->listForUser($owner);

        $this->assertCount(2, $goals);
        $this->assertTrue($goals->every(fn (Goal $goal) => $goal->user_id === $owner->id));
    }

    public function test_list_for_user_appends_streak(): void
    {
        $owner = User::factory()->create();

        Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
        ]);

        $goals = $this->service->listForUser($owner);

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
}

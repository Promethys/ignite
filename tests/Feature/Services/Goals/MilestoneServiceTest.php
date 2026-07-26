<?php

namespace Tests\Feature\Services\Goals;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use App\Services\Goals\MilestoneService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilestoneServiceTest extends TestCase
{
    use RefreshDatabase;

    private MilestoneService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(MilestoneService::class);
    }

    public function test_add_creates_a_milestone_appended_after_the_existing_ones(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        Milestone::factory()->create(['goal_id' => $goal->id, 'order' => 4]);

        $milestone = $this->service->add($owner, $goal, ['title' => 'Checkpoint']);

        $this->assertSame('Checkpoint', $milestone->title);
        $this->assertSame(5, $milestone->order);
        $this->assertSame($goal->id, $milestone->goal_id);
    }

    public function test_add_assigns_order_one_on_a_goal_without_milestones(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $milestone = $this->service->add($owner, $goal, ['title' => 'First']);

        $this->assertSame(1, $milestone->order);
    }

    public function test_add_overrides_a_client_supplied_order(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $milestone = $this->service->add($owner, $goal, ['title' => 'Sneaky order', 'order' => 99]);

        $this->assertSame(1, $milestone->order);
    }

    public function test_add_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->add($intruder, $goal, ['title' => 'Sneaky']);
    }

    public function test_complete_sets_the_completion_time(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id, 'completed_at' => null]);

        $this->service->complete($owner, $milestone);

        $this->assertNotNull($milestone->fresh()->completed_at);
    }

    public function test_complete_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->complete($intruder, $milestone);
    }
}

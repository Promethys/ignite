<?php

namespace Tests\Feature\Services\Goals;

use App\Models\Goal;
use App\Models\Milestone;
use App\Models\User;
use App\Services\Goals\MilestoneService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    public function test_find_returns_the_milestone_for_the_goal_owner(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        $found = $this->service->find($owner, $milestone->id);

        $this->assertTrue($found->is($milestone));
    }

    public function test_find_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->find($intruder, $milestone->id);
    }

    public function test_find_throws_model_not_found_for_a_missing_id(): void
    {
        $owner = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service->find($owner, 999999);
    }

    public function test_find_returns_the_same_instance_when_given_a_resolved_model(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $milestone = Milestone::factory()->create(['goal_id' => $goal->id]);

        $found = $this->service->find($owner, $milestone);

        $this->assertSame(spl_object_id($milestone), spl_object_id($found));
    }

    public function test_add_creates_a_milestone_appended_after_the_existing_ones(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'multi_step']);
        Milestone::factory()->create(['goal_id' => $goal->id, 'order' => 4]);

        $milestone = $this->service->add($owner, $goal, ['title' => 'Checkpoint']);

        $this->assertSame('Checkpoint', $milestone->title);
        $this->assertSame(5, $milestone->order);
        $this->assertSame($goal->id, $milestone->goal_id);
    }

    public function test_add_assigns_order_one_on_a_goal_without_milestones(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'multi_step']);

        $milestone = $this->service->add($owner, $goal, ['title' => 'First']);

        $this->assertSame(1, $milestone->order);
    }

    public function test_add_overrides_a_client_supplied_order(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'multi_step']);

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

    public function test_add_keeps_quantifiable_milestones_in_ascending_target_order(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'quantifiable', 'direction' => 'ascending']);

        $this->service->add($owner, $goal, ['title' => 'Fifty', 'target_value' => 50]);
        $this->service->add($owner, $goal, ['title' => 'Ten', 'target_value' => 10]);
        $this->service->add($owner, $goal, ['title' => 'Untargeted']);
        $this->service->add($owner, $goal, ['title' => 'Thirty', 'target_value' => 30]);

        $this->assertSame(['Ten', 'Thirty', 'Fifty', 'Untargeted'], $goal->milestones()->get()->pluck('title')->all());
        $this->assertSame([1, 2, 3, 4], $goal->milestones()->pluck('order')->all());
    }

    public function test_add_keeps_quantifiable_milestones_in_descending_target_order(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'quantifiable', 'direction' => 'descending']);

        $this->service->add($owner, $goal, ['title' => 'Eighty', 'target_value' => 80]);
        $this->service->add($owner, $goal, ['title' => 'Ninety', 'target_value' => 90]);

        $this->assertSame(['Ninety', 'Eighty'], $goal->milestones()->get()->pluck('title')->all());
    }

    public function test_update_moves_a_quantifiable_milestone_when_its_target_changes(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'quantifiable', 'direction' => 'ascending']);
        $ten = $this->service->add($owner, $goal, ['title' => 'Ten', 'target_value' => 10]);
        $this->service->add($owner, $goal, ['title' => 'Twenty', 'target_value' => 20]);

        $this->service->update($owner, $ten, ['target_value' => 40]);

        $this->assertSame(['Twenty', 'Ten'], $goal->milestones()->get()->pluck('title')->all());
    }

    public function test_add_inserts_a_step_at_the_given_position(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'multi_step']);
        $this->service->add($owner, $goal, ['title' => 'A']);
        $this->service->add($owner, $goal, ['title' => 'C']);

        $this->service->add($owner, $goal, ['title' => 'B'], position: 2);
        $this->service->add($owner, $goal, ['title' => 'Start'], position: 1);

        $this->assertSame(['Start', 'A', 'B', 'C'], $goal->milestones()->get()->pluck('title')->all());
    }

    public function test_reorder_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id, 'type' => 'multi_step']);
        $step = Milestone::factory()->create(['goal_id' => $goal->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->reorder(User::factory()->create(), $goal, [$step->id]);
    }
}

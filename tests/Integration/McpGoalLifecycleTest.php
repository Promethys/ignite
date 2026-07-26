<?php

namespace Tests\Integration;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\CompleteGoalTool;
use App\Mcp\Tools\CreateGoalTool;
use App\Mcp\Tools\DeleteGoalTool;
use App\Mcp\Tools\GetGoalTool;
use App\Mcp\Tools\ListGoalsTool;
use App\Mcp\Tools\LogProgressTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpGoalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function structured(TestResponse $response): array
    {
        $payload = [];

        $response->assertStructuredContent(function (AssertableJson $json) use (&$payload) {
            $payload = $json->toArray();
            $json->etc();
        });

        return $payload;
    }

    public function test_a_goal_can_be_created_progressed_completed_and_deleted_over_mcp(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $created = $this->structured(
            IgniteServer::tool(CreateGoalTool::class, [
                'title' => 'Read 12 books',
                'type' => 'quantifiable',
                'target_value' => 12,
                'unit' => 'books',
            ])->assertOk()
        );

        $goalId = $created['id'];

        $this->assertDatabaseHas('goals', [
            'id' => $goalId,
            'user_id' => $user->id,
            'title' => 'Read 12 books',
            'status' => 'not_started',
        ]);

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goalId,
            'increment' => 5,
            'note' => 'Finished five books',
        ])->assertOk();

        $afterProgress = $this->structured(
            IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goalId])->assertOk()
        );

        $this->assertSame(5.0, (float) $afterProgress['current_value']);
        $this->assertCount(1, $afterProgress['entries']);
        $this->assertArrayNotHasKey('user', $afterProgress);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('The user has 1 goals.');

        IgniteServer::tool(CompleteGoalTool::class, ['goal_id' => $goalId])->assertOk();

        $this->assertDatabaseHas('goals', ['id' => $goalId, 'status' => 'completed']);
        $this->assertNotNull(Goal::find($goalId)->completed_at);

        $preview = $this->structured(
            IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goalId])->assertOk()
        );

        $this->assertTrue($preview['requires_confirmation']);
        $this->assertDatabaseHas('goals', ['id' => $goalId]);

        IgniteServer::tool(DeleteGoalTool::class, [
            'goal_id' => $goalId,
            'confirmation_token' => $preview['confirmation_token'],
        ])->assertOk();

        $this->assertDatabaseMissing('goals', ['id' => $goalId]);
        $this->assertDatabaseMissing('goal_entries', ['goal_id' => $goalId]);
    }

    public function test_a_read_only_token_can_observe_but_never_change_anything(): void
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'current_value' => 5,
            'target_value' => 12,
        ]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)->assertOk();
        IgniteServer::tool(GetGoalTool::class, ['goal_id' => $goal->id])->assertOk();

        IgniteServer::tool(LogProgressTool::class, [
            'goal_id' => $goal->id,
            'increment' => 5,
        ])->assertHasErrors();

        IgniteServer::tool(CompleteGoalTool::class, ['goal_id' => $goal->id])->assertHasErrors();
        IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])->assertHasErrors();

        $this->assertSame(5.0, (float) $goal->fresh()->current_value);
        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'status' => $goal->status]);
    }
}

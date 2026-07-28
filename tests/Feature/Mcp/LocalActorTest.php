<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\DeleteGoalTool;
use App\Mcp\Tools\ListGoalsTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_configured_local_user_may_use_the_tools_without_a_token(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        Goal::factory()->count(2)->create(['user_id' => $user->id]);

        config(['mcp.local_user' => 'owner@example.com']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('The user has 2 goals.');
    }

    public function test_the_local_user_is_matched_case_insensitively(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        Goal::factory()->create(['user_id' => $user->id]);

        config(['mcp.local_user' => 'Owner@Example.COM']);

        IgniteServer::tool(ListGoalsTool::class)->assertOk();
    }

    public function test_the_local_user_has_access_to_every_scope_including_delete(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        config(['mcp.local_user' => 'owner@example.com']);

        IgniteServer::tool(DeleteGoalTool::class, ['goal_id' => $goal->id])
            ->assertOk()
            ->assertSee('This action needs a confirmation');
    }

    public function test_tools_stay_hidden_when_no_local_user_is_configured(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        config(['mcp.local_user' => null]);

        IgniteServer::tool(ListGoalsTool::class)->assertHasErrors();
    }

    public function test_an_unknown_local_user_leaves_the_tools_hidden(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);
        Goal::factory()->create(['user_id' => $user->id]);

        config(['mcp.local_user' => 'nobody@example.com']);

        IgniteServer::tool(ListGoalsTool::class)->assertHasErrors();
    }

    public function test_the_local_user_only_sees_their_own_goals(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.com']);
        $other = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $owner->id]);
        Goal::factory()->count(3)->create(['user_id' => $other->id]);

        config(['mcp.local_user' => 'owner@example.com']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('The user has 2 goals.');
    }
}

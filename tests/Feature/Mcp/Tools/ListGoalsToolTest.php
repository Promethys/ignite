<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\ListGoalsTool;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListGoalsToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_scoped_token_lists_the_actors_goals(): void
    {
        $user = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('The user has 2 goals.');
    }

    public function test_it_lists_only_the_actors_own_goals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Goal::factory()->count(2)->create(['user_id' => $owner->id]);
        Goal::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($owner, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertSee('The user has 2 goals.');
    }

    public function test_a_token_without_the_read_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['delete']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertHasErrors();
    }

    public function test_structured_content_is_wrapped_in_a_goals_object(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(ListGoalsTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json->has('goals')->etc());
    }

    public function test_a_local_session_without_a_token_may_use_the_tool(): void
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id]);

        IgniteServer::actingAs($user)
            ->tool(ListGoalsTool::class)
            ->assertOk();
    }
}

<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\GetUserTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetUserToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_token_returns_the_actors_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Lovelace',
            'timezone' => 'Europe/Paris',
            'locale' => 'fr',
        ]);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(GetUserTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('user.name', 'Ada Lovelace')
                ->where('user.timezone', 'Europe/Paris')
                ->where('user.locale', 'fr')
                ->etc());
    }

    public function test_the_payload_never_leaks_email_or_secrets(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(GetUserTool::class)
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('user')
                ->missing('user.email')
                ->missing('user.password')
                ->missing('user.two_factor_secret')
                ->etc());
    }

    public function test_a_token_without_the_read_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['write']);

        IgniteServer::tool(GetUserTool::class)
            ->assertHasErrors();
    }
}

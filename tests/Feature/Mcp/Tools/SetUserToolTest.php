<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\SetUserTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SetUserToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_token_partially_updates_the_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, ['timezone' => 'Europe/Paris'])
            ->assertOk()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('user.timezone', 'Europe/Paris')
                ->etc());

        // Omitted fields keep their value.
        $this->assertSame('Old Name', $user->fresh()->name);
        $this->assertSame('Europe/Paris', $user->fresh()->timezone);
    }

    public function test_email_is_not_mutable_even_if_supplied(): void
    {
        $user = User::factory()->create(['email' => 'original@example.com']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, ['name' => 'New', 'email' => 'hijacked@example.com'])
            ->assertOk();

        $this->assertSame('original@example.com', $user->fresh()->email);
        $this->assertSame('New', $user->fresh()->name);
    }

    public function test_locale_is_constrained_to_supported_languages(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, ['locale' => 'de'])
            ->assertHasErrors();
    }

    public function test_an_invalid_timezone_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, ['timezone' => 'Not/A/Zone'])
            ->assertHasErrors();
    }

    public function test_an_empty_payload_is_rejected(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, [])
            ->assertHasErrors();
    }

    public function test_a_whitespace_only_name_is_rejected(): void
    {
        $user = User::factory()->create(['name' => 'Real Name']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, ['name' => '   '])
            ->assertHasErrors();

        $this->assertSame('Real Name', $user->fresh()->name);
    }

    public function test_a_padded_name_is_stored_trimmed(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(SetUserTool::class, ['name' => '  New Name  '])
            ->assertOk();

        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_a_token_without_the_write_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(SetUserTool::class, ['name' => 'New'])
            ->assertHasErrors();
    }
}

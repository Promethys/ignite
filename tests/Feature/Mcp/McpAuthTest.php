<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class McpAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->actingAsGuest()
            ->postJson('/mcp');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_is_allowed(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this
            ->postJson('/mcp');

        $response->assertSuccessful();
    }
}

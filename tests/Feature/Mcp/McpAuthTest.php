<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SendsMcpRequests;
use Tests\TestCase;

class McpAuthTest extends TestCase
{
    use RefreshDatabase;
    use SendsMcpRequests;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->actingAsGuest()
            ->postMcp('server/discover');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_is_allowed(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postMcp('server/discover')
            ->assertSuccessful()
            ->assertJsonPath('result.supportedVersions', [self::MCP_PROTOCOL_VERSION]);
    }

    public function test_the_tool_list_tells_clients_to_refetch_it(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['read']);

        $this->postMcp('tools/list')
            ->assertSuccessful()
            ->assertJsonPath('result.ttlMs', 0)
            ->assertJsonPath('result.cacheScope', 'private');
    }

    public function test_a_client_on_the_legacy_handshake_is_still_served(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['read']);

        $this->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'legacy-client', 'version' => '1.0.0'],
            ],
        ])
            ->assertSuccessful()
            ->assertJsonPath('result.protocolVersion', '2025-06-18');
    }

    public function test_sanctum_token_abilities_grant_and_deny_by_scope(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read']);

        $this->assertTrue($user->tokenCan('read'));
        $this->assertFalse($user->tokenCan('write'));
    }
}

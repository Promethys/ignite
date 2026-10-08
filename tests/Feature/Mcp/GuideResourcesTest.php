<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Resources\GuideResource;
use App\Mcp\Servers\IgniteServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\SendsMcpRequests;
use Tests\TestCase;

class GuideResourcesTest extends TestCase
{
    use RefreshDatabase;
    use SendsMcpRequests;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create(), ['read']);
    }

    public function test_the_server_lists_one_resource_per_page_of_the_guide(): void
    {
        $resources = $this->postMcp('resources/list')->assertSuccessful()->json('result.resources');

        $this->assertEqualsCanonicalizing(
            [
                'ignite://guide/assistant',
                'ignite://guide/getting-started',
                'ignite://guide/goal-types',
                'ignite://guide/tracking-progress',
            ],
            array_column($resources, 'uri'),
        );
        $this->assertSame(['text/markdown'], array_values(array_unique(array_column($resources, 'mimeType'))));
    }

    public function test_a_client_reads_a_page_by_its_address(): void
    {
        $this->postMcp('resources/read', ['uri' => 'ignite://guide/goal-types'])
            ->assertSuccessful()
            ->assertJsonPath('result.contents.0.uri', 'ignite://guide/goal-types')
            ->assertJsonPath('result.contents.0.text', fn (string $text) => str_starts_with($text, '# Goal Types'));
    }

    public function test_reading_a_resource_returns_the_page(): void
    {
        IgniteServer::resource(new GuideResource('tracking-progress', 'Tracking Progress'))
            ->assertOk()
            ->assertSee('# Tracking Progress');
    }
}

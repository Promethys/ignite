<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\GetHelpTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GetHelpToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_read_token_gets_a_page_of_the_guide(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['read']);

        IgniteServer::tool(GetHelpTool::class, ['topic' => 'goal-types'])
            ->assertOk()
            ->assertSee('Goal Types')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('topic', 'goal-types')
                ->where('title', 'Goal Types')
                ->where('content', fn (string $content) => str_starts_with($content, '# Goal Types'))
            );
    }

    public function test_a_topic_outside_the_guide_is_refused(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['read']);

        IgniteServer::tool(GetHelpTool::class, ['topic' => '../features/encryption'])->assertHasErrors();
        IgniteServer::tool(GetHelpTool::class, [])->assertHasErrors();
    }

    public function test_it_needs_the_read_ability(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['write']);

        IgniteServer::tool(GetHelpTool::class, ['topic' => 'goal-types'])->assertHasErrors();
    }

    public function test_it_is_not_offered_where_the_guide_is_not_installed(): void
    {
        File::partialMock()->shouldReceive('glob')->andReturn([]);
        Sanctum::actingAs(User::factory()->create(), ['read']);

        IgniteServer::tool(GetHelpTool::class, ['topic' => 'goal-types'])->assertHasErrors();
    }
}

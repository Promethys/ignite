<?php

namespace Tests\Feature\Ai;

use App\Ai\AssistantTools;
use App\Ai\Tools\ConfirmedDeletion;
use App\Mcp\Servers\IgniteServer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssistantToolsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function toolNames(): array
    {
        return collect(app(AssistantTools::class)->all())->map->name()->sort()->values()->all();
    }

    public function test_a_signed_in_user_gets_every_tool_of_the_mcp_server()
    {
        $this->actingAs(User::factory()->create());

        $serverToolNames = collect(IgniteServer::TOOLS)->map(fn (string $tool) => app($tool)->name())->sort()->values()->all();

        $this->assertCount(22, $this->toolNames());
        $this->assertSame($serverToolNames, $this->toolNames());
    }

    public function test_only_the_three_deletions_are_gated_behind_an_approval()
    {
        $this->actingAs(User::factory()->create());

        $gated = collect(app(AssistantTools::class)->all())
            ->filter(fn (object $tool): bool => $tool instanceof ConfirmedDeletion)
            ->map->name()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['delete_category', 'delete_entry', 'delete_goal'], $gated);
    }

    public function test_tools_the_user_may_not_use_are_left_out()
    {
        Sanctum::actingAs(User::factory()->create(), ['read']);

        $names = $this->toolNames();

        $this->assertContains('list_goals', $names);
        $this->assertNotContains('create_goal', $names);
        $this->assertNotContains('delete_goal', $names);
    }

    public function test_a_guest_gets_no_tools()
    {
        $this->assertSame([], app(AssistantTools::class)->all());
    }
}

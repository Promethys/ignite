<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\CreateGoalTool;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateGoalToolTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, mixed> */
    private array $validGoal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validGoal = [
            'title' => 'Read more books',
            'type' => 'simple',
            'direction' => 'ascending',
            'current_value' => 0,
            'status' => 'not_started',
            'priority' => 'medium',
            'points' => 0,
            'is_public' => false,
        ];
    }

    public function test_a_write_token_creates_a_goal_owned_by_the_token_holder(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, [
            ...$this->validGoal,
            // A client-supplied owner id must be ignored; the actor owns the goal.
            'user_id' => $other->id,
        ])->assertOk();

        $this->assertDatabaseHas('goals', [
            'title' => 'Read more books',
            'user_id' => $user->id,
        ]);
    }

    public function test_a_token_without_the_write_ability_cannot_create_a_goal(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(CreateGoalTool::class, $this->validGoal)
            ->assertHasErrors();
    }

    public function test_a_quantifiable_goal_requires_a_target_value(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, [
            ...$this->validGoal,
            'type' => 'quantifiable',
        ])->assertHasErrors();

        $this->assertDatabaseMissing('goals', ['user_id' => $user->id]);
    }

    public function test_a_quantifiable_goal_with_a_target_value_is_created(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, [
            ...$this->validGoal,
            'type' => 'quantifiable',
            'target_value' => 50,
            'unit' => 'books',
        ])->assertOk();

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'type' => 'quantifiable',
            'target_value' => 50,
        ]);
    }

    public function test_a_goal_can_be_created_from_only_a_title_and_type(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, [
            'title' => 'Meditate daily',
            'type' => 'simple',
        ])->assertOk();

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => 'Meditate daily',
            'type' => 'simple',
            'current_value' => 0,
            'direction' => 'ascending',
            'status' => 'not_started',
            'priority' => 'medium',
            'polarity' => 'positive',
            'points' => 0,
            'is_public' => 0,
        ]);
    }

    public function test_the_created_goal_carries_its_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, [
            ...$this->validGoal,
            'category_id' => $category->id,
        ])
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('category.id', $category->id)
                ->where('category.name', $category->name)
                ->etc());
    }

    public function test_a_goal_cannot_be_created_in_another_users_category(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $foreignCategory = Category::factory()->for($stranger)->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, [
            ...$this->validGoal,
            'category_id' => $foreignCategory->id,
        ])->assertHasErrors();

        $this->assertDatabaseMissing('goals', [
            'user_id' => $user->id,
            'category_id' => $foreignCategory->id,
        ]);
    }

    public function test_the_created_goal_carries_a_null_category_when_it_has_none(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, $this->validGoal)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->where('category', null)
                ->etc());
    }

    public function test_the_returned_goal_never_leaks_the_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(CreateGoalTool::class, $this->validGoal)
            ->assertOk()
            ->assertStructuredContent(fn ($json) => $json
                ->missing('user')
                ->missing('user_id')
                ->has('id')
                ->etc());
    }
}

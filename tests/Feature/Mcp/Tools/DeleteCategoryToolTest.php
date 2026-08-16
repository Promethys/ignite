<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\DeleteCategoryTool;
use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteCategoryToolTest extends TestCase
{
    use RefreshDatabase;

    private function confirmationTokenFrom(TestResponse $response): string
    {
        $token = null;

        $response->assertStructuredContent(function (AssertableJson $json) use (&$token) {
            $token = $json->toArray()['confirmation_token'] ?? null;
            $json->etc();
        });

        return (string) $token;
    }

    public function test_the_first_call_previews_the_effect_and_deletes_nothing(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Fitness']);
        Goal::factory()->count(2)->create(['user_id' => $user->id, 'category_id' => $category->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteCategoryTool::class, ['category_id' => $category->id])
            ->assertOk()
            ->assertSee("the category 'Fitness'")
            ->assertSee('become uncategorised (2 affected)')
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('requires_confirmation', true)
                ->has('confirmation_token')
                ->where('preview', fn (string $preview) => str_contains($preview, '(2 affected)'))
                ->etc());

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_a_confirmed_call_deletes_the_category_and_keeps_its_goals(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $goal = Goal::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteCategoryTool::class, ['category_id' => $category->id])
        );

        IgniteServer::tool(DeleteCategoryTool::class, [
            'category_id' => $category->id,
            'confirmation_token' => $token,
        ])->assertOk();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('goals', ['id' => $goal->id, 'category_id' => null]);
    }

    public function test_a_confirmation_token_works_only_once(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteCategoryTool::class, ['category_id' => $category->id])
        );

        IgniteServer::tool(DeleteCategoryTool::class, [
            'category_id' => $category->id,
            'confirmation_token' => $token,
        ])->assertOk();

        $other = Category::factory()->create(['user_id' => $user->id]);

        IgniteServer::tool(DeleteCategoryTool::class, [
            'category_id' => $other->id,
            'confirmation_token' => $token,
        ])->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $other->id]);
    }

    public function test_an_invalid_confirmation_token_does_not_delete(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteCategoryTool::class, [
            'category_id' => $category->id,
            'confirmation_token' => 'not-a-real-token',
        ])->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_a_token_issued_for_one_category_cannot_delete_another(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $otherCategory = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write', 'delete']);

        $token = $this->confirmationTokenFrom(
            IgniteServer::tool(DeleteCategoryTool::class, ['category_id' => $category->id])
        );

        IgniteServer::tool(DeleteCategoryTool::class, [
            'category_id' => $otherCategory->id,
            'confirmation_token' => $token,
        ])->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $otherCategory->id]);
    }

    public function test_a_token_without_the_delete_ability_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(DeleteCategoryTool::class, ['category_id' => $category->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_it_denies_deleting_another_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder, ['read', 'write', 'delete']);

        IgniteServer::tool(DeleteCategoryTool::class, ['category_id' => $category->id])
            ->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}

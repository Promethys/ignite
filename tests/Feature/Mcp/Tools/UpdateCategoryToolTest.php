<?php

namespace Tests\Feature\Mcp\Tools;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\UpdateCategoryTool;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateCategoryToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_write_scoped_token_renames_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Fitness']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'name' => 'Health & Fitness',
        ])->assertOk()->assertSee('Updated the category Health & Fitness.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Health & Fitness',
        ]);
    }

    public function test_omitted_fields_keep_their_current_value(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'user_id' => $user->id,
            'name' => 'Fitness',
            'color' => '#22c55e',
            'icon' => 'dumbbell',
        ]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'name' => 'Health',
        ])->assertOk();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Health',
            'color' => '#22c55e',
            'icon' => 'dumbbell',
        ]);
    }

    public function test_a_call_with_no_fields_to_change_is_an_error(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, ['category_id' => $category->id])
            ->assertHasErrors();
    }

    public function test_a_colour_that_is_not_a_six_digit_hex_is_rejected(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'color' => '#22c55e']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'color' => 'not-a-colour',
        ])->assertHasErrors();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'color' => '#22c55e',
        ]);
    }

    public function test_a_read_only_token_cannot_use_the_tool(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Fitness']);

        Sanctum::actingAs($user, ['read']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'name' => 'Health',
        ])->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Fitness']);
    }

    public function test_it_denies_updating_another_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $owner->id, 'name' => 'Fitness']);

        Sanctum::actingAs($intruder, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'name' => 'Hijacked',
        ])->assertHasErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Fitness']);
    }

    public function test_a_supplied_slug_is_ignored(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Fitness']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'name' => 'Health',
            'slug' => 'hijacked',
        ])->assertOk();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Health']);
        $this->assertDatabaseMissing('categories', ['slug' => 'hijacked']);
    }

    public function test_supplying_only_unknown_fields_changes_nothing(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'name' => 'Fitness']);

        Sanctum::actingAs($user, ['read', 'write']);

        IgniteServer::tool(UpdateCategoryTool::class, [
            'category_id' => $category->id,
            'slug' => 'hijacked',
        ])->assertOk();

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Fitness']);
        $this->assertDatabaseMissing('categories', ['slug' => 'hijacked']);
    }
}

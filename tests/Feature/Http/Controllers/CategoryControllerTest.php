<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // =========================================================================
    // AUTHORIZATION
    // =========================================================================

    public function test_guest_is_redirected_to_login()
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
        $this->post(route('categories.store'), ['name' => 'Test'])->assertRedirect(route('login'));
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_user_can_view_their_categories()
    {
        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('items')
            );
    }

    public function test_categories_include_goal_count()
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);
        Goal::factory()->count(3)->create([
            'category_id' => $category->id,
            'user_id' => $this->user->id,
            'current_value' => 0,
        ]);

        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('items', 1)
                ->where('items.0.goals_count', 3)
            );
    }

    public function test_user_does_not_see_other_users_categories()
    {
        Category::factory()->create(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('items', 0)
            );
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function test_user_can_create_a_category()
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'My Category',
            ])
            ->assertRedirectBack()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Category created.');

        $this->assertDatabaseHas('categories', [
            'name' => 'My Category',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_category_name_is_required()
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_user_id_is_set_from_authenticated_user_not_request()
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'Test Category',
                'user_id' => $this->otherUser->id,
            ]);

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'user_id' => $this->user->id,
        ]);
        $this->assertDatabaseMissing('categories', [
            'name' => 'Test Category',
            'user_id' => $this->otherUser->id,
        ]);
    }

    public function test_order_is_auto_assigned_server_side()
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), ['name' => 'First']);

        $category = Category::where('name', 'First')->first();
        $this->assertNotNull($category->order);
        $this->assertGreaterThan(0, $category->order);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_user_can_update_their_category()
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->put(route('categories.update', $category), [
                'name' => 'Updated Name',
            ])
            ->assertRedirect(route('categories.index'))
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Category updated.');

        $this->assertEquals('Updated Name', $category->fresh()->name);
    }

    public function test_user_cannot_update_other_users_category()
    {
        $category = Category::factory()->create(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->user)
            ->put(route('categories.update', $category), [
                'name' => 'Hacked',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_user_can_delete_their_category()
    {
        $category = Category::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect()
            ->assertInertiaFlash('toast.type', 'success')
            ->assertInertiaFlash('toast.message', 'Category deleted.');

        $this->assertModelMissing($category);
    }

    public function test_user_cannot_delete_other_users_category()
    {
        $category = Category::factory()->create(['user_id' => $this->otherUser->id]);

        $this->actingAs($this->user)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();
    }
}

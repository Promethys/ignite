<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_category_belongs_to_user()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
        ]);
        $this->assertInstanceOf(User::class, $category->user);
        $this->assertEquals($user->id, $category->user_id);
    }

    public function test_category_has_many_goals()
    {
        $category = Category::factory()->create();
        $goal = Goal::factory()->create(['category_id' => $category->id]);

        $this->assertCount(1, $category->goals);
        $this->assertTrue($category->goals->contains($goal));
        $this->assertInstanceOf(Goal::class, $category->goals->first());
    }

    // =========================================================================
    // SLUGGABLE TESTS
    // =========================================================================

    public function test_slug_is_generated_from_name_on_creation()
    {
        $category = Category::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Health & Fitness',
        ]);

        $this->assertEquals('health-fitness', $category->slug);
    }

    public function test_slug_is_unique()
    {
        $user = User::factory()->create();

        $category1 = Category::create([
            'user_id' => $user->id,
            'name' => 'Fitness',
        ]);

        $category2 = Category::create([
            'user_id' => $user->id,
            'name' => 'Fitness',
        ]);

        $this->assertNotEquals($category1->slug, $category2->slug);
    }
}

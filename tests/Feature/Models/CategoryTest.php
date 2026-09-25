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
}

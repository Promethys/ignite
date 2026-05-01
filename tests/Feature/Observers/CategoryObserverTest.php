<?php

namespace Tests\Feature\Observers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_is_generated_on_creation()
    {
        $category = Category::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Health & Fitness',
        ]);

        $this->assertEquals('health-fitness', $category->slug);
    }

    public function test_order_is_set_on_creation()
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Fitness',
        ]);

        $this->assertNotNull($category->order);
        $this->assertGreaterThan(0, $category->order);
    }

    public function test_order_increments_for_each_new_category()
    {
        $user = User::factory()->create();

        $cat1 = Category::create(['user_id' => $user->id, 'name' => 'First']);
        $cat2 = Category::create(['user_id' => $user->id, 'name' => 'Second']);
        $cat3 = Category::create(['user_id' => $user->id, 'name' => 'Third']);

        $this->assertEquals(1, $cat1->order);
        $this->assertEquals(2, $cat2->order);
        $this->assertEquals(3, $cat3->order);
    }

    public function test_order_is_scoped_per_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $cat1 = Category::create(['user_id' => $user1->id, 'name' => 'First']);
        $cat2 = Category::create(['user_id' => $user2->id, 'name' => 'Second']);
        $cat3 = Category::create(['user_id' => $user1->id, 'name' => 'Third']);

        $this->assertEquals(1, $cat1->order);
        $this->assertEquals(1, $cat2->order);
        $this->assertEquals(2, $cat3->order);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_branded_404_page_renders_when_debug_is_off()
    {
        config(['app.debug' => false]);

        $this->get('/this-route-does-not-exist')
            ->assertStatus(404)
            ->assertInertia(fn (Assert $page) => $page
                ->component('ErrorPage')
                ->where('status', 404)
            );
    }

    public function test_branded_403_page_renders_when_debug_is_off()
    {
        config(['app.debug' => false]);

        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'current_value' => 0,
        ]);

        $this->actingAs($intruder)
            ->get(route('goals.show', $goal))
            ->assertStatus(403)
            ->assertInertia(fn (Assert $page) => $page
                ->component('ErrorPage')
                ->where('status', 403)
            );
    }

    public function test_default_error_behavior_is_preserved_when_debug_is_on()
    {
        config(['app.debug' => true]);

        $this->get('/this-route-does-not-exist')
            ->assertStatus(404);

        $response = $this->get('/this-route-does-not-exist');

        $this->assertStringNotContainsString(
            'ErrorPage',
            $response->getContent(),
        );
    }
}

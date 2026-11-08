<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('publicPageProvider')]
    public function test_guests_can_visit_public_pages(string $route, string $component): void
    {
        $this->get(route($route))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));

        $this->assertGuest();
    }

    #[DataProvider('publicPageProvider')]
    public function test_authenticated_users_can_visit_public_pages(string $route, string $component): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route($route))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component($component));

        $this->assertAuthenticatedAs($user);
    }

    public static function publicPageProvider(): array
    {
        return [
            'features' => ['features', 'public/Features'],
            'self-host' => ['self-host', 'public/SelfHost'],
            'privacy' => ['privacy', 'legal/Privacy'],
            'terms' => ['terms', 'legal/Terms'],
        ];
    }
}

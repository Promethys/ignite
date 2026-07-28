<?php

namespace Tests\Feature\Mcp;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function requestActingAs(User $user): Request
    {
        $request = Request::create('/mcp', 'POST');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    public function test_the_mcp_rate_limiter_is_defined(): void
    {
        $this->assertNotNull(RateLimiter::limiter('mcp'));
    }

    public function test_an_authenticated_request_passes_through_the_throttle(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['read']);

        $this->postJson('/mcp')->assertSuccessful();
    }

    public function test_requests_are_rejected_once_the_limit_is_exceeded(): void
    {
        RateLimiter::for('mcp', fn () => Limit::perMinute(2)->by('rate-limit-test'));

        Sanctum::actingAs(User::factory()->create(), ['read']);

        $this->postJson('/mcp')->assertSuccessful();
        $this->postJson('/mcp')->assertSuccessful();
        $this->postJson('/mcp')->assertStatus(429);
    }

    public function test_two_tokens_of_the_same_user_get_separate_budgets(): void
    {
        $user = User::factory()->create();
        $limiter = RateLimiter::limiter('mcp');

        $user->withAccessToken($user->createToken('first', ['read'])->accessToken);
        $firstKey = $limiter($this->requestActingAs($user))->key;

        $user->withAccessToken($user->createToken('second', ['read'])->accessToken);
        $secondKey = $limiter($this->requestActingAs($user))->key;

        $this->assertNotSame($firstKey, $secondKey);
    }

    public function test_a_session_authenticated_request_falls_back_to_the_user_key(): void
    {
        $user = User::factory()->create();
        $limiter = RateLimiter::limiter('mcp');

        $key = $limiter($this->requestActingAs($user))->key;

        $this->assertSame('user:'.$user->id, $key);
    }
}

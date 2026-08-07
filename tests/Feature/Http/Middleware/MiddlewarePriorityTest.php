<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The web stack carries ordering requirements that nothing else enforces.
 *
 * SetLocale reads the authenticated user and HandleInertiaRequests publishes
 * the resolved locale, so the two are order-dependent in a way that produces
 * a silently wrong locale rather than an error when it breaks.
 */
class MiddlewarePriorityTest extends TestCase
{
    /**
     * @return array<int, string>
     */
    protected function resolvedStackFor(string $routeName): array
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->firstOrFail(fn ($route) => $route->getName() === $routeName);

        return array_values(array_filter(
            Route::gatherRouteMiddleware($route),
            fn ($middleware) => is_string($middleware)
        ));
    }

    public function test_set_locale_resolves_before_inertia_publishes_the_shared_props()
    {
        $stack = $this->resolvedStackFor('dashboard');

        $this->assertLessThan(
            array_search(HandleInertiaRequests::class, $stack, true),
            array_search(SetLocale::class, $stack, true),
        );
    }

    public function test_the_session_starts_before_the_locale_is_resolved()
    {
        $stack = $this->resolvedStackFor('dashboard');

        $this->assertLessThan(
            array_search(SetLocale::class, $stack, true),
            array_search(StartSession::class, $stack, true),
        );
    }

    public function test_the_framework_ordering_guarantees_are_kept()
    {
        $stack = $this->resolvedStackFor('dashboard');

        $this->assertLessThan(
            array_search(SubstituteBindings::class, $stack, true),
            array_search(Authenticate::class, $stack, true),
        );
    }
}

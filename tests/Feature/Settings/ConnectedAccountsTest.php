<?php

namespace Tests\Feature\Settings;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class ConnectedAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-test-client-id',
            'services.github.client_id' => 'github-test-client-id',
        ]);
    }

    public function test_the_page_requires_authentication()
    {
        $this->get(route('connected-accounts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_page_lists_the_connected_providers()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create([
            'provider' => 'google',
            'provider_data' => ['email' => 'jane@example.com'],
        ]);

        $this->actingAs($user)
            ->get(route('connected-accounts.index'))
            ->assertInertia(fn ($page) => $page
                ->component('settings/ConnectedAccounts')
                ->has('connectedProviders', 1)
                ->where('connectedProviders.0.provider', 'google')
                ->where('connectedProviders.0.provider_email', 'jane@example.com')
            );
    }

    public function test_the_page_reports_no_providers_for_an_unlinked_account()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('connected-accounts.index'))
            ->assertInertia(fn ($page) => $page->has('connectedProviders', 0));
    }

    public function test_another_users_link_is_not_listed()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for(User::factory()->create())->create([
            'provider' => 'google',
        ]);

        $this->actingAs($user)
            ->get(route('connected-accounts.index'))
            ->assertInertia(fn ($page) => $page->has('connectedProviders', 0));
    }

    public function test_the_page_is_hidden_when_no_provider_is_supported()
    {
        config(['auth.sso.supported' => []]);

        $this->actingAs(User::factory()->create())
            ->get(route('connected-accounts.index'))
            ->assertNotFound();
    }

    public function test_the_page_is_hidden_when_no_provider_has_credentials()
    {
        config([
            'services.google.client_id' => null,
            'services.github.client_id' => null,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('connected-accounts.index'))
            ->assertNotFound();
    }

    public function test_disconnecting_requires_authentication()
    {
        $this->delete(route('sso.logout', ['provider' => 'google']))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_with_a_password_can_disconnect_their_only_provider()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->delete(route('sso.logout', ['provider' => 'google']))
            ->assertRedirect(route('connected-accounts.index'))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertSame(0, $user->socialAccounts()->count());
    }

    public function test_a_user_without_a_password_can_disconnect_one_of_two_providers()
    {
        $user = User::factory()->passwordless()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);
        SocialAccount::factory()->for($user)->create(['provider' => 'github']);

        $this->actingAs($user)
            ->delete(route('sso.logout', ['provider' => 'google']))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertSame(['github'], $user->socialAccounts()->pluck('provider')->all());
    }

    public function test_a_user_without_a_password_cannot_disconnect_their_last_provider()
    {
        $user = User::factory()->passwordless()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->from(route('connected-accounts.index'))
            ->delete(route('sso.logout', ['provider' => 'google']))
            ->assertRedirect(route('connected-accounts.index'))
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_disconnecting_a_provider_that_is_not_connected_is_rejected()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('connected-accounts.index'))
            ->delete(route('sso.logout', ['provider' => 'google']))
            ->assertInertiaFlash('toast.type', 'error');
    }

    public function test_disconnecting_only_removes_the_named_provider()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);
        SocialAccount::factory()->for($user)->create(['provider' => 'github']);

        $this->actingAs($user)
            ->delete(route('sso.logout', ['provider' => 'google']));

        $this->assertSame(['github'], $user->socialAccounts()->pluck('provider')->all());
    }

    public function test_disconnecting_does_not_touch_another_users_link()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $other = User::factory()->create();
        SocialAccount::factory()->for($other)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->delete(route('sso.logout', ['provider' => 'google']));

        $this->assertSame(1, $other->socialAccounts()->count());
    }

    public function test_disconnecting_an_unsupported_provider_is_forbidden()
    {
        $this->actingAs(User::factory()->create())
            ->delete(route('sso.logout', ['provider' => 'twitter']))
            ->assertForbidden();
    }

    public function test_connecting_a_provider_the_account_already_has_is_refused_before_leaving_the_app()
    {
        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);

        $this->actingAs($user)
            ->from(route('connected-accounts.index'))
            ->get(route('sso.redirect', ['provider' => 'google']))
            ->assertRedirect(route('connected-accounts.index'))
            ->assertInertiaFlash('toast.type', 'error');
    }

    public function test_connecting_refuses_an_identity_already_linked_to_another_account()
    {
        $other = User::factory()->create();
        SocialAccount::factory()->for($other)->create([
            'provider' => 'google',
            'provider_id' => '12345',
        ]);

        $user = User::factory()->create();

        Socialite::fake('google', $this->socialiteUser());

        $this->actingAs($user)
            ->from(route('connected-accounts.index'))
            ->get(route('sso.callback', ['provider' => 'google']))
            ->assertRedirect(route('connected-accounts.index'))
            ->assertInertiaFlash('toast.type', 'error');

        $this->assertSame(0, $user->socialAccounts()->count());
        $this->assertSame($other->id, $other->socialAccounts()->sole()->user_id);
    }

    public function test_connecting_does_not_require_the_provider_email_to_match_the_account()
    {
        $user = User::factory()->create(['email' => 'someone-else@example.com']);

        Socialite::fake('google', $this->socialiteUser());

        $this->actingAs($user)
            ->get(route('sso.callback', ['provider' => 'google']))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_connecting_accepts_a_provider_email_the_provider_has_not_verified()
    {
        $user = User::factory()->create();

        Socialite::fake('google', $this->socialiteUser(['verified_email' => false]));

        $this->actingAs($user)
            ->get(route('sso.callback', ['provider' => 'google']))
            ->assertInertiaFlash('toast.type', 'success');

        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_connecting_never_overwrites_the_name_or_email()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Socialite::fake('google', $this->socialiteUser());

        $this->actingAs($user)
            ->get(route('sso.callback', ['provider' => 'google']));

        $user->refresh();

        $this->assertSame('Original Name', $user->name);
        $this->assertSame('original@example.com', $user->email);
    }

    public function test_the_credential_check_runs_under_a_row_lock()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite does not implement SELECT ... FOR UPDATE.');
        }

        $user = User::factory()->create();
        SocialAccount::factory()->for($user)->create(['provider' => 'google']);
        SocialAccount::factory()->for($user)->create(['provider' => 'github']);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($user)->delete(route('sso.logout', ['provider' => 'google']));

        $lockedRead = $this->firstQueryMatching($queries, fn ($sql) => str_contains($sql, 'for update'));
        $credentialCount = $this->firstQueryMatching($queries, fn ($sql) => str_contains($sql, 'count(*)') && str_contains($sql, 'social_accounts'));

        $this->assertNotNull($lockedRead, 'The user row should be read with a lock.');
        $this->assertNotNull($credentialCount, 'The remaining credentials should be counted.');
        $this->assertLessThan(
            $credentialCount,
            $lockedRead,
            'The lock must be taken before the credentials are counted, or two requests can both read a stale count.'
        );
    }

    private function firstQueryMatching(array $queries, callable $matches): ?int
    {
        foreach ($queries as $index => $sql) {
            if ($matches($sql)) {
                return $index;
            }
        }

        return null;
    }

    private function socialiteUser(array $overrides = []): SocialiteUser
    {
        return SocialiteUser::fake(array_merge([
            'id' => '12345',
            'nickname' => 'janedoe',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'verified_email' => true,
        ], $overrides));
    }
}

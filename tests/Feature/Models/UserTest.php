<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Goal;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSentMail;
use Tests\TestCase;

class UserTest extends TestCase
{
    use InteractsWithSentMail;
    use RefreshDatabase;

    // =========================================================================
    // RELATIONSHIP TESTS
    // =========================================================================

    public function test_user_has_many_goals()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->goals);
        $this->assertTrue($user->goals->contains($goal));
        $this->assertInstanceOf(Goal::class, $user->goals->first());
    }

    public function test_user_has_many_categories()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->categories);
        $this->assertTrue($user->categories->contains($category));
        $this->assertInstanceOf(Category::class, $user->categories->first());
    }

    public function test_user_has_many_social_accounts()
    {
        $user = User::factory()->create();
        $account = SocialAccount::factory()->create(['user_id' => $user->id]);

        $this->assertCount(1, $user->socialAccounts);
        $this->assertTrue($user->socialAccounts->contains($account));
        $this->assertInstanceOf(SocialAccount::class, $user->socialAccounts->first());
    }

    // =========================================================================
    // METHOD TESTS
    // =========================================================================

    public function test_active_goals_returns_only_in_progress_goals()
    {
        $user = User::factory()->create();
        Goal::factory()->quantifiable()->create(['user_id' => $user->id, 'status' => 'in_progress', 'current_value' => 10, 'target_value' => 100]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'completed_at' => now(), 'current_value' => 0]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'paused', 'current_value' => 0]);

        $activeGoals = $user->activeGoals()->get();

        $this->assertCount(1, $activeGoals);
        $this->assertEquals('in_progress', $activeGoals->first()->status);
    }

    public function test_completed_goals_returns_only_completed_goals()
    {
        $user = User::factory()->create();
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'in_progress', 'current_value' => 0]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'completed', 'completed_at' => now(), 'current_value' => 0]);
        Goal::factory()->create(['user_id' => $user->id, 'status' => 'paused', 'current_value' => 0]);

        $completedGoals = $user->completedGoals()->get();

        $this->assertCount(1, $completedGoals);
        $this->assertEquals('completed', $completedGoals->first()->status);
    }

    public function test_has_password_reflects_the_password_column()
    {
        $this->assertTrue(User::factory()->create()->hasPassword());

        $passwordless = User::factory()->create(['password' => null]);

        $this->assertFalse($passwordless->hasPassword());
    }

    // =========================================================================
    // LOCALE TESTS
    // =========================================================================

    public function test_user_defaults_to_english_locale()
    {
        $user = User::factory()->create();

        $this->assertEquals('en', $user->locale);
    }

    public function test_locale_is_mass_assignable()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->assertEquals('fr', $user->locale);
    }

    public function test_preferred_locale_returns_the_stored_locale_column()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->assertEquals('fr', $user->preferredLocale());
    }

    // =========================================================================
    // NOTIFICATION TESTS
    // =========================================================================

    public function test_sending_the_verification_notification_reports_success(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertTrue($user->sendEmailVerificationNotification());
    }

    public function test_sending_the_verification_notification_reports_a_transport_failure(): void
    {
        $this->makeMailTransportFail();

        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->sendEmailVerificationNotification());
    }

    public function test_sending_the_password_reset_notification_swallows_a_transport_failure(): void
    {
        $this->makeMailTransportFail();

        $user = User::factory()->create();

        $user->sendPasswordResetNotification('token');

        $this->assertTrue(true, 'No exception escaped the send.');
    }

    // =========================================================================
    // SERIALIZATION TESTS
    // =========================================================================

    public function test_serialization_hides_two_factor_secrets_and_password()
    {
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => 'super-secret',
            'two_factor_recovery_codes' => 'recovery-codes',
        ])->save();

        $serialized = $user->fresh()->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $serialized);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $serialized);
        $this->assertArrayNotHasKey('password', $serialized);
    }
}

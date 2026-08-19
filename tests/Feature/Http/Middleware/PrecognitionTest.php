<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Precognition validates a request without executing the action behind it.
 *
 * It only reaches validation that runs while the controller's parameters are
 * resolved, which means a route must type-hint a FormRequest. A controller
 * that calls $request->validate() in its body is never entered, so a
 * precognitive call to it would answer 204 without checking anything.
 */
class PrecognitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_validates_a_complete_payload_without_creating_a_user()
    {
        $response = $this->withPrecognition()->postJson(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Strong-P@ssw0rd',
            'password_confirmation' => 'Strong-P@ssw0rd',
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertSame(0, User::count());
    }

    public function test_registration_reports_the_fields_still_missing()
    {
        $response = $this->withPrecognition()->postJson(route('register.store'), [
            'name' => 'Test User',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
        $this->assertSame(0, User::count());
    }

    public function test_registration_reports_an_email_already_registered()
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'email')
            ->postJson(route('register.store'), [
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_registration_skips_the_compromised_password_check_while_typing()
    {
        $response = $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'password')
            ->postJson(route('register.store'), [
                'password' => 'Password1!',
                'password_confirmation' => 'Password1!',
            ]);

        $response->assertSuccessfulPrecognition();
    }

    public function test_registration_reports_a_password_confirmation_mismatch()
    {
        $response = $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'password')
            ->postJson(route('register.store'), [
                'password' => 'Strong-P@ssw0rd',
                'password_confirmation' => 'Different-P@ssw0rd',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_goal_store_validates_without_creating_a_goal()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'title')
            ->postJson(route('goals.store'), [
                'title' => 'A perfectly valid title',
            ]);

        $response->assertSuccessfulPrecognition();
        $this->assertSame(0, Goal::count());
    }

    public function test_goal_store_reports_a_title_over_the_length_limit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'title')
            ->postJson(route('goals.store'), [
                'title' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
        $this->assertSame(0, Goal::count());
    }

    public function test_goal_store_is_denied_for_a_guest()
    {
        $response = $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'title')
            ->postJson(route('goals.store'), ['title' => 'Anything']);

        $response->assertStatus(401);
    }

    public function test_entry_store_reports_a_note_over_the_length_limit()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create(['type' => 'quantifiable']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'note')
            ->postJson(route('goals.entries.store', $goal), [
                'note' => str_repeat('a', 2001),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('note');
        $this->assertSame(0, GoalEntry::count());
    }

    public function test_entry_store_is_denied_on_another_users_goal()
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->for($owner)->create(['type' => 'quantifiable']);

        $response = $this->actingAs($intruder)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'note')
            ->postJson(route('goals.entries.store', $goal), [
                'note' => 'A short note',
            ]);

        $response->assertStatus(403);
    }

    public function test_check_in_reports_a_date_in_the_future()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->for($user)->create(['type' => 'recurring']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'entry_date')
            ->postJson(route('goals.entries.store', $goal), [
                'entry_date' => now()->addWeek()->toDateString(),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('entry_date');
        $this->assertSame(0, GoalEntry::count());
    }

    public function test_category_store_validates_a_complete_payload_without_creating_a_category()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->postJson(route('categories.store'), [
                'name' => 'Woodworking',
                'description' => 'Projects in the garage',
                'color' => '#6366f1',
            ]);

        $response->assertSuccessfulPrecognition();
        $this->assertSame(0, Category::where('user_id', $user->id)->count());
    }

    public function test_category_store_reports_a_name_over_the_length_limit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'name')
            ->postJson(route('categories.store'), [
                'name' => str_repeat('a', 101),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
        $this->assertSame(0, Category::where('user_id', $user->id)->count());
    }

    public function test_login_validates_the_email_without_authenticating()
    {
        $response = $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'email')
            ->postJson(route('login.store'), [
                'email' => 'not-an-email',
                'password' => 'irrelevant',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_login_accepts_a_well_formed_email_without_authenticating()
    {
        $user = User::factory()->create();

        $response = $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'email')
            ->postJson(route('login.store'), [
                'email' => $user->email,
                'password' => 'the-wrong-password',
            ]);

        $response->assertSuccessfulPrecognition();
        $this->assertGuest();
    }

    public function test_profile_update_validates_a_single_field_without_persisting()
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'name')
            ->patchJson(route('profile.update'), [
                'name' => 'Replacement Name',
            ]);

        $response->assertSuccessfulPrecognition();
        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_profile_update_reports_a_name_over_the_length_limit()
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'name')
            ->patchJson(route('profile.update'), [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
        $this->assertSame('Original Name', $user->fresh()->name);
    }

    public function test_profile_update_reports_an_email_already_taken_by_another_user()
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'email')
            ->patchJson(route('profile.update'), [
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_profile_update_allows_the_user_to_keep_their_own_email()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'email')
            ->patchJson(route('profile.update'), [
                'email' => $user->email,
            ]);

        $response->assertSuccessfulPrecognition();
    }
}

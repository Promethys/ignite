<?php

namespace Tests\Feature\Settings;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Ocean',
            'light_color' => '#2563EB',
            'dark_color' => null,
            ...$overrides,
        ];
    }

    // =========================================================================
    // ACCESS
    // =========================================================================

    public function test_guests_cannot_manage_themes(): void
    {
        $theme = Theme::factory()->create();

        $this->get(route('appearance.edit'))->assertRedirect(route('login'));
        $this->post(route('themes.store'), $this->validPayload())->assertRedirect(route('login'));
        $this->put(route('themes.update', $theme), $this->validPayload())->assertRedirect(route('login'));
        $this->patch(route('themes.activate', $theme))->assertRedirect(route('login'));
        $this->patch(route('themes.use-default'))->assertRedirect(route('login'));
        $this->delete(route('themes.destroy', $theme))->assertRedirect(route('login'));
    }

    public function test_a_user_cannot_touch_another_users_theme(): void
    {
        $theme = Theme::factory()->active()->create(['light_color' => '#111111']);

        $this->actingAs($this->user);

        $this->put(route('themes.update', $theme), $this->validPayload())->assertNotFound();
        $this->patch(route('themes.activate', $theme))->assertNotFound();
        $this->delete(route('themes.destroy', $theme))->assertNotFound();

        $this->assertSame('#111111', $theme->fresh()->light_color);
    }

    // =========================================================================
    // APPEARANCE PAGE
    // =========================================================================

    public function test_the_appearance_page_lists_only_the_users_themes(): void
    {
        $own = Theme::factory()->for($this->user)->create(['name' => 'Ocean', 'light_color' => '#2563eb']);
        Theme::factory()->create();

        $this->actingAs($this->user)
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Appearance')
                ->where('themeLimit', Theme::MAX_PER_USER)
                ->has('themes', 1)
                ->where('themes.0', [
                    'id' => $own->id,
                    'name' => 'Ocean',
                    'light_color' => '#2563eb',
                    'dark_color' => null,
                    'is_active' => false,
                ]));
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function test_a_new_theme_is_saved_lowercased_and_put_in_use(): void
    {
        $previous = Theme::factory()->for($this->user)->active()->create();

        $this->actingAs($this->user)
            ->post(route('themes.store'), $this->validPayload(['dark_color' => '#93C5FD']))
            ->assertRedirect();

        $theme = $this->user->themes()->latest('id')->first();

        $this->assertSame('Ocean', $theme->name);
        $this->assertSame('#2563eb', $theme->light_color);
        $this->assertSame('#93c5fd', $theme->dark_color);
        $this->assertTrue($theme->is_active);
        $this->assertFalse($previous->fresh()->is_active);
    }

    public function test_the_theme_name_is_encrypted_at_rest(): void
    {
        $this->actingAs($this->user)->post(route('themes.store'), $this->validPayload());

        $storedName = DB::table('themes')->where('user_id', $this->user->id)->value('name');

        $this->assertNotSame('Ocean', $storedName);
        $this->assertStringNotContainsString('Ocean', $storedName);
    }

    public function test_a_theme_needs_a_name_and_hex_colors(): void
    {
        $this->actingAs($this->user)
            ->post(route('themes.store'), $this->validPayload([
                'name' => '',
                'light_color' => 'red',
                'dark_color' => '#12345',
            ]))
            ->assertSessionHasErrors(['name', 'light_color', 'dark_color']);

        $this->assertSame(0, $this->user->themes()->count());
    }

    public function test_a_color_cannot_carry_css(): void
    {
        $this->actingAs($this->user)
            ->post(route('themes.store'), $this->validPayload(['light_color' => '#fff;}body{display:none}']))
            ->assertSessionHasErrors('light_color');
    }

    public function test_a_user_cannot_keep_more_themes_than_the_limit(): void
    {
        Theme::factory()->for($this->user)->count(Theme::MAX_PER_USER)->create();

        $this->actingAs($this->user)
            ->post(route('themes.store'), $this->validPayload())
            ->assertSessionHasErrors('name');

        $this->assertSame(Theme::MAX_PER_USER, $this->user->themes()->count());
    }

    public function test_the_form_validates_a_field_while_it_is_filled_without_saving(): void
    {
        $this->actingAs($this->user);

        $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'light_color')
            ->postJson(route('themes.store'), ['light_color' => 'red'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('light_color');

        $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'name')
            ->postJson(route('themes.store'), ['name' => ''])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', __('validation.required', ['attribute' => 'name']));

        $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'name')
            ->postJson(route('themes.store'), ['name' => 'Ocean'])
            ->assertSuccessfulPrecognition();

        $theme = Theme::factory()->for($this->user)->create();

        $this->withPrecognition()
            ->withHeader('Precognition-Validate-Only', 'name')
            ->putJson(route('themes.update', $theme), ['name' => str_repeat('a', 51)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');

        $this->assertSame(1, $this->user->themes()->count());
    }

    // =========================================================================
    // UPDATE, SELECT, DELETE
    // =========================================================================

    public function test_a_theme_can_be_renamed_and_recolored(): void
    {
        $theme = Theme::factory()->for($this->user)->withDarkColor('#000000')->create();

        $this->actingAs($this->user)
            ->put(route('themes.update', $theme), $this->validPayload(['name' => 'Forest', 'light_color' => '#15803d']))
            ->assertRedirect();

        $theme->refresh();

        $this->assertSame('Forest', $theme->name);
        $this->assertSame('#15803d', $theme->light_color);
        $this->assertNull($theme->dark_color);
    }

    public function test_selecting_a_theme_leaves_exactly_one_in_use(): void
    {
        $first = Theme::factory()->for($this->user)->active()->create();
        $second = Theme::factory()->for($this->user)->create();
        $foreign = Theme::factory()->active()->create();

        $this->actingAs($this->user)->patch(route('themes.activate', $second))->assertRedirect();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
        $this->assertTrue($foreign->fresh()->is_active);
    }

    public function test_going_back_to_the_default_leaves_no_theme_in_use(): void
    {
        $theme = Theme::factory()->for($this->user)->active()->create();
        $foreign = Theme::factory()->active()->create();

        $this->actingAs($this->user)->patch(route('themes.use-default'))->assertRedirect();

        $this->assertFalse($theme->fresh()->is_active);
        $this->assertTrue($foreign->fresh()->is_active);
    }

    public function test_deleting_the_theme_in_use_falls_back_to_the_default(): void
    {
        $theme = Theme::factory()->for($this->user)->active()->create();

        $this->actingAs($this->user)->delete(route('themes.destroy', $theme))->assertRedirect();

        $this->assertModelMissing($theme);

        $this->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('themeStylesheet', null));
    }

    // =========================================================================
    // APPLYING THE THEME
    // =========================================================================

    public function test_app_pages_carry_the_stylesheet_of_the_theme_in_use(): void
    {
        Theme::factory()->for($this->user)->active()->withDarkColor('#0f172a')->create(['light_color' => '#fde047']);

        $expected = 'html:root{--primary:#fde047;--primary-foreground:#111111}html.dark{--primary:#0f172a;--primary-foreground:#ffffff}';

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('themeStylesheet', $expected))
            ->assertSee('<style id="user-theme">'.$expected.'</style>', false);
    }

    public function test_one_color_is_used_for_both_modes_when_no_dark_color_is_set(): void
    {
        Theme::factory()->for($this->user)->active()->create(['light_color' => '#2563eb']);

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'themeStylesheet',
                'html:root{--primary:#2563eb;--primary-foreground:#ffffff}html.dark{--primary:#2563eb;--primary-foreground:#ffffff}',
            ));
    }

    public function test_pages_stay_on_the_default_without_a_theme_in_use(): void
    {
        Theme::factory()->for($this->user)->create();

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('themeStylesheet', null))
            ->assertSee('<style id="user-theme"></style>', false);
    }

    public function test_the_landing_page_is_never_themed(): void
    {
        Theme::factory()->for($this->user)->active()->create();

        $this->actingAs($this->user)
            ->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page->where('themeStylesheet', null));
    }

    public function test_auth_pages_are_never_themed_for_a_signed_in_user(): void
    {
        $unverified = User::factory()->unverified()->create();
        Theme::factory()->for($unverified)->active()->create();

        $this->actingAs($unverified)
            ->get(route('verification.notice'))
            ->assertInertia(fn (Assert $page) => $page->where('themeStylesheet', null));
    }

    public function test_deleting_the_account_deletes_its_themes(): void
    {
        $theme = Theme::factory()->for($this->user)->create();

        $this->user->delete();

        $this->assertModelMissing($theme);
    }
}

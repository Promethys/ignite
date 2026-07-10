<?php

namespace Tests\Feature\Localization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_messages_are_translated_to_french()
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $this->actingAs($user)
            ->post(route('goals.store'), [])
            ->assertSessionHasErrors('title');

        $titleError = session('errors')->get('title')[0];

        $this->assertStringContainsString('obligatoire', $titleError);
    }

    public function test_validation_messages_remain_in_english_by_default()
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->post(route('goals.store'), [])
            ->assertSessionHasErrors('title');

        $titleError = session('errors')->get('title')[0];

        $this->assertStringContainsString('required', $titleError);
    }
}

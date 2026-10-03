<?php

namespace Database\Factories;

use App\Enums\AssistantProvider;
use App\Models\AssistantKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AssistantKey>
 */
class AssistantKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $apiKey = 'sk-test-'.Str::random(32);

        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(AssistantProvider::cases()),
            'api_key' => $apiKey,
            'key_suffix' => substr($apiKey, -4),
            'model' => null,
            'consented_at' => now(),
        ];
    }

    public function provider(AssistantProvider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}

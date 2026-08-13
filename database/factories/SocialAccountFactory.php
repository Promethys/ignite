<?php

namespace Database\Factories;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialAccount>
 */
class SocialAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => fake()->randomElement(['google', 'github']),
            'provider_id' => (string) fake()->unique()->randomNumber(7),
            'provider_data' => [
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'avatar' => fake()->imageUrl(),
            ],
        ];
    }

    public function provider(string $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}

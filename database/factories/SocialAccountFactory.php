<?php

namespace Database\Factories;

use App\Models\SocialAccount;
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
            'provider' => fake()->randomElement(['google', 'github']),
            'provider_id' => (string) fake()->unique()->randomNumber(7),
            'token' => fake()->sha256(),
            'refresh_token' => fake()->optional()->sha256(),
            'provider_data' => [
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
                'avatar' => fake()->imageUrl(),
            ],
        ];
    }
}

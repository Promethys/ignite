<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theme>
 */
class ThemeFactory extends Factory
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
            'name' => fake()->words(2, true),
            'light_color' => fake()->hexColor(),
            'dark_color' => null,
        ];
    }

    public function active(): static
    {
        return $this->afterCreating(fn (Theme $theme) => $theme->activate());
    }

    public function withDarkColor(string $color): static
    {
        return $this->state(['dark_color' => $color]);
    }
}

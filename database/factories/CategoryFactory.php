<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Health & Fitness',
            'Career & Work',
            'Learning & Education',
            'Finance & Money',
            'Relationships',
            'Personal Growth',
            'Hobbies & Interests',
            'Travel & Adventure',
            'Home & Living',
            'Creativity & Arts',
        ]);

        $colors = [
            '#ef4444', // red
            '#f97316', // orange
            '#f59e0b', // amber
            '#eab308', // yellow
            '#84cc16', // lime
            '#22c55e', // green
            '#10b981', // emerald
            '#14b8a6', // teal
            '#06b6d4', // cyan
            '#0ea5e9', // sky
            '#3b82f6', // blue
            '#6366f1', // indigo
            '#8b5cf6', // violet
            '#a855f7', // purple
            '#d946ef', // fuchsia
            '#ec4899', // pink
            '#f43f5e', // rose
        ];

        $icons = ['💪', '💼', '📚', '💰', '❤️', '🌱', '🎨', '✈️', '🏠', '🎯'];

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->randomElement($colors),
            'icon' => fake()->randomElement($icons),
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}

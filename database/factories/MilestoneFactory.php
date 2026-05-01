<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\Milestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Milestone>
 */
class MilestoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCompleted = fake()->boolean(40); // 40% completed

        return [
            'goal_id' => Goal::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional(0.6)->sentence(),
            'target_value' => fake()->numberBetween(10, 100),
            'order' => fake()->numberBetween(0, 5),
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? fake()->dateTimeBetween('-2 months', 'now') : null,
            'points_reward' => fake()->numberBetween(10, 100),
        ];
    }
}

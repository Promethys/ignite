<?php

namespace Database\Factories;

use App\Models\Goal;
use App\Models\GoalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoalEntry>
 */
class GoalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $notes = [
            'Great progress today!',
            'Felt really good about this.',
            'Small step, but progress nonetheless.',
            'Challenging but rewarding.',
            'Stayed consistent!',
            'Proud of myself.',
            null,
            null,
        ];

        return [
            'goal_id' => Goal::factory(),
            'value' => fake()->randomFloat(2, 1, 50),
            'previous_value' => 0,
            'note' => fake()->randomElement($notes),
            'entry_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'attachment_path' => null,
            'attachment_type' => null,
        ];
    }
}

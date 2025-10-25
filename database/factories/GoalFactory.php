<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Goal>
 */
class GoalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['simple', 'quantifiable', 'recurring', 'multi_step']);

        $simpleGoals = [
            'Launch my portfolio website',
            'Learn to play guitar',
            'Write a book',
            'Run a marathon',
            'Start a podcast',
        ];

        $quantifiableGoals = [
            ['title' => 'Read books', 'target' => 50, 'unit' => 'books'],
            ['title' => 'Run distance', 'target' => 500, 'unit' => 'km'],
            ['title' => 'Save money', 'target' => 10000, 'unit' => '€'],
            ['title' => 'Learn vocabulary', 'target' => 1000, 'unit' => 'words'],
            ['title' => 'Meditate', 'target' => 365, 'unit' => 'sessions'],
            ['title' => 'Workout', 'target' => 100, 'unit' => 'sessions'],
        ];

        $recurringGoals = [
            'Daily meditation',
            'Weekly blog post',
            'Monthly budget review',
            'Daily journaling',
        ];

        $status = fake()->randomElement(['not_started', 'in_progress', 'completed', 'paused', 'abandoned']);

        if ($type === 'simple') {
            $title = fake()->randomElement($simpleGoals);
            $targetValue = null;
            $currentValue = 0;
            $unit = null;
            $recurrence = null;
        } elseif ($type === 'quantifiable') {
            $goal = fake()->randomElement($quantifiableGoals);
            $title = $goal['title'];
            $targetValue = $goal['target'];
            $unit = $goal['unit'];
            $recurrence = null;

            // Generate realistic progress based on status
            $currentValue = match ($status) {
                'not_started' => 0,
                'in_progress' => fake()->numberBetween($targetValue * 0.1, $targetValue * 0.9),
                'completed' => $targetValue,
                'paused' => fake()->numberBetween($targetValue * 0.2, $targetValue * 0.6),
                'abandoned' => fake()->numberBetween(0, $targetValue * 0.3),
            };
        } elseif ($type === 'recurring') {
            $title = fake()->randomElement($recurringGoals);
            $targetValue = null;
            $currentValue = 0;
            $unit = null;
            $recurrence = fake()->randomElement(['daily', 'weekly', 'monthly', 'annually']);
        } else { // multi_step
            $title = fake()->randomElement($simpleGoals);
            $targetValue = null;
            $currentValue = 0;
            $unit = null;
            $recurrence = null;
        }

        $startDate = fake()->dateTimeBetween('-6 months', 'now');
        $deadline = fake()->optional(0.8)->dateTimeBetween('now', '+1 year');

        $completedAt = null;
        if ($status === 'completed') {
            $completedAt = fake()->dateTimeBetween($startDate, 'now');
        }

        $icons = ['🎯', '🏃', '📚', '💰', '🎨', '💪', '🧘', '✍️', '🌟', '🚀'];

        return [
            'user_id' => User::factory(),
            'category_id' => fake()->optional(0.8)->randomElement([null, Category::factory()]),
            'title' => $title,
            'description' => fake()->optional(0.7)->paragraph(),
            'icon' => fake()->randomElement($icons),
            'type' => $type,
            'target_value' => $targetValue,
            'current_value' => $currentValue,
            'unit' => $unit,
            'recurrence' => $recurrence,
            'start_date' => $startDate,
            'deadline' => $deadline,
            'completed_at' => $completedAt,
            'status' => $status,
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'points' => fake()->numberBetween(0, 500),
            'is_public' => fake()->boolean(20), // 20% public
            'order' => fake()->numberBetween(0, 10),
        ];
    }

    /**
     * Goal is in progress
     */
    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
            'completed_at' => null,
        ]);
    }

    /**
     * Goal is completed
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'current_value' => $attributes['target_value'] ?? 0,
            'completed_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ]);
    }

    /**
     * Goal is overdue
     */
    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
            'deadline' => fake()->dateTimeBetween('-2 months', '-1 day'),
        ]);
    }
}

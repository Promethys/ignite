<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Achievement>
 */
class AchievementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $achievements = [
            ['name' => 'First Steps', 'slug' => 'first-steps', 'description' => 'Complete your first goal', 'icon' => '👣', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 1]],
            ['name' => 'Goal Getter', 'slug' => 'goal-getter', 'description' => 'Complete 5 goals', 'icon' => '🎯', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 5]],
            ['name' => 'Achiever', 'slug' => 'achiever', 'description' => 'Complete 10 goals', 'icon' => '🏆', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 10]],
            ['name' => 'Consistency King', 'slug' => 'consistency-king', 'description' => 'Maintain a 7-day streak', 'icon' => '👑', 'type' => 'streak', 'criteria' => ['streak_days' => 7]],
            ['name' => 'Unstoppable', 'slug' => 'unstoppable', 'description' => 'Maintain a 30-day streak', 'icon' => '🔥', 'type' => 'streak', 'criteria' => ['streak_days' => 30]],
            ['name' => 'Point Collector', 'slug' => 'point-collector', 'description' => 'Earn 1000 points', 'icon' => '💎', 'type' => 'points', 'criteria' => ['points_earned' => 1000]],
            ['name' => 'Early Bird', 'slug' => 'early-bird', 'description' => 'Complete 10 entries before 9 AM', 'icon' => '🌅', 'type' => 'consistency', 'criteria' => ['early_entries' => 10]],
            ['name' => 'Weekend Warrior', 'slug' => 'weekend-warrior', 'description' => 'Complete goals on 10 weekends', 'icon' => '⚔️', 'type' => 'consistency', 'criteria' => ['weekend_completions' => 10]],
        ];

        $achievement = fake()->randomElement($achievements);

        return [
            'name' => $achievement['name'],
            'slug' => $achievement['slug'],
            'description' => $achievement['description'],
            'icon' => $achievement['icon'],
            'badge_image' => null,
            'type' => $achievement['type'],
            'criteria' => $achievement['criteria'],
            'points_reward' => fake()->numberBetween(50, 500),
            'rarity' => fake()->randomElement(['common', 'rare', 'epic', 'legendary']),
            'order' => fake()->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InitDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting data initialization...');
        $this->command->newLine();

        DB::beginTransaction();

        try {
            // Create test users
            $this->command->info('👤 Creating users...');
            $bar = $this->command->getOutput()->createProgressBar(3);

            $demoUser = User::create([
                'name' => 'Demo User',
                'email' => 'demo@ignite.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $bar->advance();

            $activeUser = User::create([
                'name' => 'Active User',
                'email' => 'active@ignite.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $bar->advance();

            $newUser = User::create([
                'name' => 'New User',
                'email' => 'new@ignite.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $bar->advance();

            $bar->finish();
            $this->command->newLine(2);

            // Create categories
            $this->command->info('🏷️  Creating categories...');
            $categories = [
                ['name' => 'Health & Fitness', 'color' => '#22c55e', 'icon' => '💪'],
                ['name' => 'Career & Work', 'color' => '#3b82f6', 'icon' => '💼'],
                ['name' => 'Learning', 'color' => '#8b5cf6', 'icon' => '📚'],
                ['name' => 'Finance', 'color' => '#eab308', 'icon' => '💰'],
                ['name' => 'Personal Growth', 'color' => '#ec4899', 'icon' => '🌱'],
            ];

            $bar = $this->command->getOutput()->createProgressBar(count($categories));
            $createdCategories = [];

            foreach ($categories as $index => $category) {
                $createdCategories[] = Category::create([
                    'user_id' => $demoUser->id,
                    'name' => $category['name'],
                    'slug' => Str::slug($category['name']),
                    'description' => "Goals related to {$category['name']}",
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'order' => $index,
                ]);
                $bar->advance();
            }

            $bar->finish();
            $this->command->newLine(2);

            // Create goals for demo user (diverse states)
            $this->command->info('🎯 Creating goals for demo user...');
            $bar = $this->command->getOutput()->createProgressBar(12);

            // In progress goals with entries
            $runGoal = Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[0]->id,
                'title' => 'Run 500km this year',
                'description' => 'Training for a marathon',
                'icon' => '🏃',
                'type' => 'quantifiable',
                'target_value' => 500,
                'current_value' => 325,
                'unit' => 'km',
                'start_date' => now()->subMonths(6),
                'deadline' => now()->addMonths(6),
                'status' => 'in_progress',
                'priority' => 'high',
                'points' => 150,
            ]);
            $bar->advance();

            // Add entries for run goal
            for ($i = 0; $i < 45; $i++) {
                GoalEntry::create([
                    'goal_id' => $runGoal->id,
                    'value' => fake()->randomFloat(2, 3, 12),
                    'previous_value' => 0,
                    'note' => fake()->optional(0.3)->randomElement(['Great run!', 'Felt strong today', 'Tough but completed']),
                    'entry_date' => now()->subDays(rand(1, 180)),
                ]);
            }

            // Completed goal
            $readGoal = Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[2]->id,
                'title' => 'Read 50 books',
                'description' => 'Expand my knowledge and imagination',
                'icon' => '📚',
                'type' => 'quantifiable',
                'target_value' => 50,
                'current_value' => 50,
                'unit' => 'books',
                'start_date' => now()->subYear(),
                'deadline' => now()->subMonths(1),
                'completed_at' => now()->subMonths(1),
                'status' => 'completed',
                'priority' => 'medium',
                'points' => 500,
            ]);
            $bar->advance();

            // Overdue goal
            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[3]->id,
                'title' => 'Save €10,000',
                'description' => 'Emergency fund',
                'icon' => '💰',
                'type' => 'quantifiable',
                'target_value' => 10000,
                'current_value' => 4500,
                'unit' => '€',
                'start_date' => now()->subYear(),
                'deadline' => now()->subDays(30),
                'status' => 'in_progress',
                'priority' => 'high',
                'points' => 75,
            ]);
            $bar->advance();

            // Simple goals
            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[1]->id,
                'title' => 'Launch portfolio website',
                'description' => 'Build and deploy my personal portfolio',
                'icon' => '🚀',
                'type' => 'simple',
                'start_date' => now()->subMonth(),
                'deadline' => now()->addMonths(2),
                'status' => 'in_progress',
                'priority' => 'high',
                'points' => 25,
            ]);
            $bar->advance();

            // Recurring goal
            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[4]->id,
                'title' => 'Daily meditation',
                'description' => 'Meditate for 10 minutes every day',
                'icon' => '🧘',
                'type' => 'recurring',
                'recurrence' => 'daily',
                'start_date' => now()->subMonths(2),
                'status' => 'in_progress',
                'priority' => 'medium',
                'points' => 80,
            ]);
            $bar->advance();

            // Multi-step goal with milestones
            $careerGoal = Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[1]->id,
                'title' => 'Become a senior developer',
                'description' => 'Advance my career to senior level',
                'icon' => '💻',
                'type' => 'multi_step',
                'start_date' => now()->subMonths(3),
                'deadline' => now()->addYear(),
                'status' => 'in_progress',
                'priority' => 'high',
                'points' => 50,
            ]);
            $bar->advance();

            // Add milestones
            Milestone::create([
                'goal_id' => $careerGoal->id,
                'title' => 'Complete advanced course',
                'target_value' => 1,
                'order' => 1,
                'is_completed' => true,
                'completed_at' => now()->subMonth(),
                'points_reward' => 50,
            ]);

            Milestone::create([
                'goal_id' => $careerGoal->id,
                'title' => 'Build 3 side projects',
                'target_value' => 3,
                'order' => 2,
                'is_completed' => false,
                'points_reward' => 100,
            ]);

            Milestone::create([
                'goal_id' => $careerGoal->id,
                'title' => 'Get promoted',
                'target_value' => 1,
                'order' => 3,
                'is_completed' => false,
                'points_reward' => 200,
            ]);

            // More varied goals
            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[0]->id,
                'title' => 'Workout 100 times',
                'icon' => '💪',
                'type' => 'quantifiable',
                'target_value' => 100,
                'current_value' => 67,
                'unit' => 'sessions',
                'start_date' => now()->subMonths(4),
                'deadline' => now()->addMonths(2),
                'status' => 'in_progress',
                'priority' => 'medium',
                'points' => 120,
            ]);
            $bar->advance();

            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[2]->id,
                'title' => 'Learn Spanish',
                'description' => 'Become conversational in Spanish',
                'icon' => '🇪🇸',
                'type' => 'simple',
                'start_date' => now(),
                'deadline' => now()->addMonths(6),
                'status' => 'not_started',
                'priority' => 'low',
                'points' => 0,
            ]);
            $bar->advance();

            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[4]->id,
                'title' => 'Write journal entries',
                'icon' => '✍️',
                'type' => 'recurring',
                'recurrence' => 'daily',
                'start_date' => now()->subWeek(),
                'status' => 'in_progress',
                'priority' => 'low',
                'points' => 35,
            ]);
            $bar->advance();

            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[1]->id,
                'title' => 'Network with professionals',
                'description' => 'Connect with 50 people on LinkedIn',
                'icon' => '🤝',
                'type' => 'quantifiable',
                'target_value' => 50,
                'current_value' => 12,
                'unit' => 'connections',
                'start_date' => now()->subMonths(2),
                'status' => 'paused',
                'priority' => 'low',
                'points' => 20,
            ]);
            $bar->advance();

            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[3]->id,
                'title' => 'Pay off credit card',
                'description' => 'Eliminate €5,000 debt',
                'icon' => '💳',
                'type' => 'quantifiable',
                'target_value' => 5000,
                'current_value' => 800,
                'unit' => '€ paid',
                'start_date' => now()->subMonths(5),
                'deadline' => now()->addMonths(7),
                'status' => 'in_progress',
                'priority' => 'high',
                'points' => 45,
            ]);
            $bar->advance();

            Goal::create([
                'user_id' => $demoUser->id,
                'category_id' => $createdCategories[0]->id,
                'title' => 'Quit smoking',
                'description' => 'Stopped after 5 years',
                'icon' => '🚭',
                'type' => 'simple',
                'start_date' => now()->subMonths(8),
                'completed_at' => now()->subMonths(2),
                'status' => 'completed',
                'priority' => 'high',
                'points' => 300,
            ]);
            $bar->advance();

            $bar->finish();
            $this->command->newLine(2);

            // Create goals for active user
            $this->command->info('🎯 Creating goals for active user...');
            $bar = $this->command->getOutput()->createProgressBar(5);

            for ($i = 0; $i < 5; $i++) {
                Goal::factory()
                    ->for($activeUser)
                    ->inProgress()
                    ->create();
                $bar->advance();
            }

            $bar->finish();
            $this->command->newLine(2);

            // New user has no goals (clean slate)
            $this->command->info('👶 New user has no goals (clean slate)');
            $this->command->newLine();

            // Create achievements
            $this->command->info('🏆 Creating achievements...');
            $achievements = [
                ['name' => 'First Steps', 'slug' => 'first-steps', 'description' => 'Complete your first goal', 'icon' => '👣', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 1], 'rarity' => 'common'],
                ['name' => 'Goal Getter', 'slug' => 'goal-getter', 'description' => 'Complete 5 goals', 'icon' => '🎯', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 5], 'rarity' => 'rare'],
                ['name' => 'Achiever', 'slug' => 'achiever', 'description' => 'Complete 10 goals', 'icon' => '🏆', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 10], 'rarity' => 'epic'],
                ['name' => 'Master', 'slug' => 'master', 'description' => 'Complete 25 goals', 'icon' => '👑', 'type' => 'goal_completion', 'criteria' => ['goals_completed' => 25], 'rarity' => 'legendary'],
                ['name' => 'Week Streak', 'slug' => 'week-streak', 'description' => 'Maintain a 7-day streak', 'icon' => '🔥', 'type' => 'streak', 'criteria' => ['streak_days' => 7], 'rarity' => 'common'],
                ['name' => 'Month Streak', 'slug' => 'month-streak', 'description' => 'Maintain a 30-day streak', 'icon' => '🌟', 'type' => 'streak', 'criteria' => ['streak_days' => 30], 'rarity' => 'rare'],
                ['name' => 'Point Collector', 'slug' => 'point-collector', 'description' => 'Earn 1000 points', 'icon' => '💎', 'type' => 'points', 'criteria' => ['points_earned' => 1000], 'rarity' => 'rare'],
                ['name' => 'Consistent', 'slug' => 'consistent', 'description' => 'Log progress for 30 consecutive days', 'icon' => '📅', 'type' => 'consistency', 'criteria' => ['consecutive_days' => 30], 'rarity' => 'epic'],
            ];

            $bar = $this->command->getOutput()->createProgressBar(count($achievements));

            foreach ($achievements as $index => $achievement) {
                Achievement::create([
                    'name' => $achievement['name'],
                    'slug' => $achievement['slug'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'type' => $achievement['type'],
                    'criteria' => $achievement['criteria'],
                    'points_reward' => match ($achievement['rarity']) {
                        'common' => 50,
                        'rare' => 100,
                        'epic' => 250,
                        'legendary' => 500,
                    },
                    'rarity' => $achievement['rarity'],
                    'order' => $index,
                    'is_active' => true,
                ]);
                $bar->advance();
            }

            $bar->finish();
            $this->command->newLine(2);

            DB::commit();

            $this->command->newLine();
            $this->command->info('✅ Data initialization completed successfully!');
            $this->command->newLine();
            $this->command->table(
                ['User', 'Email', 'Password', 'Goals'],
                [
                    ['Demo User (full data)', 'demo@ignite.test', 'password', '12 goals'],
                    ['Active User', 'active@ignite.test', 'password', '5 goals'],
                    ['New User (empty)', 'new@ignite.test', 'password', '0 goals'],
                ]
            );
            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error during seeding: '.$e->getMessage());
            $this->command->error($e->getTraceAsString());
            throw $e;
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RevertDataSeeder extends Seeder
{
    /**
     * Revert all data created by InitDataSeeder.
     */
    public function run(): void
    {
        $this->command->warn('⚠️  This will delete all seeded data!');
        $this->command->newLine();

        if (!$this->command->confirm('Are you sure you want to continue?', false)) {
            $this->command->info('Aborted.');
            return;
        }

        $this->command->newLine();
        $this->command->info('🗑️  Starting data cleanup...');
        $this->command->newLine();

        DB::beginTransaction();

        try {
            // Delete in reverse order of creation (respecting foreign keys)

            $this->command->info('🗑️  Deleting goal entries...');
            $entriesCount = GoalEntry::count();
            GoalEntry::truncate();
            $this->command->info("   Deleted {$entriesCount} goal entries");
            $this->command->newLine();

            $this->command->info('🗑️  Deleting milestones...');
            $milestonesCount = Milestone::count();
            Milestone::truncate();
            $this->command->info("   Deleted {$milestonesCount} milestones");
            $this->command->newLine();

            $this->command->info('🗑️  Deleting user achievements...');
            $userAchievementsCount = UserAchievement::count();
            UserAchievement::truncate();
            $this->command->info("   Deleted {$userAchievementsCount} user achievements");
            $this->command->newLine();

            $this->command->info('🗑️  Deleting goals...');
            $goalsCount = Goal::count();
            Goal::truncate();
            $this->command->info("   Deleted {$goalsCount} goals");
            $this->command->newLine();

            $this->command->info('🗑️  Deleting categories...');
            $categoriesCount = Category::count();
            Category::truncate();
            $this->command->info("   Deleted {$categoriesCount} categories");
            $this->command->newLine();

            $this->command->info('🗑️  Deleting achievements...');
            $achievementsCount = Achievement::count();
            Achievement::truncate();
            $this->command->info("   Deleted {$achievementsCount} achievements");
            $this->command->newLine();

            $this->command->info('🗑️  Deleting test users...');
            $testEmails = ['demo@ignite.test', 'active@ignite.test', 'new@ignite.test'];
            $usersCount = User::whereIn('email', $testEmails)->count();
            User::whereIn('email', $testEmails)->delete();
            $this->command->info("   Deleted {$usersCount} test users");
            $this->command->newLine();

            DB::commit();

            $this->command->newLine();
            $this->command->info('✅ All seeded data has been removed successfully!');
            $this->command->newLine();

            $this->command->table(
                ['Model', 'Deleted Count'],
                [
                    ['Goal Entries', $entriesCount],
                    ['Milestones', $milestonesCount],
                    ['User Achievements', $userAchievementsCount],
                    ['Goals', $goalsCount],
                    ['Categories', $categoriesCount],
                    ['Achievements', $achievementsCount],
                    ['Test Users', $usersCount],
                    ['TOTAL', $entriesCount + $milestonesCount + $userAchievementsCount + $goalsCount + $categoriesCount + $achievementsCount + $usersCount],
                ]
            );
            $this->command->newLine();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Error during cleanup: ' . $e->getMessage());
            throw $e;
        }
    }
}

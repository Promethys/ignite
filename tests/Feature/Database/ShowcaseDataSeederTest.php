<?php

namespace Tests\Feature\Database;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use App\Services\Goals\GoalEntryService;
use App\Services\Goals\GoalService;
use App\Services\Goals\MilestoneService;
use App\Services\StreakService;
use Carbon\Carbon;
use Database\Seeders\ShowcaseDataSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ShowcaseDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_production_without_writing_anything(): void
    {
        $this->app['env'] = 'production';

        try {
            Artisan::call('db:seed', ['--class' => ShowcaseDataSeeder::class, '--force' => true]);
            $this->fail('The showcase seeder must refuse production.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Showcase data can only be seeded in local or testing environments.', $exception->getMessage());
        }

        foreach (['users', 'categories', 'goals', 'goal_entries', 'milestones', 'user_data_keys'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
    }

    public function test_it_creates_the_verified_demo_account_and_expected_goal_statuses(): void
    {
        Event::fake([Registered::class]);

        $this->seed(ShowcaseDataSeeder::class);

        $user = User::where('email', 'showcase@ignite.test')->firstOrFail();

        $this->assertSame('John Doe', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->data_key_id);
        $this->assertSame('en', $user->locale);
        $this->assertSame('Europe/Paris', $user->timezone);
        $this->assertSame(10, $user->categories()->count());
        $this->assertSame(10, $user->goals()->count());
        $this->assertSame([
            'completed' => 2,
            'in_progress' => 6,
            'not_started' => 1,
            'paused' => 1,
        ], $user->goals()->get()->countBy('status')->sortKeys()->all());
        Event::assertNotDispatched(Registered::class);
    }

    public function test_rerunning_preserves_the_account_categories_and_deterministic_dataset(): void
    {
        $this->travelTo(Carbon::parse('2026-11-07 12:00:00', 'Europe/Paris'));
        $this->seed(ShowcaseDataSeeder::class);
        $user = User::where('email', 'showcase@ignite.test')->firstOrFail();
        $attributes = $user->getRawOriginal();
        $categories = $user->categories()->get()->map->getRawOriginal()->all();
        $dataset = $this->dataset($user);

        $this->seed(ShowcaseDataSeeder::class);

        $this->assertSame($attributes, $user->fresh()->getRawOriginal());
        $this->assertSame($categories, $user->categories()->get()->map->getRawOriginal()->all());
        $this->assertSame($dataset, $this->dataset($user));
        $this->assertDatabaseCount('goals', 10);
        $this->assertDatabaseCount('goal_entries', 70);
        $this->assertDatabaseCount('milestones', 6);
    }

    public function test_it_leaves_another_users_data_untouched(): void
    {
        $user = User::factory()->create();
        $category = $user->categories()->create(['name' => 'My category', 'color' => '#123456']);
        $goal = app(GoalService::class)->create($user, [
            'title' => 'My goal', 'type' => 'quantifiable', 'target_value' => 100, 'category_id' => $category->id,
        ]);
        $entry = app(GoalEntryService::class)->logProgress($user, $goal, 12);
        $milestone = app(MilestoneService::class)->add($user, $goal, ['title' => 'My milestone']);
        $models = [$user, $category, $goal->refresh(), $entry, $milestone];
        $original = array_map(fn ($model) => $model->fresh()->getRawOriginal(), $models);

        $this->seed(ShowcaseDataSeeder::class);
        $this->seed(ShowcaseDataSeeder::class);

        $this->assertSame($original, array_map(fn ($model) => $model->fresh()->getRawOriginal(), $models));
    }

    public function test_streaks_and_history_use_today_in_paris_even_after_utc_midnight_differs(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 23:30:00', 'UTC'));
        $this->seed(ShowcaseDataSeeder::class);
        $goals = User::where('email', 'showcase@ignite.test')->firstOrFail()->goals()->get()->keyBy('title');
        $meditation = $goals['Meditate every morning'];
        $strength = $goals['Strength training three times a week'];
        $meditationStreak = StreakService::for($meditation);
        $strengthStreak = StreakService::for($strength);

        $this->assertSame(10, $meditationStreak->current);
        $this->assertSame(12, $meditationStreak->longest);
        $this->assertTrue($meditationStreak->currentPeriodSatisfied);
        $this->assertSame('2026-11-09', $meditation->entries()->latest('entry_date')->firstOrFail()->entry_date->toDateString());
        $this->assertSame(7, $strengthStreak->current);
        $this->assertSame(7, $strengthStreak->longest);
        $this->assertTrue($strengthStreak->currentPeriodSatisfied);
    }

    public function test_progress_steps_and_completion_dates_match_the_showcase(): void
    {
        $this->travelTo(Carbon::parse('2026-11-07 12:00:00', 'Europe/Paris'));
        $this->seed(ShowcaseDataSeeder::class);
        $goals = User::where('email', 'showcase@ignite.test')->firstOrFail()->goals()->get()->keyBy('title');
        $run = $goals['Run a half marathon'];
        $portfolio = $goals['Launch the freelance portfolio site'];

        $this->assertEquals(247, $run->current_value);
        $this->assertEquals(400, $run->target_value);
        $this->assertSame('2026-11-02', $run->entries()->latest('entry_date')->firstOrFail()->entry_date->toDateString());
        $this->assertSame([12, 14, 15, 16, 13, 18, 16, 11, 19, 17, 14, 18, 12, 16, 9, 15, 12],
            $run->entries()->orderBy('entry_date')->get()->map(fn (GoalEntry $entry) => (int) ($entry->value - $entry->previous_value))->all());
        $this->assertEquals(580, $goals['Learn conversational Japanese']->current_value);
        $this->assertEquals(3400, $goals['Build a six month emergency fund']->current_value);
        $this->assertSame(6, $portfolio->milestones()->count());
        $this->assertSame(4, $portfolio->milestones()->whereNotNull('completed_at')->count());
        $this->assertTrue($portfolio->milestones()->whereNotNull('completed_at')->get()->every(fn (Milestone $step) => $step->completed_at->lt(now())));
        $this->assertSame('2026-05-07', $goals['Run a 10K without stopping']->completed_at->toDateString());
        $this->assertSame('2026-08-07', $goals['Read 12 books this year']->completed_at->toDateString());
        $this->assertTrue($goals->where('status', '!=', 'completed')->every(fn (Goal $goal) => $goal->deadline === null || $goal->deadline->toDateString() >= '2026-11-07'));
    }

    private function dataset(User $user): array
    {
        $dataset = $user->goals()->orderBy('order')->get()->map(fn (Goal $goal) => [
            $goal->only(['title', 'description', 'category_id', 'type', 'status', 'priority', 'current_value', 'target_value', 'unit', 'recurrence', 'start_date', 'deadline', 'completed_at']),
            $goal->entries()->orderBy('entry_date')->get()->map->only(['value', 'previous_value', 'note', 'entry_date'])->all(),
            $goal->milestones()->orderBy('order')->get()->map->only(['title', 'order', 'completed_at'])->all(),
        ])->toArray();

        return json_decode(json_encode($dataset, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}

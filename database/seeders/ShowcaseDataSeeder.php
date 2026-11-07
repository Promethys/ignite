<?php

namespace Database\Seeders;

use App\Listeners\GenerateDefaultUserCategories;
use App\Models\User;
use App\Services\Goals\GoalEntryService;
use App\Services\Goals\GoalService;
use App\Services\Goals\MilestoneService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShowcaseDataSeeder extends Seeder
{
    public function run(GoalService $goals, GoalEntryService $entries, MilestoneService $milestones): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Showcase data can only be seeded in local or testing environments.');
        }

        DB::transaction(function () use ($goals, $entries, $milestones): void {
            $user = User::firstOrCreate(['email' => 'showcase@ignite.test'], [
                'name' => 'John Doe',
                'password' => 'password',
                'email_verified_at' => now(),
                'locale' => 'en',
                'timezone' => 'Europe/Paris',
            ]);

            $today = CarbonImmutable::today('Europe/Paris');

            $user->update(['timezone' => 'Europe/Paris', 'locale' => 'en']);

            if (! $user->categories()->exists()) {
                app(GenerateDefaultUserCategories::class)->handle(new Registered($user));
            }

            $categories = $user->categories()->get()->keyBy('name');
            $user->goals()->delete();

            foreach ($this->goalDefinitions() as $definition) {
                $category = $categories->get($definition['category']);
                $attributes = $definition['attributes'];
                $attributes['category_id'] = $category?->id;

                foreach (['start_date', 'deadline'] as $field) {
                    if (isset($attributes[$field])) {
                        $attributes[$field] = $this->shiftDate($today, $attributes[$field]);
                    }
                }

                $goal = $goals->create($user, $attributes);

                foreach ($definition['progress'] ?? [] as $index => $increment) {
                    $date = match (true) {
                        isset($definition['completed_months_ago']) => $today->subMonthsNoOverflow($definition['completed_months_ago'])->toDateString(),
                        isset($definition['dates']) => $this->shiftDate($today, $definition['dates'][$index]),
                        default => $today->subDays(5)->subWeeks(16 - $index)->toDateString(),
                    };

                    $entries->logProgress($user, $goal, $increment, $definition['note'], $date);
                }

                foreach ($definition['check_ins'] ?? [] as $daysAgo) {
                    $entries->recordCheckIn($user, $goal, $today->subDays($daysAgo)->toDateString());
                }

                if (isset($definition['weekly_check_ins'])) {
                    for ($week = $definition['weekly_check_ins'] - 1; $week >= 0; $week--) {
                        $entries->recordCheckIn($user, $goal, $today->startOfWeek()->subWeeks($week)->toDateString());
                    }
                }

                foreach ($definition['steps'] ?? [] as $index => $title) {
                    $milestone = $milestones->add($user, $goal, ['title' => $title]);

                    if ($index < 4) {
                        $milestones->complete($user, $milestone);
                        $milestone->update(['completed_at' => $today->subDays(75 - $index * 18)]);
                    }
                }

                if (isset($definition['completed_months_ago'])) {
                    $goals->complete($user, $goal);
                    $goal->update(['completed_at' => $today->subMonthsNoOverflow($definition['completed_months_ago'])]);
                } elseif (isset($definition['status'])) {
                    $goals->setStatus($user, $goal, $definition['status']);
                }
            }
        });
    }

    private function shiftDate(CarbonImmutable $today, string $date): string
    {
        $reference = CarbonImmutable::parse('2026-08-12', 'Europe/Paris');
        $offset = (int) $reference->diffInDays(CarbonImmutable::parse($date, 'Europe/Paris'));

        return $today->addDays($offset)->toDateString();
    }

    private function goalDefinitions(): array
    {
        return [
            [
                'category' => 'Health & Fitness',
                'attributes' => [
                    'title' => 'Run a half marathon',
                    'description' => 'Build up to 21 km by the autumn race. Training distance is what I track, not race day itself.',
                    'type' => 'quantifiable',
                    'target_value' => 400,
                    'unit' => 'km',
                    'start_date' => '2026-04-06',
                    'deadline' => '2026-11-08',
                    'priority' => 'high',
                ],
                'status' => 'in_progress',
                'progress' => [12, 14, 15, 16, 13, 18, 16, 11, 19, 17, 14, 18, 12, 16, 9, 15, 12],
                'note' => 'Long run',
            ],
            [
                'category' => 'Learning & Education',
                'attributes' => [
                    'title' => 'Learn conversational Japanese',
                    'description' => 'Core vocabulary for holding a real conversation. Counting words I can recall unprompted, not words I have seen.',
                    'type' => 'quantifiable',
                    'target_value' => 1000,
                    'unit' => 'words',
                    'start_date' => '2026-02-16',
                    'deadline' => '2027-02-15',
                ],
                'status' => 'in_progress',
                'progress' => [45, 60, 55, 48, 52, 40, 65, 58, 47, 62, 48],
                'dates' => ['2026-02-22', '2026-03-08', '2026-03-22', '2026-04-05', '2026-04-19', '2026-05-03', '2026-05-24', '2026-06-07', '2026-06-28', '2026-07-12', '2026-08-02'],
                'note' => 'Deck review',
            ],
            [
                'category' => 'Finance & Money',
                'attributes' => [
                    'title' => 'Build a six month emergency fund',
                    'description' => 'Six months of essential expenses held in an instant access account.',
                    'type' => 'quantifiable',
                    'target_value' => 9000,
                    'unit' => 'EUR',
                    'start_date' => '2026-01-05',
                    'deadline' => '2027-06-30',
                    'priority' => 'high',
                ],
                'status' => 'in_progress',
                'progress' => [400, 400, 350, 400, 450, 400, 500, 500],
                'dates' => ['2026-01-31', '2026-02-28', '2026-03-31', '2026-04-30', '2026-05-31', '2026-06-30', '2026-07-31', '2026-08-07'],
                'note' => 'Monthly transfer',
            ],
            [
                'category' => 'Personal Development',
                'attributes' => [
                    'title' => 'Meditate every morning',
                    'description' => 'Ten minutes before checking any screen.',
                    'type' => 'recurring',
                    'recurrence' => 'daily',
                    'start_date' => '2026-06-01',
                ],
                'status' => 'in_progress',
                'check_ins' => [...range(28, 17), ...range(9, 0)],
            ],
            [
                'category' => 'Health & Fitness',
                'attributes' => [
                    'title' => 'Strength training three times a week',
                    'description' => 'Full body sessions. One check-in per week once all three are done.',
                    'type' => 'recurring',
                    'recurrence' => 'weekly',
                    'start_date' => '2026-03-02',
                ],
                'status' => 'in_progress',
                'weekly_check_ins' => 7,
            ],
            [
                'category' => 'Career & Work',
                'attributes' => [
                    'title' => 'Launch the freelance portfolio site',
                    'description' => 'From empty repo to a live site with three case studies.',
                    'type' => 'multi_step',
                    'start_date' => '2026-05-18',
                    'deadline' => '2026-09-30',
                    'priority' => 'high',
                ],
                'status' => 'in_progress',
                'steps' => [
                    'Pick the stack and register the domain',
                    'Design the case study layout',
                    'Write the first case study',
                    'Write the second case study',
                    'Build the contact form',
                    'Deploy and point the domain',
                ],
            ],
            [
                'category' => 'Health & Fitness',
                'attributes' => [
                    'title' => 'Book the annual health checkup',
                    'description' => 'Overdue by two years. Just make the call.',
                    'type' => 'simple',
                    'deadline' => '2026-09-15',
                    'priority' => 'low',
                ],
            ],
            [
                'category' => 'Personal Development',
                'attributes' => [
                    'title' => 'Learn to play the guitar',
                    'description' => 'Paused while the half marathon training is taking the evenings.',
                    'type' => 'recurring',
                    'recurrence' => 'weekly',
                    'start_date' => '2026-02-02',
                    'priority' => 'low',
                ],
                'status' => 'paused',
                'check_ins' => [140, 133, 126],
            ],
            [
                'category' => 'Health & Fitness',
                'attributes' => [
                    'title' => 'Run a 10K without stopping',
                    'description' => 'The stepping stone before the half marathon block started.',
                    'type' => 'quantifiable',
                    'target_value' => 10,
                    'unit' => 'km',
                ],
                'progress' => [10],
                'note' => null,
                'completed_months_ago' => 6,
            ],
            [
                'category' => 'Learning & Education',
                'attributes' => [
                    'title' => 'Read 12 books this year',
                    'description' => 'Finished early, so the target moves up next year.',
                    'type' => 'quantifiable',
                    'target_value' => 12,
                    'unit' => 'books',
                    'priority' => 'low',
                ],
                'progress' => [12],
                'note' => null,
                'completed_months_ago' => 3,
            ],
        ];
    }
}

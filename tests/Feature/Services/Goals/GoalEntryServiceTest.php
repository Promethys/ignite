<?php

namespace Tests\Feature\Services\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use App\Services\Goals\GoalEntryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GoalEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoalEntryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(GoalEntryService::class);
    }

    public function test_find_returns_the_entry_for_the_goal_owner(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $found = $this->service->find($owner, $entry->id);

        $this->assertTrue($found->is($entry));
    }

    public function test_find_throws_for_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->find($intruder, $entry->id);
    }

    public function test_find_throws_model_not_found_for_a_missing_id(): void
    {
        $owner = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        $this->service->find($owner, 999999);
    }

    public function test_find_returns_the_same_instance_when_given_a_resolved_model(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $found = $this->service->find($owner, $entry);

        $this->assertSame(spl_object_id($entry), spl_object_id($found));
    }

    public function test_log_progress_increments_the_goal_and_records_the_previous_value(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'quantifiable',
            'current_value' => 20,
            'target_value' => 100,
        ]);

        $entry = $this->service->logProgress($owner, $goal, 5, 'note');

        $this->assertSame(25.0, (float) $goal->fresh()->current_value);
        $this->assertSame(25.0, (float) $entry->value);
        $this->assertSame(20.0, (float) $entry->previous_value);
        $this->assertSame(now()->toDateString(), Carbon::parse($entry->entry_date)->toDateString());
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function ownerTodayProvider(): array
    {
        return [
            'east of UTC, already tomorrow' => ['Pacific/Auckland', '2026-09-15 23:30:00', '2026-09-16'],
            'west of UTC, still yesterday' => ['Pacific/Honolulu', '2026-09-16 02:00:00', '2026-09-15'],
        ];
    }

    #[DataProvider('ownerTodayProvider')]
    public function test_log_progress_without_a_date_uses_the_owners_today(string $timezone, string $utcNow, string $ownerToday): void
    {
        Carbon::setTestNow($utcNow);
        $owner = User::factory()->create(['timezone' => $timezone]);
        $goal = $this->quantifiableGoal($owner);

        $entry = $this->service->logProgress($owner, $goal, 5);

        $this->assertSame($ownerToday, Carbon::parse($entry->entry_date)->toDateString());
    }

    #[DataProvider('ownerTodayProvider')]
    public function test_log_progress_batch_without_a_date_uses_the_owners_today(string $timezone, string $utcNow, string $ownerToday): void
    {
        Carbon::setTestNow($utcNow);
        $owner = User::factory()->create(['timezone' => $timezone]);
        $goal = $this->quantifiableGoal($owner);

        $result = $this->service->logProgressBatch($owner, $goal, [['increment' => 5]]);

        $this->assertSame($ownerToday, $result['first_date']);
        $this->assertSame([[$ownerToday, 100.0, 105.0]], $this->entryRows($goal));
    }

    public function test_log_progress_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->logProgress($intruder, $goal, 5);
    }

    public function test_log_progress_rejects_a_recurring_goal(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->logProgress($owner, $goal, 5);
    }

    public function test_record_check_in_rejects_a_non_recurring_goal(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'quantifiable',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->recordCheckIn($owner, $goal, '2026-07-20');
    }

    public function test_record_check_in_creates_a_dated_entry_without_touching_current_value(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
            'current_value' => 3,
        ]);

        $entry = $this->service->recordCheckIn($owner, $goal, '2026-07-20', 'showed up');

        $this->assertSame('2026-07-20', Carbon::parse($entry->entry_date)->toDateString());
        $this->assertSame(1.0, (float) $entry->value);
        $this->assertSame(0.0, (float) $entry->previous_value);
        $this->assertSame(3.0, (float) $goal->fresh()->current_value);
    }

    public function test_record_check_in_rejects_a_second_entry_in_the_same_daily_period(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);
        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => '2026-07-20',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->recordCheckIn($owner, $goal, '2026-07-20');
    }

    public function test_record_check_in_buckets_a_duplicate_by_the_recurrence_period(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'weekly',
        ]);
        $monday = Carbon::parse('2026-07-13')->startOfWeek();
        GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'entry_date' => $monday->toDateString(),
        ]);

        $this->expectException(ValidationException::class);

        $this->service->recordCheckIn($owner, $goal, $monday->copy()->addDays(2)->toDateString());
    }

    public function test_record_check_in_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        $this->expectException(AuthorizationException::class);

        $this->service->recordCheckIn($intruder, $goal, '2026-07-20');
    }

    public function test_update_entry_preserves_the_goal_value_when_a_historical_entry_changes(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'quantifiable',
            'current_value' => 100,
            'target_value' => 1000,
        ]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'previous_value' => 50,
            'value' => 60,
        ]);

        $updated = $this->service->updateEntry($owner, $entry, 25, 'edited');

        $this->assertSame(75.0, (float) $updated->value);
        $this->assertSame(115.0, (float) $goal->fresh()->current_value);
        $this->assertSame('edited', $updated->note);
    }

    public function test_update_entry_keeps_the_existing_note_when_none_is_given(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'quantifiable',
            'current_value' => 100,
            'target_value' => 1000,
        ]);
        $entry = GoalEntry::factory()->create([
            'goal_id' => $goal->id,
            'previous_value' => 50,
            'value' => 60,
            'note' => 'keep me',
        ]);

        $this->service->updateEntry($owner, $entry, 25);

        $this->assertSame('keep me', $entry->fresh()->note);
    }

    public function test_update_entry_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);
        $entry = GoalEntry::factory()->create(['goal_id' => $goal->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->updateEntry($intruder, $entry, 25);
    }

    public function test_log_progress_batch_applies_entries_in_date_order_with_running_values(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');
        $owner = User::factory()->create();
        $goal = $this->quantifiableGoal($owner);

        $result = $this->service->logProgressBatch($owner, $goal, [
            ['increment' => 20, 'entry_date' => '2026-09-02'],
            ['increment' => 10, 'entry_date' => '2026/09/01', 'note' => 'first'],
            ['increment' => 5],
        ]);

        $this->assertSame([
            ['2026-09-01', 100.0, 110.0, 'first'],
            ['2026-09-02', 110.0, 130.0, null],
            ['2026-09-15', 130.0, 135.0, null],
        ], $this->entryRows($goal, withNote: true));
        $this->assertSame(135.0, (float) $goal->fresh()->current_value);
        $this->assertSame(3, $result['count']);
        $this->assertSame('2026-09-01', $result['first_date']);
        $this->assertSame('2026-09-15', $result['last_date']);
    }

    public function test_log_progress_batch_shifts_existing_later_entries(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');
        $owner = User::factory()->create();
        $goal = $this->quantifiableGoal($owner);
        $this->service->logProgress($owner, $goal, 50, null, '2026-09-10');

        $this->service->logProgressBatch($owner, $goal->fresh(), [
            ['increment' => 10, 'entry_date' => '2026-09-01'],
            ['increment' => 20, 'entry_date' => '2026-09-05'],
        ]);

        $this->assertSame([
            ['2026-09-01', 100.0, 110.0],
            ['2026-09-05', 110.0, 130.0],
            ['2026-09-10', 130.0, 180.0],
        ], $this->entryRows($goal));
        $this->assertSame(180.0, (float) $goal->fresh()->current_value);
    }

    public function test_log_progress_batch_does_not_complete_a_goal_that_only_crosses_its_target_midway(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');
        $owner = User::factory()->create();
        $goal = $this->quantifiableGoal($owner, ['target_value' => 150]);

        $result = $this->service->logProgressBatch($owner, $goal, [
            ['increment' => 100, 'entry_date' => '2026-09-01'],
            ['increment' => -80, 'entry_date' => '2026-09-02'],
        ]);

        $this->assertSame('in_progress', $result['goal_status']);
        $this->assertSame('in_progress', $goal->fresh()->status);
    }

    public function test_log_progress_batch_completes_a_goal_whose_final_value_reaches_its_target(): void
    {
        Carbon::setTestNow('2026-09-15 12:00:00');
        $owner = User::factory()->create();
        $goal = $this->quantifiableGoal($owner, ['target_value' => 150]);

        $result = $this->service->logProgressBatch($owner, $goal, [
            ['increment' => 30, 'entry_date' => '2026-09-01'],
            ['increment' => 30, 'entry_date' => '2026-09-02'],
        ]);

        $this->assertSame('completed', $result['goal_status']);
        $this->assertNotNull($goal->fresh()->completed_at);
    }

    public function test_log_progress_batch_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = $this->quantifiableGoal($owner);

        $this->expectException(AuthorizationException::class);

        $this->service->logProgressBatch($intruder, $goal, [['increment' => 5]]);
    }

    public function test_log_progress_batch_rejects_a_recurring_goal(): void
    {
        $owner = User::factory()->create();
        $goal = Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'recurring',
            'recurrence' => 'daily',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->logProgressBatch($owner, $goal, [['increment' => 5]]);
    }

    private function quantifiableGoal(User $owner, array $attributes = []): Goal
    {
        return Goal::factory()->create([
            'user_id' => $owner->id,
            'type' => 'quantifiable',
            'direction' => 'ascending',
            'status' => 'in_progress',
            'completed_at' => null,
            'current_value' => 100,
            'target_value' => 1000,
            'start_date' => '2026-01-01',
            ...$attributes,
        ]);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function entryRows(Goal $goal, bool $withNote = false): array
    {
        return $goal->entries()->orderBy('entry_date')->get()
            ->map(fn (GoalEntry $entry): array => [
                Carbon::parse($entry->entry_date)->toDateString(),
                (float) $entry->previous_value,
                (float) $entry->value,
                ...($withNote ? [$entry->note] : []),
            ])
            ->all();
    }
}

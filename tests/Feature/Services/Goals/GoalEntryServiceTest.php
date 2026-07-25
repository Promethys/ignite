<?php

namespace Tests\Feature\Services\Goals;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use App\Services\Goals\GoalEntryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
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

    public function test_log_progress_denies_a_non_owner(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        $this->expectException(AuthorizationException::class);

        $this->service->logProgress($intruder, $goal, 5);
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
}

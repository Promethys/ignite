<?php

namespace Tests\Feature\Traits;

use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HasRecentScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-07-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_recent_scope_only_returns_records_from_the_last_week()
    {
        User::factory()->create(['created_at' => '2026-07-04 12:00:00']); // 11 days ago
        User::factory()->create(['created_at' => '2026-07-12 12:00:00']); // 3 days ago

        $this->assertEquals(1, User::recent()->count());
    }

    public function test_the_recent_scope_accepts_a_custom_column()
    {
        $goal = Goal::factory()->create();

        GoalEntry::factory()->create(['goal_id' => $goal->id, 'entry_date' => '2026-07-04']); // old
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'entry_date' => '2026-07-12']); // recent

        // Default column (created_at) would keep both; filtering on entry_date drops the old one.
        $this->assertEquals(1, GoalEntry::recent('entry_date')->count());
    }

    public function test_the_recent_scope_boundary_is_exclusive()
    {
        // Exactly 7 days ago: the boundary is `>`, so it is excluded.
        User::factory()->create(['created_at' => Carbon::now()->subDays(7)]); // excluded
        User::factory()->create(['created_at' => Carbon::now()->subDays(6)]); // included

        $this->assertEquals(1, User::recent()->count());
    }
}

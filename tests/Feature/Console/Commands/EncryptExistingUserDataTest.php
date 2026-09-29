<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use App\Services\Encryption\UserDataCipher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EncryptExistingUserDataTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;

    private int $goalId;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        $this->userId = DB::table('users')->insertGetId(['name' => 'Alice Martin', 'email' => 'alice@example.com', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now]);
        $categoryId = DB::table('categories')->insertGetId(['user_id' => $this->userId, 'name' => 'Santé', 'description' => 'Sport et sommeil', 'created_at' => $now, 'updated_at' => $now]);
        $this->goalId = DB::table('goals')->insertGetId(['user_id' => $this->userId, 'category_id' => $categoryId, 'title' => 'Run a marathon', 'description' => null, 'unit' => 'km', 'type' => 'quantifiable', 'status' => 'in_progress', 'priority' => 'medium', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('goals')->insert(['user_id' => $this->userId, 'title' => 'v1:not really encrypted', 'type' => 'simple', 'status' => 'in_progress', 'priority' => 'low', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('goal_entries')->insert(['goal_id' => $this->goalId, 'value' => 5, 'note' => 'Première sortie', 'entry_date' => $now->toDateString(), 'created_at' => $now, 'updated_at' => $now]);
        DB::table('milestones')->insert(['goal_id' => $this->goalId, 'title' => 'Half marathon', 'description' => 'First 21 km', 'order' => 1, 'created_at' => $now, 'updated_at' => $now]);
    }

    /**
     * @return list<string>
     */
    private function unencryptedValues(): array
    {
        $columns = [
            'users' => ['name'],
            'goals' => ['title', 'description', 'unit'],
            'categories' => ['name', 'description'],
            'goal_entries' => ['note'],
            'milestones' => ['title', 'description'],
        ];

        return collect($columns)->flatMap(fn (array $tableColumns, string $table) => collect($tableColumns)->flatMap(
            fn (string $column) => DB::table($table)->whereNotNull($column)->where($column, 'not like', UserDataCipher::PREFIX.'%')->pluck($column)
        ))->values()->all();
    }

    public function test_it_gives_every_user_a_key_and_encrypts_every_existing_value()
    {
        $this->artisan('app:encrypt-existing-user-data')->assertSuccessful();

        $this->assertNotNull(DB::table('users')->where('id', $this->userId)->value('data_key_id'));
        $this->assertSame([], $this->unencryptedValues());
    }

    public function test_the_models_read_the_encrypted_rows_back_in_clear()
    {
        $this->artisan('app:encrypt-existing-user-data');

        $goal = Goal::with(['entries', 'milestones'])->find($this->goalId);

        $this->assertSame('Alice Martin', User::find($this->userId)->name);
        $this->assertSame('Run a marathon', $goal->title);
        $this->assertNull($goal->description);
        $this->assertSame('km', $goal->unit);
        $this->assertSame('Santé', $goal->category->name);
        $this->assertSame('Première sortie', $goal->entries->first()->note);
        $this->assertSame('Half marathon', $goal->milestones->first()->title);
    }

    public function test_plaintext_that_starts_with_the_prefix_is_still_encrypted()
    {
        $this->artisan('app:encrypt-existing-user-data');

        $this->assertSame('v1:not really encrypted', Goal::where('type', 'simple')->firstOrFail()->title);
    }

    public function test_a_second_run_changes_nothing()
    {
        $this->artisan('app:encrypt-existing-user-data');
        $snapshot = [
            DB::table('users')->pluck('name', 'id'),
            DB::table('goals')->pluck('title', 'id'),
            DB::table('goal_entries')->pluck('note', 'id'),
            DB::table('categories')->pluck('name', 'id'),
            DB::table('milestones')->pluck('title', 'id'),
        ];

        $this->artisan('app:encrypt-existing-user-data')
            ->expectsOutputToContain('Data keys assigned: 0')
            ->assertSuccessful();

        $this->assertEquals($snapshot, [
            DB::table('users')->pluck('name', 'id'),
            DB::table('goals')->pluck('title', 'id'),
            DB::table('goal_entries')->pluck('note', 'id'),
            DB::table('categories')->pluck('name', 'id'),
            DB::table('milestones')->pluck('title', 'id'),
        ]);
    }

    public function test_rows_written_through_the_models_are_left_alone()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id]);
        $goal = Goal::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);
        GoalEntry::factory()->create(['goal_id' => $goal->id, 'note' => 'Already encrypted']);
        Milestone::factory()->create(['goal_id' => $goal->id]);
        $before = DB::table('goals')->where('id', $goal->id)->value('title');

        $this->artisan('app:encrypt-existing-user-data');

        $this->assertSame($before, DB::table('goals')->where('id', $goal->id)->value('title'));
    }
}

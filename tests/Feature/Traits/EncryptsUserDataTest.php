<?php

namespace Tests\Feature\Traits;

use App\Exceptions\UserDataEncryptionException;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use App\Services\Encryption\UserDataCipher;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EncryptsUserDataTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Goal $goal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Alice Martin']);
        $this->goal = Goal::factory()->create([
            'user_id' => $this->owner->id,
            'category_id' => null,
            'title' => 'Run a marathon',
            'description' => 'Paris, avril',
            'unit' => 'km',
        ]);
    }

    private function storedValue(Model $model, string $column): ?string
    {
        return DB::table($model->getTable())->where('id', $model->getKey())->value($column);
    }

    private function decryptWithOwnerKey(string $storedValue): string
    {
        return app(UserDataCipher::class)->decrypt($storedValue, $this->owner->data_key_id);
    }

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function encryptedColumns(): array
    {
        return [
            'user' => [User::class, ['name']],
            'goal' => [Goal::class, ['title', 'description', 'unit']],
            'category' => [Category::class, ['name', 'description']],
            'goal entry' => [GoalEntry::class, ['note']],
            'milestone' => [Milestone::class, ['title', 'description']],
        ];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<string>  $columns
     */
    #[DataProvider('encryptedColumns')]
    public function test_every_listed_column_is_stored_encrypted_and_read_back_in_clear(string $modelClass, array $columns)
    {
        $model = match ($modelClass) {
            User::class => $this->owner,
            Goal::class => $this->goal,
            Category::class => Category::factory()->create(['user_id' => $this->owner->id, 'name' => 'Santé', 'description' => 'Sport']),
            GoalEntry::class => GoalEntry::factory()->create(['goal_id' => $this->goal->id, 'note' => 'Première sortie']),
            Milestone::class => Milestone::factory()->create(['goal_id' => $this->goal->id, 'title' => 'Half marathon', 'description' => 'First 21 km']),
        };

        $fresh = $modelClass::find($model->getKey());

        foreach ($columns as $column) {
            $stored = $this->storedValue($model, $column);

            $this->assertStringStartsWith(UserDataCipher::PREFIX, $stored, "{$column} is not encrypted");
            $this->assertSame($model->{$column}, $fresh->{$column});
            $this->assertSame($model->{$column}, $this->decryptWithOwnerKey($stored));
        }
    }

    public function test_null_stays_null()
    {
        $goal = Goal::factory()->create(['user_id' => $this->owner->id, 'category_id' => null, 'description' => null, 'unit' => null]);

        $this->assertNull($this->storedValue($goal, 'description'));
        $this->assertNull(Goal::find($goal->id)->description);
    }

    public function test_the_saved_instance_keeps_plaintext_in_memory()
    {
        $goal = $this->owner->goals()->create(['title' => 'Learn the cello', 'type' => 'simple', 'status' => 'in_progress', 'priority' => 'low']);

        $this->assertSame('Learn the cello', $goal->title);
        $this->assertSame('Learn the cello', $goal->toArray()['title']);
        $this->assertFalse($goal->isDirty());
    }

    public function test_a_loaded_model_is_clean_and_saving_it_unchanged_writes_nothing()
    {
        $goal = Goal::find($this->goal->id);

        $this->assertFalse($goal->isDirty());

        DB::enableQueryLog();
        $goal->save();

        $this->assertEmpty(collect(DB::getQueryLog())->filter(fn (array $query): bool => Str::startsWith($query['query'], 'update')));
    }

    public function test_changing_another_column_leaves_the_encrypted_value_untouched()
    {
        $before = $this->storedValue($this->goal, 'title');

        Goal::find($this->goal->id)->update(['status' => 'paused']);

        $this->assertSame($before, $this->storedValue($this->goal, 'title'));
    }

    public function test_rows_are_encrypted_with_their_owners_key_whoever_saves_them()
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        Goal::find($this->goal->id)->update(['title' => 'Edited by someone else']);
        $entry = GoalEntry::factory()->create(['goal_id' => $this->goal->id, 'note' => 'Logged by someone else']);

        $this->assertSame('Edited by someone else', $this->decryptWithOwnerKey($this->storedValue($this->goal, 'title')));
        $this->assertSame('Logged by someone else', $this->decryptWithOwnerKey($this->storedValue($entry, 'note')));

        $this->expectException(DecryptException::class);
        app(UserDataCipher::class)->decrypt($this->storedValue($this->goal, 'title'), $admin->data_key_id);
    }

    public function test_a_plaintext_value_in_an_encrypted_column_fails_loudly()
    {
        DB::table('goals')->where('id', $this->goal->id)->update(['title' => 'Written around the model']);

        $this->expectException(UserDataEncryptionException::class);

        Goal::find($this->goal->id);
    }

    public function test_plucking_an_encrypted_column_in_sql_fails_instead_of_returning_ciphertext()
    {
        $this->expectException(UserDataEncryptionException::class);

        Goal::query()->pluck('title');
    }

    public function test_plucking_from_loaded_models_returns_plaintext()
    {
        $this->assertSame(['Run a marathon'], Goal::all()->pluck('title')->all());
    }

    public function test_the_data_key_id_never_reaches_serialized_output()
    {
        $this->assertArrayNotHasKey('data_key_id', $this->owner->toArray());
    }
}

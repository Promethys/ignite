<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\IgniteServer;
use App\Mcp\Tools\AddMilestoneTool;
use App\Mcp\Tools\CheckInTool;
use App\Mcp\Tools\CompleteGoalTool;
use App\Mcp\Tools\CompleteMilestoneTool;
use App\Mcp\Tools\DeleteCategoryTool;
use App\Mcp\Tools\DeleteEntryTool;
use App\Mcp\Tools\DeleteGoalTool;
use App\Mcp\Tools\GetGoalTool;
use App\Mcp\Tools\ListEntriesTool;
use App\Mcp\Tools\LogProgressBatchTool;
use App\Mcp\Tools\LogProgressTool;
use App\Mcp\Tools\SetGoalStatusTool;
use App\Mcp\Tools\UncompleteGoalTool;
use App\Mcp\Tools\UpdateCategoryTool;
use App\Mcp\Tools\UpdateEntryTool;
use App\Mcp\Tools\UpdateGoalTool;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OwnedIdArgumentsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string, string, string, array<string, mixed>, string}>
     */
    public static function toolProvider(): array
    {
        $goalError = 'The selected goal id is invalid.';

        return [
            'get_goal' => [GetGoalTool::class, 'goal_id', 'goal', [], $goalError],
            'list_entries' => [ListEntriesTool::class, 'goal_id', 'goal', [], $goalError],
            'update_goal' => [UpdateGoalTool::class, 'goal_id', 'goal', ['title' => 'Renamed'], $goalError],
            'complete_goal' => [CompleteGoalTool::class, 'goal_id', 'goal', [], $goalError],
            'uncomplete_goal' => [UncompleteGoalTool::class, 'goal_id', 'goal', ['status' => 'paused'], $goalError],
            'set_goal_status' => [SetGoalStatusTool::class, 'goal_id', 'goal', ['status' => 'paused'], $goalError],
            'log_progress' => [LogProgressTool::class, 'goal_id', 'goal', ['increment' => 1], $goalError],
            'log_progress_batch' => [LogProgressBatchTool::class, 'goal_id', 'goal', ['entries' => [['increment' => 1]]], $goalError],
            'check_in' => [CheckInTool::class, 'goal_id', 'goal', [], $goalError],
            'add_milestone' => [AddMilestoneTool::class, 'goal_id', 'goal', ['title' => 'Step'], $goalError],
            'delete_goal' => [DeleteGoalTool::class, 'goal_id', 'goal', [], $goalError],
            'update_entry' => [UpdateEntryTool::class, 'entry_id', 'entry', ['increment' => 1], 'The selected entry id is invalid.'],
            'delete_entry' => [DeleteEntryTool::class, 'entry_id', 'entry', [], 'The selected entry id is invalid.'],
            'complete_milestone' => [CompleteMilestoneTool::class, 'milestone_id', 'milestone', [], 'The selected milestone id is invalid.'],
            'update_category' => [UpdateCategoryTool::class, 'category_id', 'category', ['name' => 'Renamed'], 'The selected category is invalid.'],
            'delete_category' => [DeleteCategoryTool::class, 'category_id', 'category', [], 'The selected category is invalid.'],
        ];
    }

    /**
     * @param  class-string  $tool
     * @param  array<string, mixed>  $arguments
     */
    #[DataProvider('toolProvider')]
    public function test_a_foreign_id_and_a_missing_id_get_the_same_error(string $tool, string $idArgument, string $record, array $arguments, string $error): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $foreignId = $this->recordOwnedBy($owner, $record);

        Sanctum::actingAs($intruder, ['read', 'write', 'delete']);

        IgniteServer::tool($tool, [$idArgument => $foreignId, ...$arguments])->assertHasErrors([$error]);
        IgniteServer::tool($tool, [$idArgument => 999999, ...$arguments])->assertHasErrors([$error]);
    }

    private function recordOwnedBy(User $owner, string $record): int
    {
        $goal = Goal::factory()->create(['user_id' => $owner->id]);

        return match ($record) {
            'goal' => $goal->id,
            'entry' => GoalEntry::factory()->create(['goal_id' => $goal->id])->id,
            'milestone' => Milestone::factory()->create(['goal_id' => $goal->id])->id,
            'category' => Category::factory()->create(['user_id' => $owner->id])->id,
        };
    }
}

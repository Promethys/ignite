<?php

namespace Tests\Feature\Ai\Tools;

use App\Ai\Tools\ConfirmedDeletion;
use App\Mcp\Tools\DeleteCategoryTool;
use App\Mcp\Tools\DeleteEntryTool;
use App\Mcp\Tools\DeleteGoalTool;
use App\Models\Category;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\Milestone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ConfirmedDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Goal $goal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->goal = Goal::factory()->create(['user_id' => $this->user->id, 'category_id' => null, 'title' => 'Write a book']);
        $this->actingAs($this->user);
    }

    private function deleteGoal(): ConfirmedDeletion
    {
        return new ConfirmedDeletion(app(DeleteGoalTool::class));
    }

    public function test_it_keeps_the_name_of_the_tool_it_wraps()
    {
        $this->assertSame('delete_goal', $this->deleteGoal()->name());
    }

    public function test_the_model_is_never_offered_a_confirmation_token_parameter()
    {
        $schema = $this->deleteGoal()->schema(new JsonSchemaTypeFactory);

        $this->assertArrayHasKey('goal_id', $schema);
        $this->assertArrayNotHasKey('confirmation_token', $schema);
        $this->assertStringNotContainsString('confirmation_token', $this->deleteGoal()->description());
    }

    public function test_it_asks_for_approval_with_the_preview_of_what_would_be_deleted()
    {
        Milestone::factory()->count(2)->create(['goal_id' => $this->goal->id]);
        GoalEntry::factory()->create(['goal_id' => $this->goal->id]);

        $approval = $this->deleteGoal()->shouldRequestApproval(new Request(['goal_id' => $this->goal->id]));

        $this->assertNotNull($approval);
        $this->assertStringContainsString("the goal 'Write a book'", $approval->reason);
        $this->assertStringContainsString('its 2 milestones and 1 progress entry', $approval->reason);
        $this->assertModelExists($this->goal);
    }

    public function test_asking_for_approval_reveals_no_token()
    {
        $approval = $this->deleteGoal()->shouldRequestApproval(new Request(['goal_id' => $this->goal->id]));

        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9]{40}/', $approval->reason);
    }

    public function test_once_approved_it_deletes_without_the_model_supplying_a_token()
    {
        $result = $this->deleteGoal()->handle(new Request(['goal_id' => $this->goal->id]));

        $this->assertModelMissing($this->goal);
        $this->assertStringContainsString('Deleted the goal Write a book.', $result);
        $this->assertDoesNotMatchRegularExpression('/[A-Za-z0-9]{40}/', $result);
    }

    public function test_a_token_supplied_by_the_model_is_ignored()
    {
        $result = $this->deleteGoal()->handle(new Request(['goal_id' => $this->goal->id, 'confirmation_token' => 'made-up-by-the-model']));

        $this->assertModelMissing($this->goal);
        $this->assertStringContainsString('Deleted the goal', $result);
    }

    public function test_an_invalid_target_needs_no_approval_and_fails_validation_when_run()
    {
        $othersGoal = Goal::factory()->create();
        $tool = $this->deleteGoal();

        $this->assertNull($tool->shouldRequestApproval(new Request(['goal_id' => $othersGoal->id])));

        try {
            $tool->handle(new Request(['goal_id' => $othersGoal->id]));
            $this->fail('Another user\'s goal must not be deletable.');
        } catch (ValidationException) {
            $this->assertModelExists($othersGoal);
        }
    }

    public function test_it_gates_category_and_entry_deletions_the_same_way()
    {
        $category = Category::factory()->create(['user_id' => $this->user->id, 'name' => 'Reading']);
        $entry = GoalEntry::factory()->create(['goal_id' => $this->goal->id]);

        $deleteCategory = new ConfirmedDeletion(app(DeleteCategoryTool::class));
        $deleteEntry = new ConfirmedDeletion(app(DeleteEntryTool::class));

        $this->assertStringContainsString('Reading', $deleteCategory->shouldRequestApproval(new Request(['category_id' => $category->id]))->reason);
        $this->assertNotNull($deleteEntry->shouldRequestApproval(new Request(['entry_id' => $entry->id])));

        $deleteCategory->handle(new Request(['category_id' => $category->id]));
        $deleteEntry->handle(new Request(['entry_id' => $entry->id]));

        $this->assertModelMissing($category);
        $this->assertModelMissing($entry);
    }
}

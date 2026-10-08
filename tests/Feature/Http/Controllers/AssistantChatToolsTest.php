<?php

namespace Tests\Feature\Http\Controllers;

use App\Ai\Agents\IgniteAssistant;
use App\Models\AssistantKey;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssistantChatToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Goal $goal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $this->user->id]);

        $this->goal = Goal::factory()->quantifiable()->create([
            'user_id' => $this->user->id,
            'category_id' => null,
            'title' => 'Read twelve books',
        ]);
    }

    private function send(array $message, ?string $conversationId = null): TestResponse
    {
        return $this->actingAs($this->user)->postJson(route('assistant.chat'), [
            'messages' => [$message],
            'conversation_id' => $conversationId,
        ]);
    }

    private function ask(string $text): TestResponse
    {
        return $this->send(['id' => 'user-1', 'role' => 'user', 'parts' => [['type' => 'text', 'text' => $text]]]);
    }

    private function decide(string $conversationId, bool $approved): TestResponse
    {
        return $this->send([
            'id' => 'assistant-1',
            'role' => 'assistant',
            'parts' => [[
                'type' => 'tool-delete_goal',
                'toolCallId' => 'call-1',
                'state' => 'approval-responded',
                'input' => ['goal_id' => $this->goal->id],
                'approval' => ['id' => 'call-1', 'approved' => $approved],
            ]],
        ], $conversationId);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function parts(TestResponse $response): Collection
    {
        return Str::of($response->streamedContent())
            ->explode("\n")
            ->filter(fn (string $line) => str_starts_with($line, 'data: {'))
            ->map(fn (string $line) => json_decode(substr($line, 6), true))
            ->values();
    }

    // =========================================================================
    // WRITES
    // =========================================================================

    public function test_a_tool_call_from_the_model_changes_the_users_data()
    {
        IgniteAssistant::fake([
            new ToolCall('call-1', 'log_progress', ['goal_id' => $this->goal->id, 'increment' => 3]),
            'Logged 3 books.',
        ]);

        $parts = $this->parts($this->ask('I read three books'));

        $this->assertDatabaseCount('goal_entries', 1);
        $this->assertDatabaseHas('goal_entries', ['goal_id' => $this->goal->id]);
        $this->assertNotNull($parts->firstWhere('type', 'tool-output-available'));
        $this->assertNull($parts->firstWhere('type', 'tool-approval-request'));
    }

    public function test_arguments_the_tool_refuses_come_back_to_the_model_as_text()
    {
        $othersGoal = Goal::factory()->quantifiable()->create();

        IgniteAssistant::fake([
            new ToolCall('call-1', 'log_progress', ['goal_id' => $othersGoal->id, 'increment' => 3]),
            'I could not find that goal.',
        ]);

        $response = $this->ask('Log three on that goal')->assertOk();
        $parts = $this->parts($response);

        $this->assertDatabaseCount('goal_entries', 0);
        $this->assertNull($parts->firstWhere('type', 'error'));
        $this->assertSame('I could not find that goal.', $parts->where('type', 'text-delta')->pluck('delta')->implode(''));
    }

    // =========================================================================
    // DELETIONS
    // =========================================================================

    public function test_a_deletion_pauses_for_approval_with_its_preview_and_no_token()
    {
        IgniteAssistant::fake([new ToolCall('call-1', 'delete_goal', ['goal_id' => $this->goal->id])]);

        $response = $this->ask('Delete my reading goal');
        $content = $response->streamedContent();
        $request = $this->parts($response)->firstWhere('type', 'tool-approval-request');

        $this->assertModelExists($this->goal);
        $this->assertSame('call-1', $request['toolCallId']);
        $this->assertStringContainsString('Read twelve books', $request['reason']);
        $this->assertStringNotContainsString('confirmation_token', $content);
    }

    #[DataProvider('decisions')]
    public function test_the_users_decision_resumes_the_paused_conversation(bool $approved)
    {
        IgniteAssistant::fake([
            new ToolCall('call-1', 'delete_goal', ['goal_id' => $this->goal->id]),
            'Done.',
        ]);

        $paused = $this->ask('Delete my reading goal');
        $paused->streamedContent();
        $conversationId = $paused->headers->get('X-Conversation-Id');

        $resumed = $this->decide($conversationId, $approved)->assertOk();
        $start = $this->parts($resumed)->firstWhere('type', 'start');

        $this->assertSame('assistant-1', $start['messageId']);

        $resumed->assertHeader('X-Conversation-Id', $conversationId);
        IgniteAssistant::assertPrompted(
            fn (AgentPrompt $prompt) => $prompt->approvalDecisions?->get('call-1')?->isApproved() === $approved
        );
        $this->assertSame(1, DB::table('agent_conversation_messages')->where('role', 'user')->count());
    }

    public static function decisions(): array
    {
        return ['approved' => [true], 'rejected' => [false]];
    }

    public function test_a_decision_cannot_be_replayed_in_another_users_conversation()
    {
        IgniteAssistant::fake([new ToolCall('call-1', 'delete_goal', ['goal_id' => $this->goal->id])]);

        $paused = $this->ask('Delete my reading goal');
        $paused->streamedContent();

        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);

        $this->actingAs($other)->postJson(route('assistant.chat'), [
            'conversation_id' => $paused->headers->get('X-Conversation-Id'),
            'messages' => [[
                'id' => 'assistant-1',
                'role' => 'assistant',
                'parts' => [[
                    'type' => 'tool-delete_goal',
                    'toolCallId' => 'call-1',
                    'state' => 'approval-responded',
                    'approval' => ['id' => 'call-1', 'approved' => true],
                ]],
            ]],
        ])->assertNotFound();

        $this->assertModelExists($this->goal);
    }
}

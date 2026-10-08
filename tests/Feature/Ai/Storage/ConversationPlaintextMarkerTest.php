<?php

namespace Tests\Feature\Ai\Storage;

use App\Ai\Agents\IgniteAssistant;
use App\Ai\Storage\EncryptedConversationStore;
use App\Models\AssistantKey;
use App\Models\Goal;
use App\Models\GoalEntry;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Responses\Data\ToolCall;
use RuntimeException;
use Tests\TestCase;

class ConversationPlaintextMarkerTest extends TestCase
{
    use RefreshDatabase;

    private const MARKER = 'zebra-quartz-7391';

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
            'title' => 'Goal '.self::MARKER,
            'description' => 'Described with '.self::MARKER,
        ]);
        GoalEntry::factory()->create(['goal_id' => $this->goal->id, 'note' => 'Note '.self::MARKER]);
    }

    private function send(array $message, ?string $conversationId = null): TestResponse
    {
        $response = $this->actingAs($this->user)->postJson(route('assistant.chat'), [
            'messages' => [$message],
            'conversation_id' => $conversationId,
        ]);
        $response->streamedContent();

        return $response;
    }

    private function ask(string $text, ?string $conversationId = null): string
    {
        return $this->send(
            ['id' => 'user-1', 'role' => 'user', 'parts' => [['type' => 'text', 'text' => $text]]],
            $conversationId,
        )->headers->get('X-Conversation-Id');
    }

    private function decide(string $conversationId, bool $approved): void
    {
        $this->send([
            'id' => 'assistant-1',
            'role' => 'assistant',
            'parts' => [[
                'type' => 'tool-delete_goal',
                'toolCallId' => 'delete-1',
                'state' => 'approval-responded',
                'approval' => ['id' => 'delete-1', 'approved' => $approved],
            ]],
        ], $conversationId)->assertOk();
    }

    private function assertNoStoredRowHoldsTheMarker(): void
    {
        foreach (['agent_conversations', 'agent_conversation_messages'] as $table) {
            $rows = DB::table($table)->get();

            $this->assertNotEmpty($rows, "[{$table}] holds no row, so the scan proves nothing.");

            foreach ($rows as $row) {
                foreach ((array) $row as $column => $value) {
                    $this->assertStringNotContainsString(
                        self::MARKER,
                        (string) $value,
                        "[{$table}.{$column}] holds user text in clear.",
                    );
                }
            }
        }
    }

    public function test_no_column_of_a_full_conversation_holds_user_text_in_clear()
    {
        IgniteAssistant::fake([
            new ToolCall('read-1', 'get_goal', ['goal_id' => $this->goal->id]),
            new ToolCall('list-1', 'list_entries', ['goal_id' => $this->goal->id]),
            'Your goal '.self::MARKER.' has one entry.',
            new ToolCall('delete-1', 'delete_goal', ['goal_id' => $this->goal->id]),
            'I kept the goal '.self::MARKER.'.',
        ]);

        $conversationId = $this->ask('Tell me about '.self::MARKER);
        $this->ask('Now delete '.self::MARKER, $conversationId);
        $this->assertNoStoredRowHoldsTheMarker();

        $this->decide($conversationId, approved: false);
        app(EncryptedConversationStore::class)->renameConversation($conversationId, 'About '.self::MARKER);

        $this->assertNoStoredRowHoldsTheMarker();
        $this->assertSame(4, DB::table('agent_conversation_messages')->count());
    }

    public function test_the_scan_would_see_the_marker_if_it_were_readable()
    {
        IgniteAssistant::fake([
            new ToolCall('read-1', 'get_goal', ['goal_id' => $this->goal->id]),
            'Your goal '.self::MARKER.' is on track.',
        ]);

        $conversationId = $this->ask('Tell me about '.self::MARKER);

        $store = app(EncryptedConversationStore::class);
        $readable = json_encode([
            $store->latestConversationsOf($this->user, 1),
            $store->uiMessagesOf($conversationId, 10),
        ]);

        $this->assertGreaterThanOrEqual(4, substr_count($readable, self::MARKER));
    }

    public function test_a_turn_that_fails_midway_stores_nothing_in_clear()
    {
        IgniteAssistant::fake([
            new ToolCall('read-1', 'get_goal', ['goal_id' => $this->goal->id]),
            fn () => throw new RuntimeException('The provider echoed '.self::MARKER),
        ]);

        $this->ask('Tell me about '.self::MARKER);

        $this->assertNoStoredRowHoldsTheMarker();
        $this->assertDatabaseHas('agent_conversation_messages', ['role' => 'assistant', 'status' => 'failed']);
    }

    public function test_the_admin_panel_has_no_page_over_conversations()
    {
        $models = collect(Filament::getPanel('admin')->getResources())
            ->map(fn (string $resource): string => $resource::getModel());

        $this->assertNotEmpty($models);
        $this->assertNotContains(Conversation::class, $models);
        $this->assertNotContains(ConversationMessage::class, $models);
    }
}

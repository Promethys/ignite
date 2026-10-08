<?php

namespace Tests\Feature\Ai\Storage;

use App\Ai\Agents\IgniteAssistant;
use App\Ai\Storage\EncryptedConversationStore;
use App\Models\AssistantKey;
use App\Models\Goal;
use App\Models\User;
use App\Services\Encryption\UserDataCipher;
use App\Services\Encryption\UserDataKeyring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Responses\Data\ToolCall;
use LogicException;
use Tests\TestCase;

class EncryptedConversationStoreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Goal $goal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $this->user->id]);

        $this->goal = Goal::factory()->create([
            'user_id' => $this->user->id,
            'category_id' => null,
            'title' => 'Read twelve books',
        ]);
    }

    private function store(): EncryptedConversationStore
    {
        return app(EncryptedConversationStore::class);
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

    private function decrypted(string $stored, ?User $owner = null): string
    {
        return app(UserDataCipher::class)->decrypt(
            $stored,
            app(UserDataKeyring::class)->keyIdForUser(($owner ?? $this->user)->id),
        );
    }

    // =========================================================================
    // BINDING
    // =========================================================================

    public function test_the_ai_sdk_stores_conversations_through_it()
    {
        $this->assertInstanceOf(EncryptedConversationStore::class, app(ConversationStore::class));
        $this->assertSame($this->store(), app(ConversationStore::class));
    }

    // =========================================================================
    // WRITES
    // =========================================================================

    public function test_a_title_and_its_messages_are_stored_encrypted_with_the_users_key()
    {
        IgniteAssistant::fake(['You are halfway through your books.']);

        $conversationId = $this->ask('How are my books going?');

        $conversation = DB::table('agent_conversations')->where('id', $conversationId)->sole();
        $messages = DB::table('agent_conversation_messages')->orderBy('id')->get();

        $this->assertStringStartsWith(UserDataCipher::PREFIX, $conversation->title);
        $this->assertSame('How are my books going?', $this->decrypted($conversation->title));

        foreach ($messages as $message) {
            foreach (['content', 'attachments', 'steps', 'meta'] as $column) {
                $this->assertStringStartsWith(UserDataCipher::PREFIX, $message->{$column}, "[{$column}] is stored in clear.");
            }
        }

        $this->assertSame('How are my books going?', $this->decrypted($messages[0]->content));
        $this->assertSame('You are halfway through your books.', $this->decrypted($messages[1]->content));
    }

    public function test_what_a_tool_returned_is_not_readable_in_the_stored_steps()
    {
        IgniteAssistant::fake([
            new ToolCall('call-1', 'get_goal', ['goal_id' => $this->goal->id]),
            'It is going well.',
        ]);

        $this->ask('How is that goal going?');

        $steps = DB::table('agent_conversation_messages')->where('role', 'assistant')->sole()->steps;

        $this->assertStringNotContainsString('Read twelve books', $steps);
        $this->assertStringContainsString('Read twelve books', $this->decrypted($steps));
    }

    public function test_another_users_key_cannot_read_it()
    {
        IgniteAssistant::fake(['Fine.']);
        $conversationId = $this->ask('How are my books going?');
        $title = DB::table('agent_conversations')->where('id', $conversationId)->value('title');

        $this->expectException(\Throwable::class);

        $this->decrypted($title, User::factory()->create());
    }

    public function test_a_paused_deletion_and_its_resume_stay_encrypted_in_place()
    {
        IgniteAssistant::fake([
            new ToolCall('call-1', 'delete_goal', ['goal_id' => $this->goal->id]),
            'I kept it.',
        ]);

        $conversationId = $this->ask('Delete my reading goal');
        $paused = DB::table('agent_conversation_messages')->where('role', 'assistant')->sole();

        $this->assertStringNotContainsString('Read twelve books', $paused->steps);
        $this->assertStringContainsString('Read twelve books', $this->decrypted($paused->steps));
        $this->assertCount(1, $this->store()->pendingApprovalsFor($conversationId));

        $this->send([
            'id' => 'assistant-1',
            'role' => 'assistant',
            'parts' => [[
                'type' => 'tool-delete_goal',
                'toolCallId' => 'call-1',
                'state' => 'approval-responded',
                'approval' => ['id' => 'call-1', 'approved' => false],
            ]],
        ], $conversationId)->assertOk();

        $resumed = DB::table('agent_conversation_messages')->where('role', 'assistant')->sole();

        $this->assertSame($paused->id, $resumed->id);
        $this->assertSame('completed', $resumed->status);
        $this->assertStringStartsWith(UserDataCipher::PREFIX, $resumed->steps);
        $this->assertSame('I kept it.', $this->decrypted($resumed->content));
    }

    public function test_renaming_stores_the_new_title_encrypted()
    {
        IgniteAssistant::fake(['Fine.']);
        $conversationId = $this->ask('How are my books going?');

        $this->store()->renameConversation($conversationId, 'Reading review');

        $stored = DB::table('agent_conversations')->where('id', $conversationId)->value('title');

        $this->assertStringStartsWith(UserDataCipher::PREFIX, $stored);
        $this->assertSame('Reading review', $this->decrypted($stored));
    }

    public function test_it_refuses_to_store_a_conversation_nobody_owns()
    {
        $this->expectException(LogicException::class);

        $this->store()->storeConversation(null, null, 'Nobody owns this');
    }

    public function test_it_refuses_a_participant_that_is_not_a_user()
    {
        $this->expectException(LogicException::class);

        $this->store()->storeConversation($this->goal->getMorphClass(), $this->goal->id, 'Owned by a goal');
    }

    // =========================================================================
    // READS
    // =========================================================================

    public function test_the_model_gets_the_earlier_messages_back_in_clear()
    {
        IgniteAssistant::fake(['You are halfway through your books.', 'Six more.']);
        $conversationId = $this->ask('How are my books going?');
        $this->ask('How many are left?', $conversationId);

        $history = $this->store()->getLatestConversationMessages($conversationId, 10);

        $this->assertSame(
            ['How are my books going?', 'You are halfway through your books.', 'How many are left?', 'Six more.'],
            $history->pluck('content')->all(),
        );
    }

    public function test_the_panel_gets_titles_and_messages_back_in_clear()
    {
        IgniteAssistant::fake(['You are halfway through your books.']);
        $conversationId = $this->ask('How are my books going?');

        $listed = $this->store()->latestConversationsOf($this->user, 10)->sole();
        $messages = $this->store()->uiMessagesOf($conversationId, 10);

        $this->assertSame('How are my books going?', $listed->title);
        $this->assertSame('How are my books going?', $messages[0]['parts'][0]['text']);
        $this->assertSame('You are halfway through your books.', $messages[1]['parts'][0]['text']);
    }

    public function test_each_panel_message_carries_when_it_was_stored()
    {
        Carbon::setTestNow('2026-10-22 14:12:00');
        IgniteAssistant::fake(['You are halfway through your books.']);
        $conversationId = $this->ask('How are my books going?');

        $messages = $this->store()->uiMessagesOf($conversationId, 10);

        $this->assertSame('2026-10-22T14:12:00+00:00', $messages[0]['metadata']['createdAt']);
        $this->assertSame('2026-10-22T14:12:00+00:00', $messages[1]['metadata']['createdAt']);
    }

    public function test_a_conversation_is_only_found_for_its_owner()
    {
        IgniteAssistant::fake(['Fine.']);
        $conversationId = $this->ask('How are my books going?');

        $this->assertNotNull($this->store()->conversationOf($this->user, $conversationId));
        $this->assertNull($this->store()->conversationOf(User::factory()->create(), $conversationId));
    }

    public function test_a_value_stored_in_clear_is_an_error_not_a_pass_through()
    {
        IgniteAssistant::fake(['Fine.']);
        $conversationId = $this->ask('How are my books going?');
        DB::table('agent_conversations')->where('id', $conversationId)->update(['title' => 'Written in clear']);

        $this->expectException(\Throwable::class);

        $this->store()->latestConversationsOf($this->user, 10);
    }

    // =========================================================================
    // DELETION
    // =========================================================================

    public function test_deleting_a_conversation_removes_its_messages()
    {
        IgniteAssistant::fake(['Fine.', 'Fine.']);
        $deleted = $this->ask('How are my books going?');
        $kept = $this->ask('What is due this week?');

        $this->store()->deleteConversation($deleted);

        $this->assertDatabaseMissing('agent_conversations', ['id' => $deleted]);
        $this->assertDatabaseMissing('agent_conversation_messages', ['conversation_id' => $deleted]);
        $this->assertDatabaseHas('agent_conversations', ['id' => $kept]);
    }

    public function test_deleting_a_users_conversations_leaves_everyone_elses()
    {
        IgniteAssistant::fake(['Fine.', 'Fine.', 'Fine.']);
        $this->ask('How are my books going?');
        $this->ask('What is due this week?');

        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);
        $this->user = $other;
        $kept = $this->ask('A question of my own');

        $this->store()->deleteConversationsOf(User::query()->oldest('id')->firstOrFail());

        $this->assertSame([$kept], DB::table('agent_conversations')->pluck('id')->all());
        $this->assertSame([$kept], DB::table('agent_conversation_messages')->distinct()->pluck('conversation_id')->all());
    }

    public function test_user_messages_written_directly_through_the_store_are_encrypted_too()
    {
        $conversationId = $this->store()->storeConversation($this->user->getMorphClass(), $this->user->id, 'Direct');

        $this->store()->storeUserMessage(
            $conversationId,
            $this->user->getMorphClass(),
            $this->user->id,
            IgniteAssistant::class,
            new UserMessage('Written through the store'),
        );

        $this->assertDatabaseMissing('agent_conversation_messages', ['content' => 'Written through the store']);
        $this->assertSame(
            'Written through the store',
            $this->store()->getLatestConversationMessages($conversationId, 1)->sole()->content,
        );
    }
}

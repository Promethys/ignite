<?php

namespace Tests\Feature\Http\Controllers;

use App\Ai\Agents\IgniteAssistant;
use App\Ai\Storage\EncryptedConversationStore;
use App\Http\Requests\RenameAssistantConversationRequest;
use App\Models\AssistantKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AssistantConversationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $this->user->id]);

        IgniteAssistant::fake(['You have three active goals.']);
    }

    private function startConversation(string $text, ?User $as = null): string
    {
        $response = $this->actingAs($as ?? $this->user)->postJson(route('assistant.chat'), [
            'messages' => [['id' => 'message-1', 'role' => 'user', 'parts' => [['type' => 'text', 'text' => $text]]]],
        ]);
        $response->streamedContent();

        return $response->headers->get('X-Conversation-Id');
    }

    private function storedTitle(string $conversationId, ?User $owner = null): string
    {
        return app(EncryptedConversationStore::class)->conversationOf($owner ?? $this->user, $conversationId)->title;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function test_a_guest_cannot_list_conversations()
    {
        $this->getJson(route('assistant.conversations.index'))->assertUnauthorized();
    }

    public function test_it_lists_the_users_conversations_newest_first()
    {
        Carbon::setTestNow('2026-10-01 10:00:00');
        $older = $this->startConversation('How am I doing?');

        Carbon::setTestNow('2026-10-02 10:00:00');
        $newer = $this->startConversation('What is due this week?');

        $this->actingAs($this->user)->getJson(route('assistant.conversations.index'))
            ->assertOk()
            ->assertJsonCount(2, 'conversations')
            ->assertJsonPath('conversations.0.id', $newer)
            ->assertJsonPath('conversations.0.title', 'What is due this week?')
            ->assertJsonPath('conversations.1.id', $older);
    }

    public function test_it_never_lists_another_users_conversations()
    {
        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);
        $this->startConversation('A private question', $other);

        $this->actingAs($this->user)->getJson(route('assistant.conversations.index'))
            ->assertOk()
            ->assertJsonCount(0, 'conversations');
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function test_it_returns_a_conversation_as_ui_messages()
    {
        $conversationId = $this->startConversation('How am I doing?');

        $this->actingAs($this->user)->getJson(route('assistant.conversations.show', $conversationId))
            ->assertOk()
            ->assertJsonPath('id', $conversationId)
            ->assertJsonPath('title', 'How am I doing?')
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.0.parts.0.text', 'How am I doing?')
            ->assertJsonPath('messages.1.role', 'assistant')
            ->assertJsonPath('messages.1.parts.0.text', 'You have three active goals.');
    }

    public function test_it_is_not_found_for_another_users_conversation()
    {
        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);
        $conversationId = $this->startConversation('A private question', $other);

        $this->actingAs($this->user)->getJson(route('assistant.conversations.show', $conversationId))
            ->assertNotFound();
    }

    public function test_it_is_not_found_for_a_conversation_that_does_not_exist()
    {
        $this->actingAs($this->user)->getJson(route('assistant.conversations.show', 'missing'))
            ->assertNotFound();
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function test_it_renames_a_conversation_without_moving_it_in_the_list()
    {
        Carbon::setTestNow('2026-10-01 10:00:00');
        $conversationId = $this->startConversation('How am I doing?');

        Carbon::setTestNow('2026-10-03 10:00:00');
        $this->actingAs($this->user)
            ->patchJson(route('assistant.conversations.update', $conversationId), ['title' => 'Weekly review'])
            ->assertOk()
            ->assertJsonPath('title', 'Weekly review');

        $this->assertSame('Weekly review', $this->storedTitle($conversationId));
        $this->assertDatabaseHas('agent_conversations', ['id' => $conversationId, 'updated_at' => '2026-10-01 10:00:00']);
        $this->assertDatabaseMissing('agent_conversations', ['title' => 'Weekly review']);
    }

    public function test_a_title_is_required_and_limited()
    {
        $conversationId = $this->startConversation('How am I doing?');
        $route = route('assistant.conversations.update', $conversationId);

        $this->actingAs($this->user)->patchJson($route, ['title' => ''])->assertJsonValidationErrors('title');
        $this->actingAs($this->user)
            ->patchJson($route, ['title' => str_repeat('a', RenameAssistantConversationRequest::MAX_TITLE_LENGTH + 1)])
            ->assertJsonValidationErrors('title');

        $this->assertSame('How am I doing?', $this->storedTitle($conversationId));
    }

    public function test_it_cannot_rename_another_users_conversation()
    {
        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);
        $conversationId = $this->startConversation('A private question', $other);

        $this->actingAs($this->user)
            ->patchJson(route('assistant.conversations.update', $conversationId), ['title' => 'Mine now'])
            ->assertNotFound();

        $this->assertSame('A private question', $this->storedTitle($conversationId, $other));
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function test_it_deletes_a_conversation_with_its_messages_and_nothing_else()
    {
        $deleted = $this->startConversation('How am I doing?');
        $kept = $this->startConversation('What is due this week?');

        $this->actingAs($this->user)
            ->deleteJson(route('assistant.conversations.destroy', $deleted))
            ->assertNoContent();

        $this->assertDatabaseMissing('agent_conversations', ['id' => $deleted]);
        $this->assertDatabaseMissing('agent_conversation_messages', ['conversation_id' => $deleted]);
        $this->assertDatabaseHas('agent_conversations', ['id' => $kept]);
        $this->assertDatabaseCount('agent_conversation_messages', 2);
    }

    public function test_it_cannot_delete_another_users_conversation()
    {
        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);
        $conversationId = $this->startConversation('A private question', $other);

        $this->actingAs($this->user)
            ->deleteJson(route('assistant.conversations.destroy', $conversationId))
            ->assertNotFound();

        $this->assertDatabaseHas('agent_conversations', ['id' => $conversationId]);
        $this->assertDatabaseCount('agent_conversation_messages', 2);
    }

    public function test_a_guest_cannot_rename_or_delete_a_conversation()
    {
        $conversationId = $this->startConversation('How am I doing?');
        auth()->logout();

        $this->patchJson(route('assistant.conversations.update', $conversationId), ['title' => 'New'])->assertUnauthorized();
        $this->deleteJson(route('assistant.conversations.destroy', $conversationId))->assertUnauthorized();
    }
}

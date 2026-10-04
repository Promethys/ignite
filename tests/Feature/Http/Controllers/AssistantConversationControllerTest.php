<?php

namespace Tests\Feature\Http\Controllers;

use App\Ai\Agents\IgniteAssistant;
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
}

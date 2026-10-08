<?php

namespace Tests\Feature\Http\Controllers;

use App\Ai\Agents\IgniteAssistant;
use App\Http\Requests\AssistantChatRequest;
use App\Models\AssistantKey;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

class AssistantChatControllerTest extends TestCase
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

    private function userMessage(string $text, array $extraParts = []): array
    {
        return ['id' => 'message-1', 'role' => 'user', 'parts' => [['type' => 'text', 'text' => $text], ...$extraParts]];
    }

    private function chat(array $payload = [], ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->user)->postJson(route('assistant.chat'), [
            'messages' => [$this->userMessage('How am I doing?')],
            ...$payload,
        ]);
    }

    private function streamedText(TestResponse $response): string
    {
        return Str::of($response->streamedContent())
            ->explode("\n")
            ->filter(fn (string $line) => str_starts_with($line, 'data: {'))
            ->map(fn (string $line) => json_decode(substr($line, 6), true))
            ->where('type', 'text-delta')
            ->pluck('delta')
            ->implode('');
    }

    // =========================================================================
    // ACCESS
    // =========================================================================

    public function test_a_guest_cannot_chat()
    {
        $this->postJson(route('assistant.chat'), ['messages' => [$this->userMessage('Hello')]])
            ->assertUnauthorized();

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_an_unverified_user_cannot_chat()
    {
        $unverified = User::factory()->unverified()->create();
        AssistantKey::factory()->create(['user_id' => $unverified->id]);

        $this->chat(as: $unverified)->assertForbidden();

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_it_is_not_found_when_the_user_has_no_assistant()
    {
        $this->chat(as: User::factory()->create())->assertNotFound();

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_it_is_rate_limited_per_user()
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $this->chat()->assertOk();
        }

        $this->chat()->assertTooManyRequests();

        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);

        $this->chat(as: $other)->assertOk();
    }

    // =========================================================================
    // STREAMING
    // =========================================================================

    public function test_it_streams_the_reply_in_the_ui_message_protocol()
    {
        $response = $this->chat()->assertOk();

        $response->assertHeader('x-vercel-ai-ui-message-stream', 'v1');
        $this->assertSame('You have three active goals.', $this->streamedText($response));

        IgniteAssistant::assertPrompted(fn (AgentPrompt $prompt) => $prompt->prompt === 'How am I doing?');
    }

    public function test_a_first_message_starts_a_conversation_owned_by_the_user()
    {
        $response = $this->chat();
        $conversationId = $response->headers->get('X-Conversation-Id');
        $response->streamedContent();

        $this->assertNotNull($conversationId);
        $this->assertDatabaseHas('agent_conversations', [
            'id' => $conversationId,
            'participant_type' => $this->user->getMorphClass(),
            'participant_id' => $this->user->id,
        ]);
        $this->assertSame(2, DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->count());
    }

    public function test_a_follow_up_continues_the_same_conversation()
    {
        $first = $this->chat();
        $conversationId = $first->headers->get('X-Conversation-Id');
        $first->streamedContent();

        $second = $this->chat(['conversation_id' => $conversationId])->assertOk();
        $second->streamedContent();

        $second->assertHeader('X-Conversation-Id', $conversationId);
        $this->assertSame(1, DB::table('agent_conversations')->count());
        $this->assertSame(4, DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->count());
    }

    public function test_it_is_not_found_for_another_users_conversation()
    {
        $first = $this->chat();
        $conversationId = $first->headers->get('X-Conversation-Id');
        $first->streamedContent();

        $other = User::factory()->create();
        AssistantKey::factory()->create(['user_id' => $other->id]);

        $this->chat(['conversation_id' => $conversationId], $other)->assertNotFound();

        $this->assertSame(2, DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->count());
    }

    public function test_it_is_not_found_for_a_conversation_that_does_not_exist()
    {
        $this->chat(['conversation_id' => '01990000-0000-7000-8000-000000000000'])->assertNotFound();
    }

    // =========================================================================
    // INPUT
    // =========================================================================

    public function test_the_goal_on_screen_is_passed_to_the_assistant()
    {
        $goal = Goal::factory()->create(['user_id' => $this->user->id, 'category_id' => null]);

        $this->chat(['goal_id' => $goal->id])->assertOk();

        IgniteAssistant::assertPrompted(
            fn (AgentPrompt $prompt) => str_contains($prompt->agent->instructions(), "the goal with id {$goal->id}")
        );
    }

    public function test_another_users_goal_is_ignored()
    {
        $goal = Goal::factory()->create(['user_id' => User::factory()->create()->id, 'category_id' => null]);

        $this->chat(['goal_id' => $goal->id])->assertOk();

        IgniteAssistant::assertPrompted(
            fn (AgentPrompt $prompt) => ! str_contains($prompt->agent->instructions(), 'currently looking at')
        );
    }

    public function test_attachments_are_dropped()
    {
        $file = ['type' => 'file', 'mediaType' => 'image/png', 'url' => 'data:image/png;base64,iVBORw0KGgo='];

        $this->chat(['messages' => [$this->userMessage('What is this?', [$file])]])->assertOk();

        IgniteAssistant::assertPrompted(
            fn (AgentPrompt $prompt) => $prompt->prompt === 'What is this?' && $prompt->attachments->isEmpty()
        );
    }

    public function test_messages_are_required()
    {
        $this->chat(['messages' => []])->assertJsonValidationErrors('messages');

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_an_empty_message_is_rejected()
    {
        $this->chat(['messages' => [$this->userMessage('   ')]])->assertJsonValidationErrors('messages');

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_a_message_over_the_length_limit_is_rejected()
    {
        $tooLong = str_repeat('a', AssistantChatRequest::MAX_PROMPT_LENGTH + 1);

        $this->chat(['messages' => [$this->userMessage($tooLong)]])->assertJsonValidationErrors('messages');

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_a_turn_ending_on_an_assistant_message_without_decisions_is_rejected()
    {
        $assistantMessage = ['id' => 'message-2', 'role' => 'assistant', 'parts' => [['type' => 'text', 'text' => 'Hi']]];

        $this->chat(['messages' => [$this->userMessage('Hello'), $assistantMessage]])
            ->assertJsonValidationErrors('messages');

        IgniteAssistant::assertNeverPrompted();
    }

    public function test_the_conversation_id_must_be_a_uuid()
    {
        $this->chat(['conversation_id' => 'not-a-uuid'])->assertJsonValidationErrors('conversation_id');
    }
}

<?php

namespace App\Ai\Storage;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Storage\DatabaseConversationStore;
use Laravel\Ai\Vercel\Vercel;
use stdClass;

class EncryptedConversationStore extends DatabaseConversationStore
{
    private const ENCRYPTED_CONVERSATION_COLUMNS = ['title'];

    private const ENCRYPTED_MESSAGE_COLUMNS = ['content', 'attachments', 'steps', 'meta'];

    /**
     * Get the user's most recent conversations.
     *
     * @return Collection<int, stdClass>
     */
    public function latestConversationsOf(User $user, int $limit): Collection
    {
        return $this->conversationsOf($user)
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'updated_at']);
    }

    /**
     * Find one of the user's conversations.
     */
    public function conversationOf(User $user, string $conversationId): ?stdClass
    {
        return $this->conversationsOf($user)->where('id', $conversationId)->first(['id', 'title', 'updated_at']);
    }

    /**
     * Get a conversation's latest messages in the shape the chat panel renders.
     *
     * @return list<array<string, mixed>>
     */
    public function uiMessagesOf(string $conversationId, int $limit): array
    {
        $messages = $this->table($this->messagesTable())
            ->where('conversation_id', $conversationId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (stdClass $record): ConversationMessage => (new ConversationMessage)->newFromBuilder((array) $record));

        return Vercel::toUiMessages($messages);
    }

    public function renameConversation(string $conversationId, string $title): void
    {
        $this->table($this->conversationsTable())->where('id', $conversationId)->update(['title' => $title]);
    }

    public function deleteConversation(string $conversationId): void
    {
        DB::connection($this->connection)->transaction(function () use ($conversationId): void {
            $this->table($this->messagesTable())->where('conversation_id', $conversationId)->delete();
            $this->table($this->conversationsTable())->where('id', $conversationId)->delete();
        });
    }

    public function deleteConversationsOf(User $user): void
    {
        DB::connection($this->connection)->transaction(function () use ($user): void {
            $this->table($this->messagesTable())
                ->whereIn('conversation_id', $this->conversationsOf($user)->select('id'))
                ->delete();
            $this->conversationsOf($user)->delete();
        });
    }

    /**
     * Get a query builder that encrypts what it writes and decrypts what it reads.
     */
    protected function table(string $table): Builder
    {
        $connection = DB::connection($this->connection);

        return (new EncryptingQueryBuilder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor()))
            ->encrypting($table === $this->conversationsTable()
                ? self::ENCRYPTED_CONVERSATION_COLUMNS
                : self::ENCRYPTED_MESSAGE_COLUMNS)
            ->from($table);
    }

    private function conversationsOf(User $user): Builder
    {
        return $this->table($this->conversationsTable())
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey());
    }
}

<?php

namespace App\Http\Controllers;

use App\Ai\Storage\EncryptedConversationStore;
use App\Http\Requests\RenameAssistantConversationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssistantConversationController extends Controller
{
    public const LISTED_CONVERSATIONS = 30;

    public const LOADED_MESSAGES = 100;

    public function __construct(private readonly EncryptedConversationStore $conversations) {}

    /**
     * List the user's most recent conversations.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'conversations' => $this->conversations->latestConversationsOf($request->user(), self::LISTED_CONVERSATIONS),
        ]);
    }

    /**
     * Return one of the user's conversations with its latest messages, ready for the chat panel.
     */
    public function show(Request $request, string $conversation): JsonResponse
    {
        $found = $this->conversations->conversationOf($request->user(), $conversation) ?? abort(404);

        return response()->json([
            'id' => $found->id,
            'title' => $found->title,
            'messages' => $this->conversations->uiMessagesOf($found->id, self::LOADED_MESSAGES),
        ]);
    }

    /**
     * Rename one of the user's conversations.
     */
    public function update(RenameAssistantConversationRequest $request, string $conversation): JsonResponse
    {
        $found = $this->conversations->conversationOf($request->user(), $conversation) ?? abort(404);

        $this->conversations->renameConversation($found->id, $request->validated('title'));

        return response()->json(['id' => $found->id, 'title' => $request->validated('title')]);
    }

    /**
     * Delete one of the user's conversations with its messages.
     */
    public function destroy(Request $request, string $conversation): Response
    {
        $found = $this->conversations->conversationOf($request->user(), $conversation) ?? abort(404);

        $this->conversations->deleteConversation($found->id);

        return response()->noContent();
    }
}

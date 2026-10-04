<?php

namespace App\Http\Controllers;

use App\Http\Requests\RenameAssistantConversationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Vercel\Vercel;

class AssistantConversationController extends Controller
{
    public const LISTED_CONVERSATIONS = 30;

    public const LOADED_MESSAGES = 100;

    /**
     * List the user's most recent conversations.
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = $request->user()->conversations()
            ->latest('updated_at')
            ->limit(self::LISTED_CONVERSATIONS)
            ->get(['id', 'title', 'updated_at']);

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Return one of the user's conversations with its latest messages, ready for the chat panel.
     */
    public function show(Request $request, string $conversation): JsonResponse
    {
        $conversation = $request->user()->conversations()->findOrFail($conversation);

        $messages = $conversation->messages()
            ->latest('id')
            ->limit(self::LOADED_MESSAGES)
            ->get()
            ->reverse();

        return response()->json([
            ...$conversation->only(['id', 'title']),
            'messages' => Vercel::toUiMessages($messages),
        ]);
    }

    /**
     * Rename one of the user's conversations.
     */
    public function update(RenameAssistantConversationRequest $request, string $conversation): JsonResponse
    {
        $conversation = $request->user()->conversations()->findOrFail($conversation);

        $conversation->timestamps = false;
        $conversation->update(['title' => $request->validated('title')]);

        return response()->json($conversation->only(['id', 'title']));
    }

    /**
     * Delete one of the user's conversations with its messages.
     */
    public function destroy(Request $request, string $conversation): Response
    {
        $conversation = $request->user()->conversations()->findOrFail($conversation);

        DB::connection($conversation->getConnectionName())->transaction(function () use ($conversation) {
            $conversation->messages()->delete();
            $conversation->delete();
        });

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}

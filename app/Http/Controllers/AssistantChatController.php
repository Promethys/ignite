<?php

namespace App\Http\Controllers;

use App\Ai\Agents\IgniteAssistant;
use App\Http\Requests\AssistantChatRequest;
use App\Services\Assistant\AssistantKeyResolver;
use Symfony\Component\HttpFoundation\Response;

class AssistantChatController extends Controller
{
    /**
     * Stream the assistant's reply to the user's message or approval decisions.
     */
    public function __invoke(
        AssistantChatRequest $request,
        AssistantKeyResolver $resolver,
    ): Response {
        $user = $request->user();
        $connection = $resolver->resolve($user) ?? abort(404);
        $conversationId = $request->validated('conversation_id');

        abort_if($conversationId !== null && $user->conversations()->whereKey($conversationId)->doesntExist(), 404);

        $currentGoal = $request->validated('goal_id') === null
            ? null
            : $user->goals()->find($request->validated('goal_id'));

        $stream = (new IgniteAssistant($user, $connection, $currentGoal))
            ->continueOrStart($conversationId, $user)
            ->stream($request->turn())
            ->usingVercelDataProtocol($request->continuedMessageId());

        $response = $stream->toResponse($request);
        $response->headers->set('X-Conversation-Id', $conversationId ?? $stream->conversationId);

        return $response;
    }
}

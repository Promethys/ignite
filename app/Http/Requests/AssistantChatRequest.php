<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Vercel\Vercel;

class AssistantChatRequest extends FormRequest
{
    public const MAX_PROMPT_LENGTH = 4000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'messages' => ['required', 'array', 'max:200'],
            'messages.*' => ['array'],
            'conversation_id' => ['nullable', 'uuid'],
            'goal_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * The approval decisions or the text the user just sent, never their attachments.
     */
    public function turn(): Decisions|string
    {
        $chat = Vercel::chat($this);

        $turn = $chat->decisions() ?? trim($chat->message()->content ?? '');

        if ($turn === '') {
            throw ValidationException::withMessages(['messages' => __('validation.required', ['attribute' => 'message'])]);
        }

        if (is_string($turn) && mb_strlen($turn) > self::MAX_PROMPT_LENGTH) {
            throw ValidationException::withMessages([
                'messages' => __('validation.max.string', ['attribute' => 'message', 'max' => self::MAX_PROMPT_LENGTH]),
            ]);
        }

        return $turn;
    }
}

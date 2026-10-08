<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenameAssistantConversationRequest extends FormRequest
{
    public const MAX_TITLE_LENGTH = 100;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:'.self::MAX_TITLE_LENGTH],
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use App\Enums\AssistantProvider;
use App\Http\Requests\Settings\Concerns\ChecksAssistantKey;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssistantKeyRequest extends FormRequest
{
    use ChecksAssistantKey;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'api_key' => ['nullable', 'string', 'max:500'],
            'model' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function providerToCheck(): ?AssistantProvider
    {
        return $this->user()->assistantKeys()->findOrFail($this->route('assistantKey'))->provider;
    }
}

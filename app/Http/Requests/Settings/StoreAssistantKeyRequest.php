<?php

namespace App\Http\Requests\Settings;

use App\Enums\AssistantProvider;
use App\Http\Requests\Settings\Concerns\ChecksAssistantKey;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssistantKeyRequest extends FormRequest
{
    use ChecksAssistantKey;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider' => [
                'required',
                Rule::enum(AssistantProvider::class),
                Rule::unique('assistant_keys', 'provider')->where('user_id', $this->user()->id),
            ],
            'api_key' => ['required', 'string', 'max:500'],
            'model' => ['nullable', 'string', 'max:255'],
            'consent' => ['accepted'],
        ];
    }

    protected function providerToCheck(): ?AssistantProvider
    {
        return AssistantProvider::tryFrom((string) $this->input('provider'));
    }
}

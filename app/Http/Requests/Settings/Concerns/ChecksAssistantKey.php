<?php

namespace App\Http\Requests\Settings\Concerns;

use App\Enums\AssistantKeyCheck;
use App\Enums\AssistantProvider;
use App\Services\Assistant\AssistantKeyChecker;
use Illuminate\Validation\Validator;

trait ChecksAssistantKey
{
    abstract protected function providerToCheck(): ?AssistantProvider;

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $provider = $this->providerToCheck();

                if ($this->isPrecognitive() || $validator->errors()->isNotEmpty() || blank($this->input('api_key')) || $provider === null) {
                    return;
                }

                $message = match (app(AssistantKeyChecker::class)->check($provider, $this->input('api_key'))) {
                    AssistantKeyCheck::Valid => null,
                    AssistantKeyCheck::Invalid => __('validation.custom.api_key.rejected', ['provider' => $provider->label()]),
                    AssistantKeyCheck::Unreachable => __('validation.custom.api_key.unverifiable', ['provider' => $provider->label()]),
                };

                if ($message !== null) {
                    $validator->errors()->add('api_key', $message);
                }
            },
        ];
    }
}

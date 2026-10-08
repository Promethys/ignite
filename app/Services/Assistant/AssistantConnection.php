<?php

namespace App\Services\Assistant;

class AssistantConnection
{
    /**
     * @param  array<string, mixed>  $providerConfiguration
     */
    public function __construct(
        public readonly array $providerConfiguration,
        public readonly ?string $model = null,
    ) {}
}

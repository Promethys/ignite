<?php

namespace App\Jobs\Webhooks;

use App\Services\Discord\DiscordOpsNotifier;
use App\Services\Webhooks\FormbricksResponseFormatter;
use App\Traits\Jobs\ConfiguresRetries;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessFormbricksResponse implements ShouldQueue
{
    use ConfiguresRetries, Queueable;

    public array $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->payload)) {
            return;
        }

        $formatted = FormbricksResponseFormatter::formatToDiscordWebhook($this->payload);

        DiscordOpsNotifier::send($formatted);
    }
}

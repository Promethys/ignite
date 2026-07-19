<?php

namespace App\Services\Discord;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordOpsNotifier
{
    public static function send(array $payload)
    {
        if (empty($payload)) {
            return;
        }

        $url = config('services.discord.ops_webhook_url');

        if (empty($url)) {
            Log::warning('Discord ops webhook URL is not configured; notification skipped.');

            return;
        }

        try {
            $response = Http::timeout(5)
                ->post($url, $payload);

            if ($response->failed()) {
                Log::error('Discord ops notification failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Discord ops notification threw an exception.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ]);
        }
    }
}

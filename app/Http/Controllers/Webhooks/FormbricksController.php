<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\Webhooks\ProcessFormbricksResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FormbricksController extends Controller
{
    public function handle(Request $request)
    {
        if (! config('services.discord.ops_enabled')) {
            return response()->noContent();
        }

        try {
            $payload = $request->attributes->get('webhook_payload');
            $webhookId = $request->attributes->get('webhook_id');

            $cached = Cache::add("formbricks:webhooks:$webhookId", $webhookId, now()->plus(hours: 4));

            if (! $cached) {
                Log::channel('formbricks')->warning('Received a webhook that was already processed', [
                    'webhook_id' => $webhookId,
                ]);

                return response()->noContent();
            }

            ProcessFormbricksResponse::dispatchAfterResponse($payload);

            return response()->noContent();
        } catch (\Throwable $exception) {
            Log::channel('formbricks')->error('Failed to process Formbricks webhook.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'code' => $exception->getCode(),
            ]);

            return response(null, 500);
        }
    }
}

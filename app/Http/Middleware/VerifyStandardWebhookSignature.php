<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Svix\Exception\WebhookVerificationException;
use Svix\Webhook;
use Symfony\Component\HttpFoundation\Response;

class VerifyStandardWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        if ($this->isSvixCompatible($provider)) {
            return $this->verifySvixRequest($request, $next, $provider);
        }

        throw new \Exception('Provider not supported');
    }

    protected function getWebhookSecret(string $provider): ?string
    {
        return match ($provider) {
            'formbricks' => config('services.formbricks.webhook_secret'),
            default => null,
        };
    }

    protected function isSvixCompatible(string $provider): bool
    {
        return in_array($provider, ['formbricks'], true);
    }

    protected function verifySvixRequest(Request $request, Closure $next, string $provider): Response
    {
        $payload = $request->getContent();
        $headers = collect($request->headers->all())->transform(fn ($item) => $item[0]);

        try {
            $webhook = new Webhook($this->getWebhookSecret($provider));
            $decoded = $webhook->verify($payload, $headers);

            $request->attributes->set('webhook_payload', $decoded);
            $request->attributes->set('webhook_id', $headers['svix-id'] ?? $headers['webhook-id']);

            return $next($request);
        } catch (WebhookVerificationException $e) {
            return new Response('', 400);
        }
    }
}

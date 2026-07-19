<?php

namespace App\Services\Webhooks;

use Illuminate\Support\Arr;

class FormbricksResponseFormatter
{
    /**
     * Ignite flame accent, as a Discord decimal-encoded color.
     */
    private const EMBED_COLOR = 0xF97316;

    /**
     * Discord caps a field value at 1024 characters.
     */
    private const MAX_FIELD_VALUE = 1024;

    /**
     * Turn a Formbricks `responseFinished` payload into a Discord execute-webhook payload.
     *
     * Survey answers are keyed by question id (not label) and the payload carries no
     * question headlines, so we surface the respondent, response metadata and a link back
     * to Formbricks where the answers render with their real labels, rather than mapping
     * ids per survey (which would not be portable across self-hosted instances).
     */
    public static function formatToDiscordWebhook(array $payload): array
    {
        $response = data_get($payload, 'data', []);
        $surveyTitle = data_get($response, 'survey.title', 'Formbricks survey');
        $url = self::responseUrl($response);

        return [
            'content' => "New feedback response: {$surveyTitle}",
            'embeds' => [
                array_filter([
                    'title' => $surveyTitle,
                    'url' => $url,
                    'description' => $url ? "[View full response in Formbricks]({$url})" : null,
                    'color' => self::EMBED_COLOR,
                    'timestamp' => data_get($response, 'createdAt'),
                    'fields' => self::fields($response),
                    'footer' => self::footer($response),
                ]),
            ],
        ];
    }

    /**
     * Respondent and context, then a label-free preview of the answer values.
     */
    private static function fields(array $response): array
    {
        return collect([
            ['name' => 'Respondent', 'value' => self::respondent($response), 'inline' => false],
            ['name' => 'Language', 'value' => data_get($response, 'language'), 'inline' => true],
            ['name' => 'Country', 'value' => data_get($response, 'meta.country'), 'inline' => true],
            ['name' => 'Device', 'value' => data_get($response, 'meta.userAgent.device'), 'inline' => true],
            ['name' => 'Answers', 'value' => self::answers($response), 'inline' => false],
        ])
            ->filter(fn ($field) => filled($field['value']))
            ->map(fn ($field) => [...$field, 'value' => (string) $field['value']])
            ->values()
            ->all();
    }

    private static function respondent(array $response): ?string
    {
        $name = data_get($response, 'contactAttributes.name');
        $email = data_get($response, 'contactAttributes.email');

        return match (true) {
            $name && $email => "{$name} ({$email})",
            (bool) $email => $email,
            (bool) $name => $name,
            default => null,
        };
    }

    /**
     * The answer values only (labels live in Formbricks), length-bounded for Discord.
     */
    private static function answers(array $response): ?string
    {
        $values = collect(data_get($response, 'data', []))
            ->map(fn ($answer) => self::stringifyAnswer($answer))
            ->filter(fn ($value) => $value !== '')
            ->map(fn ($value) => "- {$value}")
            ->values();

        if ($values->isEmpty()) {
            return null;
        }

        $list = $values->implode("\n");

        return mb_strlen($list) > self::MAX_FIELD_VALUE
            ? mb_substr($list, 0, self::MAX_FIELD_VALUE - 3).'...'
            : $list;
    }

    private static function responseUrl(array $response): ?string
    {
        $appUrl = config('services.formbricks.app_url');
        $workspaceId = config('services.formbricks.workspace_id');
        $surveyId = data_get($response, 'surveyId');

        if (! $appUrl || ! $workspaceId || ! $surveyId) {
            return null;
        }

        return rtrim($appUrl, '/')."/workspaces/{$workspaceId}/surveys/{$surveyId}/responses";
    }

    private static function footer(array $response): ?array
    {
        $id = data_get($response, 'id');

        return $id ? ['text' => "Response {$id}"] : null;
    }

    private static function stringifyAnswer(mixed $answer): string
    {
        $value = trim(match (true) {
            is_array($answer) => implode(', ', Arr::flatten($answer)),
            is_bool($answer) => $answer ? 'Yes' : 'No',
            is_null($answer) => '',
            default => (string) $answer,
        });

        // Collapse newlines so a multi-line answer stays a single list item.
        return preg_replace('/\R+/u', ' ', $value);
    }
}

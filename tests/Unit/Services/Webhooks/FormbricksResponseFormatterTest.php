<?php

namespace Tests\Unit\Services\Webhooks;

use App\Services\Webhooks\FormbricksResponseFormatter;
use Tests\TestCase;

class FormbricksResponseFormatterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.formbricks.app_url' => 'https://fb.example.com',
            'services.formbricks.workspace_id' => 'ws_test',
        ]);
    }

    private function fixture(): array
    {
        return json_decode(
            file_get_contents(base_path('tests/Fixtures/webhooks/formbricks/response-finished.json')),
            true
        );
    }

    private function format(array $payload): array
    {
        return FormbricksResponseFormatter::formatToDiscordWebhook($payload);
    }

    private function embed(array $payload): array
    {
        return $this->format($payload)['embeds'][0];
    }

    /**
     * @return array<string, string> field name => field value
     */
    private function fieldMap(array $embed): array
    {
        return collect($embed['fields'] ?? [])
            ->mapWithKeys(fn ($field) => [$field['name'] => $field['value']])
            ->all();
    }

    public function test_builds_an_ops_alert_from_a_real_response()
    {
        $result = $this->format($this->fixture());
        $embed = $result['embeds'][0];
        $url = 'https://fb.example.com/workspaces/ws_test/surveys/survey_anonymizedid00000001/responses';

        $this->assertSame('New feedback response: Feedback Box', $result['content']);
        $this->assertSame('Feedback Box', $embed['title']);
        $this->assertSame($url, $embed['url']);
        $this->assertStringContainsString($url, $embed['description']);
        $this->assertSame('Response resp_anonymizedid000000001', $embed['footer']['text']);

        $fields = $this->fieldMap($embed);
        $this->assertSame('Test User (test.user@example.com)', $fields['Respondent']);
        $this->assertSame('fr', $fields['Language']);
        $this->assertSame('FR', $fields['Country']);
        $this->assertSame('desktop', $fields['Device']);
        $this->assertSame("- Autre\n- test webhook", $fields['Answers']);
    }

    public function test_never_exposes_raw_question_ids_as_field_names()
    {
        $fields = $this->fieldMap($this->embed($this->fixture()));

        $this->assertArrayNotHasKey('egpf1195bqunhzymfkm69prt', $fields);
        $this->assertArrayNotHasKey('mp7yutkizfmsiubhfkq0n45m', $fields);
    }

    public function test_omits_the_link_when_formbricks_config_is_missing()
    {
        config(['services.formbricks.workspace_id' => null]);

        $embed = $this->embed($this->fixture());

        $this->assertArrayNotHasKey('url', $embed);
        $this->assertArrayNotHasKey('description', $embed);
    }

    public function test_omits_respondent_for_an_anonymous_response()
    {
        $embed = $this->embed([
            'data' => ['survey' => ['title' => 'Feedback'], 'data' => ['q' => 'hi']],
        ]);

        $this->assertArrayNotHasKey('Respondent', $this->fieldMap($embed));
    }

    public function test_joins_array_and_boolean_answers_without_labels()
    {
        $embed = $this->embed([
            'data' => [
                'data' => [
                    'multi' => ['a', 'b'],
                    'consent' => true,
                    'skipped' => null,
                ],
            ],
        ]);

        $this->assertSame("- a, b\n- Yes", $this->fieldMap($embed)['Answers']);
    }

    public function test_truncates_long_answers()
    {
        $embed = $this->embed([
            'data' => ['data' => ['essay' => str_repeat('a', 2000)]],
        ]);

        $value = $this->fieldMap($embed)['Answers'];
        $this->assertSame(1024, mb_strlen($value));
        $this->assertStringEndsWith('...', $value);
    }

    public function test_falls_back_when_the_survey_is_missing()
    {
        $result = $this->format([]);
        $embed = $result['embeds'][0];

        $this->assertSame('New feedback response: Formbricks survey', $result['content']);
        $this->assertSame('Formbricks survey', $embed['title']);
        $this->assertArrayNotHasKey('url', $embed);
        $this->assertArrayNotHasKey('fields', $embed);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_landing_page_carries_the_social_tags_in_the_response_body()
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:description"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:card"', false);
    }

    public function test_the_social_title_is_built_from_the_application_name()
    {
        config(['app.name' => 'Rebranded']);

        $response = $this->get(route('home'));

        $response->assertSee('content="Rebranded - '.__('landing.meta.tagline').'"', false)
            ->assertSee('property="og:site_name" content="Rebranded"', false);
    }

    public function test_the_landing_page_emits_valid_structured_data()
    {
        $response = $this->get(route('home'));

        $matched = preg_match(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $response->getContent(),
            $matches
        );

        $this->assertSame(1, $matched, 'No JSON-LD block was rendered.');

        $decoded = json_decode($matches[1], true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('SoftwareApplication', $decoded['@type']);
    }

    public function test_the_structured_data_is_limited_to_the_landing_page()
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertDontSee('application/ld+json', false)
            ->assertSee('property="og:title"', false);
    }

    public function test_the_social_tags_follow_the_resolved_locale()
    {
        $response = $this->withUnencryptedCookie('locale', 'fr')->get(route('home'));

        $response->assertOk()
            ->assertSee('property="og:locale" content="fr_FR"', false)
            ->assertSee(e(__('landing.meta.description', [], 'fr')), false);
    }

    public function test_the_authenticated_areas_are_disallowed_to_crawlers()
    {
        $robots = file_get_contents(public_path('robots.txt'));

        foreach (['/dashboard', '/goals', '/settings', '/login', '/register', '/admin'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}", $robots);
        }
    }
}

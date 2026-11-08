<?php

namespace Tests\Feature\Services\Help;

use App\Services\Help\GuideRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GuideRendererTest extends TestCase
{
    public function test_it_strips_raw_html_and_unsafe_links(): void
    {
        $result = $this->render("<script>alert('unsafe')</script>\n\n<img src=x onerror=alert(1)>\n\n[Unsafe](javascript:alert%281%29)\n\n[Safe](https://example.com)");

        $this->assertStringNotContainsString('<script', $result['html']);
        $this->assertStringNotContainsString('<img', $result['html']);
        $this->assertStringNotContainsString('javascript:', $result['html']);
        $this->assertStringContainsString('href="https://example.com"', $result['html']);
    }

    public function test_it_adds_unique_heading_ids_and_returns_only_h2_and_h3_for_navigation(): void
    {
        $result = $this->render("# Title\n\n## First section\n\n### Details\n\n## First section\n\n#### Deep detail");

        $this->assertSame(['First section', 'Details', 'First section'], array_column($result['headings'], 'text'));
        $this->assertSame([2, 3, 2], array_column($result['headings'], 'level'));
        $this->assertCount(3, array_unique(array_column($result['headings'], 'id')));

        foreach ($result['headings'] as $heading) {
            $this->assertStringContainsString('id="'.$heading['id'].'"', $result['html']);
            $this->assertStringContainsString('href="#'.$heading['id'].'"', $result['html']);
        }
    }

    #[DataProvider('calloutProvider')]
    public function test_it_renders_github_callouts(string $type): void
    {
        $result = $this->render("> [!{$type}]\n> Helpful **content**.\n\n> A regular quote.");

        $this->assertStringContainsString('docs-callout-'.strtolower($type), $result['html']);
        $this->assertStringContainsString('class="docs-callout-title"', $result['html']);
        $this->assertStringContainsString('class="docs-callout-icon"', $result['html']);
        $this->assertStringContainsString('aria-hidden="true"', $result['html']);
        $this->assertStringContainsString('</svg>'.ucfirst(strtolower($type)), $result['html']);
        $this->assertStringContainsString('<strong>content</strong>', $result['html']);
        $this->assertStringNotContainsString("[!{$type}]", $result['html']);
        $this->assertStringContainsString('<blockquote>', $result['html']);
    }

    public function test_it_renders_gfm_tables(): void
    {
        $result = $this->render("| Goal | Progress |\n| --- | --- |\n| Read | 3 |\n");

        $this->assertStringContainsString('<table>', $result['html']);
        $this->assertStringContainsString('<th>Goal</th>', $result['html']);
        $this->assertStringContainsString('<td>3</td>', $result['html']);
    }

    #[DataProvider('linkProvider')]
    public function test_it_rewrites_links_and_images(string $markdown, string $attribute): void
    {
        $this->assertStringContainsString($attribute, $this->render($markdown)['html']);
    }

    public function test_external_links_have_a_new_tab_hint_and_internal_links_do_not(): void
    {
        app()->setLocale('fr');
        $result = $this->render('[External](https://example.com) [Protocol relative](//example.com/path) [Developer](/installation) [Guide](/guide/goal-types) [Docs](/docs/themes) [Anchor](#section) [Mail](mailto:help@example.com) [Same site]('.config('app.url').'/docs)');
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$result['html']);
        $links = iterator_to_array($document->getElementsByTagName('a'));
        foreach (array_slice($links, 0, 3) as $link) {
            $this->assertSame('_blank', $link->getAttribute('target'));
            $this->assertSame('noopener', $link->getAttribute('rel'));
            $this->assertSame('docs-external-link', $link->getAttribute('class'));
            $this->assertSame('sr-only', $link->getElementsByTagName('span')->item(0)->getAttribute('class'));
            $this->assertStringContainsString(__('public.opens_in_new_tab'), $link->textContent);
        }
        foreach (array_slice($links, 3) as $link) {
            $this->assertFalse($link->hasAttribute('target'));
            $this->assertFalse($link->hasAttribute('rel'));
            $this->assertCount(0, $link->getElementsByTagName('span'));
        }
        $this->assertSame('/docs/themes', $links[4]->getAttribute('href'));
        $this->assertStringNotContainsString(__('public.opens_in_new_tab'), $result['text']);
    }

    public function test_it_extracts_plain_body_text_with_decoded_entities(): void
    {
        $result = $this->render("## Heading\n\nBody **content** &amp; `code`.\n\nAnother paragraph.");
        $this->assertStringContainsString('Body content & code.', $result['text']);
        $this->assertStringContainsString('Another paragraph.', $result['text']);
        $this->assertStringNotContainsString('<', $result['text']);
    }

    public static function calloutProvider(): array
    {
        return [['NOTE'], ['TIP'], ['WARNING']];
    }

    public static function linkProvider(): array
    {
        return [
            'guide home' => ['[Guide](/guide/)', 'href="/docs"'],
            'guide root' => ['[Guide](/guide)', 'href="/docs"'],
            'getting started' => ['[Start](/guide/getting-started)', 'href="/docs"'],
            'guide topic and anchor' => ['[Types](/guide/goal-types#recurring)', 'href="/docs/goal-types#recurring"'],
            'developer page' => ['[Install](/getting-started)', 'href="https://ignite-docs.promethys.dev/getting-started"'],
            'developer feature' => ['[MCP](/features/mcp-server)', 'href="https://ignite-docs.promethys.dev/features/mcp-server"'],
            'legacy image' => ['![Dashboard](/dashboard_filled.png)', 'src="/images/guide/dashboard_filled.png"'],
            'guide image' => ['![Dashboard](/images/guide/dashboard_filled.png)', 'src="/images/guide/dashboard_filled.png"'],
            'external link' => ['[Example](https://example.com/path)', 'href="https://example.com/path"'],
            'local anchor' => ['[Section](#section)', 'href="#section"'],
        ];
    }

    private function render(string $markdown): array
    {
        return app(GuideRenderer::class)->render($markdown);
    }
}

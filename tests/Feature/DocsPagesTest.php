<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Help\UserGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DocsPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    #[DataProvider('guidePageProvider')]
    public function test_guests_can_read_every_guide_page(string $path, string $component, string $topic, string $title, string $file): void
    {
        $this->assertGuidePage($path, $component, $topic, $title, $file);
        $this->assertGuest();
    }

    #[DataProvider('guidePageProvider')]
    public function test_authenticated_users_can_read_every_guide_page(string $path, string $component, string $topic, string $title, string $file): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->assertGuidePage($path, $component, $topic, $title, $file);
        $this->assertAuthenticatedAs($user);
    }

    public function test_unknown_topics_show_the_docs_not_found_page(): void
    {
        $this->get('/docs/missing-page')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('docs/Show')
                ->where('article', null)
                ->has('pages', 5)
                ->has('searchIndex', 5));
    }

    public function test_the_home_page_has_ordered_navigation_and_a_search_index(): void
    {
        $this->get(route('docs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pages.0.topic', 'getting-started')
                ->where('pages.0.url', '/docs')
                ->where('previous', null)
                ->where('next.topic', 'goal-types')
                ->where('next.url', '/docs/goal-types')
                ->has('searchIndex', 5)
                ->where('searchIndex.4.text', fn (string $text) => str_contains($text, 'The preview in the form') && ! str_contains($text, '<p>') && mb_strlen($text) <= 20000)
                ->has('searchIndex.0', fn (Assert $entry) => $entry
                    ->where('topic', 'getting-started')
                    ->where('title', 'Getting Started')
                    ->where('url', '/docs')
                    ->where('summary', fn (string $summary) => $summary !== '')
                    ->where('text', fn (string $text) => $text !== '')
                    ->has('headings.0', fn (Assert $heading) => $heading
                        ->where('id', fn (string $id) => $id !== '')
                        ->where('text', fn (string $text) => $text !== '')
                        ->where('level', fn (int $level) => in_array($level, [2, 3], true)))));
    }

    public function test_getting_started_redirects_permanently_to_the_docs_home(): void
    {
        $this->get('/docs/getting-started')->assertStatus(301)->assertRedirect('/docs');
        $this->actingAs(User::factory()->create())->get('/docs/getting-started')->assertStatus(301)->assertRedirect('/docs');
    }

    public function test_search_counts_are_pluralised_in_both_languages(): void
    {
        foreach (['en' => ['result', 'results'], 'fr' => ['résultat', 'résultats']] as $locale => [$singular, $plural]) {
            $this->assertSame('1 '.$singular, trans_choice('public.docs.results', 1, locale: $locale));
            $this->assertSame('0 '.$plural, trans_choice('public.docs.results', 0, locale: $locale));
            $this->assertSame('2 '.$plural, trans_choice('public.docs.results', 2, locale: $locale));
        }
    }

    public function test_the_last_page_has_a_previous_page_and_no_next_page(): void
    {
        $this->get(route('docs.show', 'themes'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('previous.topic', 'assistant')
                ->where('previous.url', '/docs/assistant')
                ->where('next', null));
    }

    public static function guidePageProvider(): array
    {
        return [
            'home' => ['/docs', 'docs/Index', 'getting-started', 'Getting Started', 'index'],
            'goal types' => ['/docs/goal-types', 'docs/Show', 'goal-types', 'Goal Types', 'goal-types'],
            'tracking progress' => ['/docs/tracking-progress', 'docs/Show', 'tracking-progress', 'Tracking Progress', 'tracking-progress'],
            'assistant' => ['/docs/assistant', 'docs/Show', 'assistant', 'Use the Assistant', 'assistant'],
            'themes' => ['/docs/themes', 'docs/Show', 'themes', 'Themes', 'themes'],
        ];
    }

    private function assertGuidePage(string $path, string $component, string $topic, string $title, string $file): void
    {
        $this->get($path)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->has('pages', 5)
                ->has('searchIndex', 5)
                ->where('article.topic', $topic)
                ->where('article.title', $title)
                ->where('article.summary', fn (string $summary) => $summary !== '')
                ->where('article.html', fn (string $html) => str_contains($html, '<p>'))
                ->has('article.headings.0', fn (Assert $heading) => $heading
                    ->where('id', fn (string $id) => $id !== '')
                    ->where('text', fn (string $text) => $text !== '')
                    ->where('level', fn (int $level) => in_array($level, [2, 3], true)))
                ->where('article.rawUrl', "https://raw.githubusercontent.com/Promethys/ignite/main/docs/guide/{$file}.md")
                ->where('article.editUrl', "https://github.com/Promethys/ignite/edit/main/docs/guide/{$file}.md")
                ->has('previous')
                ->has('next'));
    }

    public function test_legacy_guide_redirects_land_on_existing_docs_pages(): void
    {
        $rules = collect(file(base_path('docs/public/_redirects'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))
            ->map(fn (string $line): array => preg_split('/\s+/', trim($line)))
            ->mapWithKeys(fn (array $rule): array => [$rule[0] => $rule[1]]);

        $this->assertSame('https://ignite.promethys.dev/docs', $rules['/guide/index']);
        $this->assertLessThan($rules->keys()->search('/guide/*'), $rules->keys()->search('/guide/index'));

        foreach (array_keys(app(UserGuide::class)->topics()) as $topic) {
            if ($topic === 'getting-started') {
                $this->get("/docs/{$topic}")->assertStatus(301)->assertRedirect('/docs');
            } else {
                $this->get("/docs/{$topic}")->assertOk();
            }
        }
    }
}

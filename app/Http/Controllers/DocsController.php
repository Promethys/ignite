<?php

namespace App\Http\Controllers;

use App\Services\Help\GuideRenderer;
use App\Services\Help\UserGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DocsController extends Controller
{
    public function __construct(private readonly UserGuide $guide, private readonly GuideRenderer $renderer) {}

    public function index(): Response
    {
        return Inertia::render('docs/Index', $this->props('getting-started'));
    }

    public function show(Request $request, string $topic): Response|HttpResponse
    {
        if ($topic === 'getting-started') {
            return redirect()->route('docs.index', status: 301);
        }

        $props = $this->props($topic);
        $response = Inertia::render('docs/Show', $props);

        return $props['article'] === null ? $response->toResponse($request)->setStatusCode(404) : $response;
    }

    private function props(string $topic): array
    {
        $pages = [];
        $articles = [];
        foreach ($this->guide->pages() as $page) {
            $file = $page['file'];
            unset($page['file']);
            $page['url'] = $page['topic'] === 'getting-started'
                ? route('docs.index', absolute: false)
                : route('docs.show', ['topic' => $page['topic']], false);
            $pages[] = $page;
            $markdown = preg_replace('/\A#\s+[^\n]+\n*/', '', $this->guide->page($page['topic']));
            $markdown = preg_replace('/\A.*?(?:\n[ \t]*\n|\z)/s', '', $markdown, 1);
            $articles[] = [
                ...$page,
                ...$this->renderer->render($markdown),
                'rawUrl' => 'https://raw.githubusercontent.com/Promethys/ignite/main/docs/guide/'.$file.'.md',
                'editUrl' => 'https://github.com/Promethys/ignite/edit/main/docs/guide/'.$file.'.md',
            ];
        }

        $position = array_search($topic, array_column($pages, 'topic'), true);

        return [
            'pages' => $pages,
            'searchIndex' => array_map(fn (array $article): array => [
                'topic' => $article['topic'],
                'title' => $article['title'],
                'summary' => $article['summary'],
                'url' => $article['url'],
                'headings' => $article['headings'],
                'text' => Str::limit($article['text'], 20000, ''),
            ], $articles),
            'article' => $position === false ? null : $articles[$position],
            'previous' => $position === false ? null : ($pages[$position - 1] ?? null),
            'next' => $position === false ? null : ($pages[$position + 1] ?? null),
        ];
    }
}

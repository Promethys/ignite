<?php

namespace App\Services\Help;

use DOMDocument;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;

class GuideRenderer
{
    /** @return array{html: string, text: string, headings: list<array{id: string, text: string, level: int}>} */
    public function render(string $markdown): array
    {
        $headings = [];
        $environment = new Environment([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'insert' => 'after',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'apply_id_to_heading' => true,
                'aria_hidden' => false,
                'symbol' => '#',
                'title' => '',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            foreach ($event->getDocument()->iterator() as $node) {
                if ($node instanceof Link) {
                    $node->setUrl($this->linkUrl($node->getUrl()));
                }

                if ($node instanceof Image) {
                    $node->setUrl($this->imageUrl($node->getUrl()));
                    $node->data->set('attributes/loading', 'lazy');
                }

                if ($node instanceof BlockQuote) {
                    $first = $node->firstChild()?->firstChild();
                    if ($first instanceof Text && preg_match('/^\[!(NOTE|TIP|WARNING)\](?:\s|$)/', $first->getLiteral(), $match)) {
                        $kind = strtolower($match[1]);
                        $first->setLiteral(ltrim(substr($first->getLiteral(), strlen($match[0]))));
                        $node->data->set('attributes/class', 'docs-callout docs-callout-'.$kind);
                        $label = new Paragraph;
                        $label->data->set('attributes/class', 'docs-callout-title');
                        $label->appendChild(new Text(ucfirst($kind)));
                        $node->prependChild($label);
                    }
                }
            }
        });
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event) use (&$headings): void {
            foreach ($event->getDocument()->iterator() as $node) {
                if ($node instanceof Heading && in_array($node->getLevel(), [2, 3], true)) {
                    $headings[] = [
                        'id' => $node->data->get('attributes/id'),
                        'text' => $this->text($node),
                        'level' => $node->getLevel(),
                    ];
                }
            }
        }, -200);

        $html = (new MarkdownConverter($environment))->convert($markdown)->getContent();
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('#</(?:p|h[1-6]|li|td|th|pre|blockquote)>#', '$0 ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        foreach ($document->getElementsByTagName('a') as $link) {
            $url = $link->getAttribute('href');
            $host = parse_url($url, PHP_URL_HOST);
            if (! $host || ! preg_match('#^(?:https?:)?//#i', $url) || strcasecmp($host, parse_url(config('app.url'), PHP_URL_HOST) ?? '') === 0) {
                continue;
            }

            $link->setAttribute('target', '_blank');
            $link->setAttribute('rel', 'noopener');
            $link->setAttribute('class', 'docs-external-link');
            $hint = $document->createElement('span');
            $hint->setAttribute('class', 'sr-only');
            $hint->appendChild($document->createTextNode(' '.__('public.opens_in_new_tab')));
            $link->appendChild($hint);
        }

        $container = $document->getElementsByTagName('div')->item(0);
        $html = implode('', array_map(fn ($node): string => $document->saveHTML($node), iterator_to_array($container->childNodes)));

        return ['html' => $html, 'text' => $text, 'headings' => $headings];
    }

    private function linkUrl(string $url): string
    {
        if (! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return $url;
        }

        if (preg_match('#^/guide(?:/([^?\#]*))?([?\#].*)?$#', $url, $match)) {
            $topic = preg_replace('/\.md$/', '', rtrim($match[1] ?? '', '/'));
            $path = in_array($topic, ['', 'index', 'getting-started'], true) ? '/docs' : '/docs/'.$topic;

            return $path.($match[2] ?? '');
        }

        if (preg_match('#^/docs(?:[/?\#]|$)#', $url) || str_starts_with($url, '/images/guide/')) {
            return $url;
        }

        return 'https://ignite-docs.promethys.dev'.$url;
    }

    private function imageUrl(string $url): string
    {
        return preg_replace('#^/(dashboard_filled|new_goal_form|quantifiable_goal|recurring_goal|multi_step_goal)\.png(?=[?\#]|$)#', '/images/guide/$1.png', $url);
    }

    private function text(Node $node): string
    {
        $text = '';
        foreach ($node->iterator() as $child) {
            if ($child instanceof Text || $child instanceof Code) {
                $text .= $child->getLiteral();
            }
        }

        return $text;
    }
}

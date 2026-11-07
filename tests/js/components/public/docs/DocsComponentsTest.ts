import ArticleFooterLinks from '@/components/public/docs/ArticleFooterLinks.vue';
import DocsArticle from '@/components/public/docs/DocsArticle.vue';
import DocsNav from '@/components/public/docs/DocsNav.vue';
import DocsSearch from '@/components/public/docs/DocsSearch.vue';
import OnThisPage from '@/components/public/docs/OnThisPage.vue';
import type { GuideSearchPage } from '@/components/public/docs/types';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

vi.mock('laravel-vue-i18n', () => ({ trans: (key: string) => key }));
vi.mock('@/composables/useScrollSpy', () => ({
    useScrollSpy: () => ref('details'),
}));

const pages: GuideSearchPage[] = [
    {
        topic: 'getting-started',
        title: 'Getting Started',
        summary: 'Create your first goal.',
        text: 'Use a private workspace to organise everything.',
        url: '/docs',
        headings: [{ id: 'create', text: 'Create a goal', level: 2 }],
    },
    {
        topic: 'tracking-progress',
        title: 'Tracking Progress',
        summary: 'Follow your progress over time.',
        text: 'Review the calendar for daily activity.',
        url: '/docs/tracking-progress',
        headings: [{ id: 'details', text: 'Record a check-in', level: 3 }],
    },
];
const linkStub = {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
};
const global = { stubs: { Link: linkStub } };

const article = {
    ...pages[0],
    html: '<pre><code>php artisan serve\n</code></pre>',
    rawUrl: 'https://raw.githubusercontent.com/Promethys/ignite/main/docs/guide/index.md',
    editUrl:
        'https://github.com/Promethys/ignite/edit/main/docs/guide/index.md',
};

const clipboardDescriptor = Object.getOwnPropertyDescriptor(
    navigator,
    'clipboard',
);
const permissionsDescriptor = Object.getOwnPropertyDescriptor(
    navigator,
    'permissions',
);
const execCommandDescriptor = Object.getOwnPropertyDescriptor(
    document,
    'execCommand',
);
const mockClipboard = (writeText: ReturnType<typeof vi.fn>) => {
    Object.defineProperty(navigator, 'clipboard', {
        configurable: true,
        value: { writeText },
    });
    Object.defineProperty(navigator, 'permissions', {
        configurable: true,
        value: {
            query: vi
                .fn()
                .mockResolvedValue(
                    Object.assign(new EventTarget(), { state: 'granted' }),
                ),
        },
    });
    Object.defineProperty(document, 'execCommand', {
        configurable: true,
        value: vi.fn(() => {
            throw new Error('Clipboard unavailable');
        }),
    });
};

afterEach(() => {
    for (const [target, key, descriptor] of [
        [navigator, 'clipboard', clipboardDescriptor],
        [navigator, 'permissions', permissionsDescriptor],
        [document, 'execCommand', execCommandDescriptor],
    ] as const) {
        if (descriptor) Object.defineProperty(target, key, descriptor);
        else Reflect.deleteProperty(target, key);
    }
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('DocsNav', () => {
    it('marks only the current topic and follows page URLs', async () => {
        const wrapper = mount(DocsNav, {
            props: { pages, currentTopic: 'tracking-progress' },
            global,
        });

        expect(wrapper.findAll('[aria-current="page"]')).toHaveLength(1);
        expect(wrapper.get('[aria-current="page"]').attributes('href')).toBe(
            '/docs/tracking-progress',
        );
        await wrapper.get('a[href="/docs"]').trigger('click');
        expect(wrapper.emitted('navigate')).toHaveLength(1);
        await wrapper.setProps({ currentTopic: 'getting-started' });
        expect(wrapper.get('[aria-current="page"]').text()).toBe(
            'Getting Started',
        );
        wrapper.unmount();
    });
});

describe('OnThisPage', () => {
    it('marks the active heading and updates when another heading is selected', async () => {
        const wrapper = mount(OnThisPage, {
            props: { headings: pages.flatMap((page) => page.headings) },
        });

        expect(
            wrapper.get('[aria-current="location"]').attributes('href'),
        ).toBe('#details');
        await wrapper.get('a[href="#create"]').trigger('click');
        expect(
            wrapper.get('[aria-current="location"]').attributes('href'),
        ).toBe('#create');
        wrapper.unmount();
    });

    it('omits navigation when the article has no headings', () => {
        const wrapper = mount(OnThisPage, { props: { headings: [] } });
        expect(wrapper.find('nav').exists()).toBe(false);
        wrapper.unmount();
    });
});

describe('DocsSearch', () => {
    it.each([
        ['STARTED', 'Getting Started'],
        ['over time', 'Tracking Progress'],
        ['check-in', 'Tracking Progress'],
        ['calendar', 'Tracking Progress'],
        ['private workspace', 'Getting Started'],
        ['progress calendar', 'Tracking Progress'],
    ])(
        'searches titles, summaries and headings for %s',
        async (query, title) => {
            const wrapper = mount(DocsSearch, {
                props: { searchIndex: pages },
                global,
            });
            await wrapper.get('input[type="search"]').setValue(query);
            expect(wrapper.findAll('li')).toHaveLength(1);
            expect(wrapper.get('li a').text()).toContain(title);
            wrapper.unmount();
        },
    );

    it('links heading matches to their anchor and displays the matched heading', async () => {
        const wrapper = mount(DocsSearch, {
            props: { searchIndex: pages },
            global,
        });
        await wrapper.get('input').setValue('check-in');
        expect(wrapper.get('li a').attributes('href')).toBe(
            '/docs/tracking-progress#details',
        );
        expect(wrapper.get('li p').text()).toBe('Record a check-in');
        await wrapper.get('input').setValue('calendar');
        expect(wrapper.get('li a').attributes('href')).toBe(
            '/docs/tracking-progress',
        );
        wrapper.unmount();
    });

    it('shows the empty state and clears the query', async () => {
        const wrapper = mount(DocsSearch, {
            props: { searchIndex: pages },
            global,
        });
        await wrapper.get('input').setValue('missing topic');
        expect(wrapper.text()).toContain('public.docs.no_results');
        expect(wrapper.find('li').exists()).toBe(false);
        await wrapper.get('button').trigger('click');
        expect((wrapper.get('input').element as HTMLInputElement).value).toBe(
            '',
        );
        expect(wrapper.text()).not.toContain('public.docs.no_results');
        wrapper.unmount();
    });
});

describe('DocsArticle', () => {
    it('copies only the code and reports success', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        mockClipboard(writeText);
        const wrapper = mount(DocsArticle, {
            props: { article, previous: null, next: pages[1] },
            global,
        });
        await flushPromises();
        expect(wrapper.findAll('.public-code-block button')).toHaveLength(1);
        await wrapper.get('.public-code-block button').trigger('click');
        await flushPromises();
        expect(writeText).toHaveBeenCalledWith('php artisan serve\n');
        expect(wrapper.get('.public-code-block button').text()).toBe(
            'public.docs.copied',
        );
        wrapper.unmount();
    });

    it('reports clipboard failures and enhances replacement article HTML once', async () => {
        mockClipboard(vi.fn().mockRejectedValue(new Error('Denied')));
        const wrapper = mount(DocsArticle, {
            props: { article, previous: null, next: null },
            global,
        });
        await flushPromises();
        await wrapper.get('.public-code-block button').trigger('click');
        await flushPromises();
        expect(wrapper.get('.public-code-block button').text()).toBe(
            'public.docs.copy_failed',
        );
        await wrapper.setProps({
            article: {
                ...article,
                html: '<pre><code>npm run build</code></pre>',
            },
        });
        await flushPromises();
        expect(wrapper.findAll('.public-code-block button')).toHaveLength(1);
        expect(wrapper.get('.public-code-block button').text()).toBe(
            'public.docs.copy',
        );
        wrapper.unmount();
    });
});

describe('ArticleFooterLinks', () => {
    it('points Markdown and edit links to the current source file', () => {
        const wrapper = mount(ArticleFooterLinks, {
            props: { rawUrl: article.rawUrl, editUrl: article.editUrl },
        });
        const links = wrapper.findAll('a');
        expect(links.map((link) => link.attributes('href'))).toEqual([
            article.rawUrl,
            article.editUrl,
        ]);
        for (const link of links) {
            expect(link.attributes('target')).toBe('_blank');
            expect(link.attributes('rel')).toBe('noopener');
            expect(link.get('.sr-only').text()).toBe('public.opens_in_new_tab');
        }
        expect(links[0].text()).toContain('public.docs.view_markdown');
        expect(links[1].text()).toContain('public.docs.edit_page');
        wrapper.unmount();
    });
});

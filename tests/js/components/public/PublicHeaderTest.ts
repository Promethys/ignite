import PublicHeader from '@/components/public/PublicHeader.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    page: {
        url: '/features',
        props: {
            auth: { user: null as null | { id: number; name: string } },
            githubUrl: 'https://github.com/example/ignite',
        },
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => mocks.page,
    Link: {
        props: ['href'],
        template:
            '<a :href="typeof href === \'string\' ? href : href.url"><slot /></a>',
    },
}));

vi.mock('@/components/LanguageSwitcher.vue', () => ({
    default: { template: '<button>Language</button>' },
}));

const mountHeader = () =>
    mount(PublicHeader, {
        global: {
            mocks: { $page: mocks.page },
            stubs: {
                Link: false,
                SheetContent: { template: '<div><slot /></div>' },
            },
        },
    });

describe('PublicHeader', () => {
    beforeEach(() => {
        mocks.page.props.auth.user = null;
        mocks.page.url = '/features';
    });

    it('offers guests login and registration alongside the public links', () => {
        const wrapper = mountHeader();

        for (const href of ['/features', '/self-host', '/login', '/register']) {
            expect(wrapper.find(`a[href="${href}"]`).exists()).toBe(true);
        }
        expect(wrapper.find('a[href="/dashboard"]').exists()).toBe(false);
        expect(wrapper.find('a[href="/docs"]').exists()).toBe(true);
        expect(
            wrapper.find(`a[href="${mocks.page.props.githubUrl}"]`).exists(),
        ).toBe(true);
        expect(wrapper.text()).toContain('Language');
    });

    it('offers authenticated users the dashboard', () => {
        mocks.page.props.auth.user = { id: 1, name: 'Jane' };
        const wrapper = mountHeader();

        expect(wrapper.find('a[href="/dashboard"]').exists()).toBe(true);
        expect(wrapper.find('a[href="/login"]').exists()).toBe(false);
        expect(wrapper.find('a[href="/register"]').exists()).toBe(false);
    });

    it.each(['/features#progress', '/docs/goal-types'])(
        'marks the current public page at %s',
        (url) => {
            mocks.page.url = url;
            const wrapper = mountHeader();

            expect(
                wrapper
                    .get(
                        `a[href="${url.startsWith('/docs') ? '/docs' : '/features'}"]`,
                    )
                    .attributes('aria-current'),
            ).toBe('page');
            expect(
                wrapper.get('a[href="/self-host"]').attributes('aria-current'),
            ).toBeUndefined();
        },
    );

    it('marks external websites and announces their new tab', () => {
        const wrapper = mountHeader();

        const externalLinks = wrapper.findAll('a[href^="https://"]');

        expect(externalLinks).toHaveLength(2);
        for (const link of externalLinks) {
            expect(link.attributes('target')).toBe('_blank');
            expect(link.attributes('rel')).toBe('noopener');
            expect(
                link.get('svg.lucide-move-up-right').attributes('aria-hidden'),
            ).toBe('true');
            expect(link.get('svg').classes()).toContain('size-3');
            expect(link.get('.sr-only').text()).toBe('public.opens_in_new_tab');
        }

        for (const link of wrapper.findAll('a[href^="/"]')) {
            expect(link.find('svg.lucide-move-up-right').exists()).toBe(false);
            expect(link.attributes('target')).toBeUndefined();
            expect(link.find('.sr-only').exists()).toBe(false);
        }
    });
});

import PublicFooter from '@/components/public/PublicFooter.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const githubUrl = 'https://github.com/example/ignite';
const supportEmail = 'help@example.test';

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { githubUrl, supportEmail } }),
    Link: {
        props: ['href'],
        template:
            '<a :href="typeof href === \'string\' ? href : href.url"><slot /></a>',
    },
}));

describe('PublicFooter', () => {
    it('links the product, project and legal destinations', () => {
        const wrapper = mount(PublicFooter, {
            global: { stubs: { Link: false } },
        });
        const hrefs = wrapper
            .findAll('a')
            .map((link) => link.attributes('href'));

        expect(hrefs).toEqual(
            expect.arrayContaining([
                '/features',
                '/self-host',
                '/privacy',
                '/terms',
                '/docs',
                'https://ignite-docs.promethys.dev/',
                githubUrl,
                `${githubUrl}/releases`,
                `${githubUrl}/issues`,
                `mailto:${supportEmail}`,
            ]),
        );
        expect(wrapper.text()).toContain('Ignite');
        expect(wrapper.text()).toContain('©');
    });

    it('marks external websites and announces their new tab', () => {
        const wrapper = mount(PublicFooter, {
            global: { stubs: { Link: false } },
        });

        const externalLinks = wrapper.findAll('a[href^="https://"]');

        expect(externalLinks).toHaveLength(4);
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

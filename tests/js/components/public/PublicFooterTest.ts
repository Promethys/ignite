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
                'https://ignite-docs.promethys.dev/guide/',
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
});

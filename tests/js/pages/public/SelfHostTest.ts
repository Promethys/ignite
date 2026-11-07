import ClosingCta from '@/components/public/ClosingCta.vue';
import SelfHost from '@/pages/public/SelfHost.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        usePage: () => ({
            props: {
                githubUrl: 'https://github.com/Promethys/ignite',
                auth: { user: null },
            },
        }),
    };
});
vi.mock('laravel-vue-i18n', () => ({ trans: (key: string) => key }));

const mountPage = () =>
    mount(SelfHost, {
        global: {
            stubs: {
                PublicShell: {
                    template: '<div class="public-surface"><slot /></div>',
                },
                ClosingCta: true,
            },
        },
    });

describe('SelfHost', () => {
    it('renders the intro, licence, stack, setup, contributions and shared closing CTA', () => {
        const wrapper = mountPage();
        expect(wrapper.get('h1').text()).toBe('public.self_host.title');
        expect(
            wrapper
                .findAll('section')
                .map((section) => section.attributes('aria-labelledby')),
        ).toEqual(['licence', 'stack', 'run', 'contribute']);
        expect(
            wrapper.findAll('section[aria-labelledby="licence"] li'),
        ).toHaveLength(3);
        expect(wrapper.findComponent(ClosingCta).exists()).toBe(true);
        wrapper.unmount();
    });

    it('links to the repository, developer docs, licence, guide and contribution resources in new tabs', () => {
        const wrapper = mountPage();
        const links = wrapper.findAll('a');
        expect(links.map((link) => link.attributes('href'))).toEqual([
            'https://github.com/Promethys/ignite',
            'https://ignite-docs.promethys.dev/',
            'https://github.com/Promethys/ignite/blob/main/LICENSE',
            'https://ignite-docs.promethys.dev/self-hosting',
            'https://github.com/Promethys/ignite/issues',
            'https://github.com/Promethys/ignite/issues',
            'https://github.com/Promethys/ignite/blob/main/CONTRIBUTING.md',
        ]);
        for (const link of links) {
            expect(link.attributes('target')).toBe('_blank');
            expect(link.attributes('rel')).toBe('noopener');
            expect(link.get('.sr-only').text()).toBe('public.opens_in_new_tab');
            expect(link.find('svg').exists()).toBe(true);
        }
        wrapper.unmount();
    });

    it('renders all six ordered steps and places the backup warning with the master key', () => {
        const wrapper = mountPage();
        const steps = wrapper.findAll('ol > li');
        expect(steps).toHaveLength(6);
        steps.forEach((step, index) => {
            expect(step.get('h3').text()).toContain(
                `public.self_host.steps.${index + 1}.title`,
            );
            expect(step.get('code').text()).toBe(
                `public.self_host.steps.${index + 1}.code`,
            );
        });
        const warning = steps[3].get('aside.docs-callout-warning');
        expect(warning.attributes('aria-labelledby')).toBe(
            'master-key-warning',
        );
        expect(warning.get('#master-key-warning').text()).toBe(
            'public.self_host.warning_title',
        );
        expect(warning.text()).toContain('public.self_host.warning');
        expect(wrapper.findAll('aside')).toHaveLength(1);
        wrapper.unmount();
    });
});

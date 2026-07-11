import ErrorPage from '@/pages/ErrorPage.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return { ...actual, Head: { name: 'Head', render: () => null } };
});

const stubs = {
    AppLogoIcon: { template: '<img alt="Ignite" />' },
    Link: { props: ['href'], template: '<a :href="href"><slot /></a>' },
    Button: { template: '<button><slot /></button>' },
};

const mountPage = (status: number) =>
    mount(ErrorPage, { props: { status }, global: { stubs } });

describe('ErrorPage', () => {
    it('renders the 404 title for status 404', () => {
        const wrapper = mountPage(404);

        expect(wrapper.text()).toContain('errors.404.title');
    });

    it('renders the 500 title for status 500', () => {
        const wrapper = mountPage(500);

        expect(wrapper.text()).toContain('errors.500.title');
    });

    it('falls back to the generic title for an unlisted status', () => {
        const wrapper = mountPage(418);

        expect(wrapper.text()).toContain('errors.generic.title');
        expect(wrapper.text()).not.toContain('errors.418');
    });

    it('renders the home action link', () => {
        const wrapper = mountPage(404);

        expect(wrapper.text()).toContain('errors.actions.home');
    });
});

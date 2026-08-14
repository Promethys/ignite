import ConnectedAccounts from '@/pages/settings/ConnectedAccounts.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const { routerDelete, pageProps } = vi.hoisted(() => ({
    routerDelete: vi.fn(),
    pageProps: {
        ssoProviders: ['google', 'github'],
        auth: { user: { has_password: true } },
    },
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        usePage: () => ({ props: pageProps }),
        router: { delete: routerDelete },
    };
});

vi.mock('@/routes/connected-accounts', () => ({
    index: () => ({ url: '/settings/connected-accounts' }),
}));

vi.mock('@/routes/password', () => ({
    edit: () => ({ url: '/settings/password' }),
}));

vi.mock('@/routes/sso', () => ({
    redirect: ({ provider }: { provider: string }) => ({
        url: `/auth/${provider}/redirect`,
    }),
    logout: ({ provider }: { provider: string }) => ({
        url: `/auth/${provider}/logout`,
    }),
}));

const stubs = {
    AppLayout: { template: '<div><slot /></div>' },
    SettingsLayout: { template: '<div><slot /></div>' },
    HeadingSmall: { template: '<div />' },
    Google: true,
    Github: true,
};

interface ConnectedProvider {
    id: number;
    provider: string;
    provider_email: string | null;
    created_at: string;
}

function renderPage(
    connectedProviders: ConnectedProvider[] = [],
    hasPassword = true,
) {
    pageProps.auth.user.has_password = hasPassword;

    return mount(ConnectedAccounts, {
        props: { connectedProviders },
        global: { stubs },
    });
}

function google(overrides: Partial<ConnectedProvider> = {}) {
    return {
        id: 1,
        provider: 'google',
        provider_email: 'jane@example.com',
        created_at: '2026-08-01T00:00:00Z',
        ...overrides,
    };
}

describe('settings/ConnectedAccounts', () => {
    beforeEach(() => {
        routerDelete.mockClear();
    });

    it('lists every supported provider, connected or not', () => {
        const wrapper = renderPage([google()]);

        expect(wrapper.text()).toContain('Google');
        expect(wrapper.text()).toContain('GitHub');
    });

    it('names GitHub with its real capitalisation', () => {
        const wrapper = renderPage();

        expect(wrapper.text()).toContain('GitHub');
        expect(wrapper.text()).not.toContain('Github');
    });

    it('shows the provider email so the user can tell which account is linked', () => {
        const wrapper = renderPage([google()]);

        expect(wrapper.text()).toContain('jane@example.com');
    });

    it('offers a connect link for a provider that is not linked', () => {
        const wrapper = renderPage([]);

        const links = wrapper.findAll('a').map((a) => a.attributes('href'));

        expect(links).toContain('/auth/google/redirect');
        expect(links).toContain('/auth/github/redirect');
    });

    it('sends the connect link as a real anchor, not an inertia visit', () => {
        const wrapper = renderPage([]);

        const connect = wrapper
            .findAll('a')
            .find((a) => a.attributes('href') === '/auth/google/redirect');

        expect(connect).toBeDefined();
        expect(connect!.element.tagName).toBe('A');
    });

    it('disconnects through the inertia router so the page refreshes', async () => {
        const wrapper = renderPage([google()]);

        await wrapper
            .findAll('button')
            .find((b) =>
                b.text().includes('settings.connected_accounts.disconnect'),
            )!
            .trigger('click');

        expect(routerDelete).toHaveBeenCalledWith(
            '/auth/google/logout',
            expect.anything(),
        );
    });

    it('hides disconnect when the provider is the only way in', () => {
        const wrapper = renderPage([google()], false);

        expect(wrapper.text()).not.toContain(
            'settings.connected_accounts.disconnect',
        );
        expect(wrapper.text()).toContain(
            'settings.connected_accounts.last_credential',
        );
    });

    it('points at the password page instead of a dead disconnect button', () => {
        const wrapper = renderPage([google()], false);

        const links = wrapper.findAll('a').map((a) => a.attributes('href'));

        expect(links).toContain('/settings/password');
    });

    it('never offers connect on an already connected provider', () => {
        const wrapper = renderPage([google()], false);

        const links = wrapper.findAll('a').map((a) => a.attributes('href'));

        expect(links).not.toContain('/auth/google/redirect');
    });

    it('allows disconnect without a password when a second provider remains', () => {
        const wrapper = renderPage(
            [google(), google({ id: 2, provider: 'github' })],
            false,
        );

        expect(wrapper.text()).toContain(
            'settings.connected_accounts.disconnect',
        );
        expect(wrapper.text()).not.toContain(
            'settings.connected_accounts.last_credential',
        );
    });
});

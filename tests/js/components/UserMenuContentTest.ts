import UserMenuContent from '@/components/UserMenuContent.vue';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const supportEmail = 'help@example.test';
const githubUrl = 'https://github.com/example/repo';

const mocks = vi.hoisted(() => ({
    flushAll: vi.fn(),
    fbLogout: vi.fn().mockResolvedValue(undefined),
    fbSetLanguage: vi.fn().mockResolvedValue(undefined),
}));

vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    router: { flushAll: mocks.flushAll },
    usePage: () => ({ props: { supportEmail, githubUrl } }),
}));

vi.mock('@formbricks/js', () => ({
    default: { logout: mocks.fbLogout, setLanguage: mocks.fbSetLanguage },
}));

vi.mock('@/components/ui/dropdown-menu', () => ({
    DropdownMenuGroup: { template: '<div><slot /></div>' },
    DropdownMenuItem: {
        props: ['asChild'],
        template: '<div><slot /></div>',
    },
    DropdownMenuLabel: { template: '<div><slot /></div>' },
    DropdownMenuSeparator: { template: '<hr />' },
}));

vi.mock('@/components/UserInfo.vue', () => ({
    default: { template: '<div>user-info</div>' },
}));

vi.mock('@/routes', () => ({
    logout: () => '/logout',
}));

vi.mock('@/routes/profile', () => ({
    edit: () => '/profile/edit',
}));

const en: Record<string, string> = {
    'common.actions.settings': 'Settings',
    'common.actions.log_out': 'Log out',
    'common.support.report_issue': 'Report an issue',
    'common.support.email_subject': 'Ignite feedback',
    'common.support.send_feedback': 'Send feedback',
    'common.nav.repository': 'Repository',
};

const user = {
    id: 1,
    name: 'Jane',
    email: 'jane@example.test',
};

const mountMenu = () =>
    mount(UserMenuContent, {
        props: { user },
        global: { mocks: { $t: (key: string) => en[key] ?? key } },
    });

const findMailto = (wrapper: ReturnType<typeof mountMenu>) =>
    wrapper
        .findAll('a')
        .find((a) =>
            (a.attributes('href') ?? '').startsWith(`mailto:${supportEmail}`),
        );

describe('UserMenuContent', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.stubEnv('VITE_FORMBRICKS_WORKSPACE_ID', 'ws_test');
    });

    afterEach(() => {
        vi.unstubAllEnvs();
    });

    it('renders a mailto link to the shared support address', () => {
        expect(findMailto(mountMenu())).toBeTruthy();
    });

    it('includes a url-encoded subject in the mailto href', () => {
        expect(findMailto(mountMenu())?.attributes('href')).toContain(
            `subject=${encodeURIComponent('Ignite feedback')}`,
        );
    });

    it('opens the mailto link in a new tab', () => {
        const mailto = findMailto(mountMenu());

        expect(mailto?.attributes('target')).toBe('_blank');
        expect(mailto?.attributes('rel')).toBe('noopener');
    });

    it('renders a link to the shared source repository', () => {
        const repoLink = mountMenu()
            .findAll('a')
            .find((a) => a.attributes('href') === githubUrl);

        expect(repoLink).toBeTruthy();
        expect(repoLink?.text()).toContain('Repository');
    });

    it('renders the feedback trigger the survey listens for', () => {
        const trigger = mountMenu().get('#send-feedback');

        expect(trigger.text()).toContain('Send feedback');
    });

    it('logs out of Formbricks and flushes Inertia state on logout', async () => {
        const wrapper = mountMenu();

        await wrapper.get('[data-test="logout-button"]').trigger('click');

        expect(mocks.fbLogout).toHaveBeenCalledTimes(1);
        expect(mocks.flushAll).toHaveBeenCalledTimes(1);
    });
});

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import UserMenuContent from '@/components/UserMenuContent.vue';

const supportEmail = 'help@example.test';

vi.mock('@inertiajs/vue3', () => ({
    Link: { template: '<a><slot /></a>' },
    router: { flushAll: vi.fn() },
    usePage: () => ({ props: { supportEmail } }),
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
};

const user = {
    id: 1,
    name: 'Jane',
    email: 'jane@example.test',
};

describe('UserMenuContent', () => {
    it('renders a mailto link to the shared support address', () => {
        const wrapper = mount(UserMenuContent, {
            props: { user },
            global: {
                mocks: {
                    $t: (key: string) => en[key] ?? key,
                },
            },
        });

        const links = wrapper.findAll('a');
        const mailto = links.find((a) =>
            (a.attributes('href') ?? '').startsWith(`mailto:${supportEmail}`),
        );

        expect(mailto).toBeTruthy();
    });

    it('includes a url-encoded subject in the mailto href', () => {
        const wrapper = mount(UserMenuContent, {
            props: { user },
            global: {
                mocks: {
                    $t: (key: string) => en[key] ?? key,
                },
            },
        });

        const links = wrapper.findAll('a');
        const mailto = links.find((a) =>
            (a.attributes('href') ?? '').startsWith(`mailto:${supportEmail}`),
        );

        expect(mailto?.attributes('href')).toContain(
            `subject=${encodeURIComponent('Ignite feedback')}`,
        );
    });
});

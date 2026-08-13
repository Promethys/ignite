import Password from '@/pages/settings/Password.vue';
import type { SsoProvider } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { h } from 'vue';

// The Inertia <Head> component needs the app's head manager and <Form> needs a
// live router, neither of which exists in unit tests; stub only those exports
// and keep the rest of the module real.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();

    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        Form: {
            name: 'Form',
            setup(_props: unknown, { slots }: { slots: Record<string, any> }) {
                return () =>
                    h('form', {}, [
                        slots.default?.({
                            errors: {},
                            processing: false,
                            recentlySuccessful: false,
                        }),
                    ]);
            },
        },
    };
});

vi.mock('@/actions/App/Http/Controllers/Settings/PasswordController', () => ({
    default: { update: { form: () => ({}) } },
}));

vi.mock('@/routes/password', () => ({
    edit: () => ({ url: '/settings/password' }),
}));

const stubs = {
    AppLayout: { template: '<div><slot /></div>' },
    SettingsLayout: { template: '<div><slot /></div>' },
    HeadingSmall: {
        props: ['title', 'description'],
        template: '<div><h2>{{ title }}</h2><p>{{ description }}</p></div>',
    },
    PasswordInput: {
        props: ['id', 'name'],
        template: '<input :id="id" :name="name" />',
    },
    InputError: true,
    InputRequiredIndicator: true,
};

function renderPage(props: {
    hasPassword: boolean;
    socialAccounts: SsoProvider[];
}) {
    return mount(Password, { props, global: { stubs } });
}

describe('settings/Password', () => {
    it('asks for the current password when the account already has one', () => {
        const wrapper = renderPage({ hasPassword: true, socialAccounts: [] });

        expect(wrapper.find('#current_password').exists()).toBe(true);
    });

    it('omits the current password field when the account has none', () => {
        const wrapper = renderPage({
            hasPassword: false,
            socialAccounts: ['google'],
        });

        expect(wrapper.find('#current_password').exists()).toBe(false);
        expect(wrapper.find('#password').exists()).toBe(true);
        expect(wrapper.find('#password_confirmation').exists()).toBe(true);
    });

    it('switches the heading and the button from updating to creating', () => {
        const updating = renderPage({ hasPassword: true, socialAccounts: [] });
        const creating = renderPage({
            hasPassword: false,
            socialAccounts: ['google'],
        });

        expect(updating.text()).toContain('settings.password.title');
        expect(updating.text()).toContain('settings.password.save');

        expect(creating.text()).toContain('settings.password.create_title');
        expect(creating.text()).toContain('settings.password.create_save');
    });

    it('says nothing about linked accounts when there are none', () => {
        const wrapper = renderPage({ hasPassword: true, socialAccounts: [] });

        expect(wrapper.text()).not.toContain('settings.password.linked_title');
    });

    it('names the providers with their real capitalisation', () => {
        const wrapper = renderPage({
            hasPassword: false,
            socialAccounts: ['google', 'github'],
        });

        expect(wrapper.text()).toContain('Google, GitHub');
    });

    it('tells a linked user without a password they are gaining a second way in', () => {
        const wrapper = renderPage({
            hasPassword: false,
            socialAccounts: ['google'],
        });

        expect(wrapper.text()).toContain(
            'settings.password.linked_without_password',
        );
        expect(wrapper.text()).not.toContain(
            'settings.password.linked_with_password',
        );
    });

    it('tells a linked user who already has a password that both routes work', () => {
        const wrapper = renderPage({
            hasPassword: true,
            socialAccounts: ['google'],
        });

        expect(wrapper.text()).toContain(
            'settings.password.linked_with_password',
        );
        expect(wrapper.text()).not.toContain(
            'settings.password.linked_without_password',
        );
    });
});

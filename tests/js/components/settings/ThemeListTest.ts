import ThemeList from '@/components/settings/ThemeList.vue';
import type { Theme } from '@/types/models';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    routerPatch: vi.fn(),
    routerDelete: vi.fn(),
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        router: { patch: mocks.routerPatch, delete: mocks.routerDelete },
    };
});

vi.mock('@/routes/themes', () => ({
    activate: (id: number) => ({ url: `/settings/themes/${id}/activate` }),
    destroy: (id: number) => ({ url: `/settings/themes/${id}` }),
    useDefault: () => ({ url: '/settings/themes/default' }),
}));

const ocean: Theme = {
    id: 1,
    name: 'Ocean',
    light_color: '#2563eb',
    dark_color: null,
    is_active: false,
};

const forest: Theme = {
    id: 2,
    name: 'Forest',
    light_color: '#15803d',
    dark_color: '#86efac',
    is_active: true,
};

function mountList(themes: Theme[], themeLimit = 5) {
    return mount(ThemeList, {
        props: { themes, themeLimit },
        global: {
            stubs: { ThemeFormDialog: true, Dialog: true },
        },
    });
}

describe('ThemeList', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('marks the built-in theme as in use when no theme is selected', () => {
        const wrapper = mountList([ocean]);

        const defaultRow = wrapper.find('[data-test="theme-default"]');

        expect(defaultRow.text()).toContain('settings.themes.in_use');
        expect(defaultRow.find('button').exists()).toBe(false);
        expect(wrapper.find('[data-test="theme-1"]').text()).not.toContain(
            'settings.themes.in_use',
        );
    });

    it('marks the selected theme and offers the built-in one back', async () => {
        const wrapper = mountList([ocean, forest]);

        expect(wrapper.find('[data-test="theme-2"]').text()).toContain(
            'settings.themes.in_use',
        );

        await wrapper
            .find('[data-test="theme-default"] button')
            .trigger('click');

        expect(mocks.routerPatch).toHaveBeenCalledWith(
            '/settings/themes/default',
            {},
            { preserveScroll: true },
        );
    });

    it('selects a theme that is not in use', async () => {
        const wrapper = mountList([ocean, forest]);

        const useButton = wrapper
            .findAll('[data-test="theme-1"] button')
            .find((button) => button.text() === 'settings.themes.use');

        await useButton!.trigger('click');

        expect(mocks.routerPatch).toHaveBeenCalledWith(
            '/settings/themes/1/activate',
            {},
            { preserveScroll: true },
        );
    });

    it('shows both colours only when the dark one differs', () => {
        const wrapper = mountList([ocean, forest]);

        expect(wrapper.find('[data-test="theme-1"]').text()).toContain(
            '#2563eb',
        );
        expect(wrapper.find('[data-test="theme-2"]').text()).toContain(
            '#15803d / #86efac',
        );
    });

    it('blocks new themes once the limit is reached', () => {
        const atLimit = mountList([ocean, forest], 2);
        const belowLimit = mountList([ocean], 2);

        expect(
            atLimit.find('[data-test="theme-new"]').attributes('disabled'),
        ).toBeDefined();
        expect(
            belowLimit.find('[data-test="theme-new"]').attributes('disabled'),
        ).toBeUndefined();
    });
});

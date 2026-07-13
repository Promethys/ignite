import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    loadLanguageAsync: vi.fn().mockResolvedValue(undefined),
    momentLocale: vi.fn(),
    usePage: vi.fn(),
    routerReload: vi.fn(),
    routerPatch: vi.fn(),
    fbSetLanguage: vi.fn().mockResolvedValue(undefined),
}));

vi.mock('laravel-vue-i18n', () => ({
    loadLanguageAsync: mocks.loadLanguageAsync,
    i18nVue: { install: () => {} },
}));

vi.mock('moment', () => ({
    default: { locale: mocks.momentLocale },
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: mocks.usePage,
    router: { reload: mocks.routerReload, patch: mocks.routerPatch },
}));

vi.mock('@formbricks/js', () => ({
    default: { setLanguage: mocks.fbSetLanguage },
}));

// Stub the Reka dropdown wrappers so menu items render inline (no teleport,
// always open) and each locale is a clickable button we can target by code.
// The real dropdown behaviour is Reka's concern; this test covers the
// selection -> cookie/switchTo/reload logic the component owns.
vi.mock('@/components/ui/dropdown-menu', () => ({
    DropdownMenu: { template: '<div><slot /></div>' },
    DropdownMenuTrigger: { template: '<div><slot /></div>' },
    DropdownMenuContent: { template: '<div><slot /></div>' },
    DropdownMenuRadioGroup: {
        props: ['modelValue'],
        template: '<div><slot /></div>',
    },
    DropdownMenuRadioItem: {
        props: ['value'],
        emits: ['select'],
        template:
            '<button type="button" :data-locale="value" @click="$emit(\'select\')"><slot /></button>',
    },
}));

import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

describe('LanguageSwitcher', () => {
    let cookieSpy: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        vi.clearAllMocks();
        vi.stubEnv('VITE_FORMBRICKS_WORKSPACE_ID', 'ws_test');
        mocks.usePage.mockReturnValue({
            props: {
                locale: 'en',
                supportedLocales: { en: 'English', fr: 'Français' },
                auth: { user: null },
            },
        });
        cookieSpy = vi.spyOn(document, 'cookie', 'set');
    });

    afterEach(() => {
        vi.unstubAllEnvs();
    });

    it('renders one menu item per supported locale', () => {
        const wrapper = mount(LanguageSwitcher);

        expect(wrapper.findAll('[data-locale]')).toHaveLength(2);
    });

    it('sets the locale cookie and reloads when selecting fr', async () => {
        const wrapper = mount(LanguageSwitcher);

        await wrapper.get('[data-locale="fr"]').trigger('click');
        await flushPromises();

        expect(cookieSpy).toHaveBeenCalledWith(
            expect.stringContaining('locale=fr'),
        );
        expect(mocks.routerReload).toHaveBeenCalledTimes(1);
    });

    it('applies the locale through the i18n loader and moment', async () => {
        const wrapper = mount(LanguageSwitcher);

        await wrapper.get('[data-locale="fr"]').trigger('click');
        await flushPromises();

        expect(mocks.loadLanguageAsync).toHaveBeenCalledWith('fr');
        expect(mocks.momentLocale).toHaveBeenCalledWith('fr');
    });

    it('updates the feedback survey language when switching', async () => {
        const wrapper = mount(LanguageSwitcher);

        await wrapper.get('[data-locale="fr"]').trigger('click');
        await flushPromises();

        expect(mocks.fbSetLanguage).toHaveBeenCalledWith('fr');
    });

    it('does nothing when selecting the already active locale', async () => {
        const wrapper = mount(LanguageSwitcher);

        await wrapper.get('[data-locale="en"]').trigger('click');
        await flushPromises();

        expect(cookieSpy).not.toHaveBeenCalled();
        expect(mocks.routerReload).not.toHaveBeenCalled();
        expect(mocks.fbSetLanguage).not.toHaveBeenCalled();
    });

    it('patches the locale route and skips the cookie when authenticated', async () => {
        mocks.usePage.mockReturnValue({
            props: {
                locale: 'en',
                supportedLocales: { en: 'English', fr: 'Français' },
                auth: { user: { id: 1 } },
            },
        });

        const wrapper = mount(LanguageSwitcher);

        await wrapper.get('[data-locale="fr"]').trigger('click');
        await flushPromises();

        expect(mocks.routerPatch).toHaveBeenCalledWith(expect.any(String), {
            locale: 'fr',
        });
        expect(cookieSpy).not.toHaveBeenCalled();
        expect(mocks.routerReload).not.toHaveBeenCalled();
    });
});

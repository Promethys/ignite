import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    loadLanguageAsync: vi.fn().mockResolvedValue(undefined),
    momentLocale: vi.fn(),
    usePage: vi.fn(),
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
}));

import { useLocale } from '@/composables/useLocale';

function mockPage(locale: string) {
    mocks.usePage.mockReturnValue({
        props: {
            locale,
            supportedLocales: { en: 'English', fr: 'Français' },
        },
    });
}

describe('useLocale', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockPage('en');
    });

    it('initializes current and supported from the shared page props', () => {
        mockPage('fr');

        const { current, supported } = useLocale();

        expect(current.value).toBe('fr');
        expect(supported).toEqual({ en: 'English', fr: 'Français' });
    });

    it('switchTo applies the locale through the i18n loader and moment', async () => {
        const { current, switchTo } = useLocale();

        await switchTo('fr');

        expect(current.value).toBe('fr');
        expect(mocks.loadLanguageAsync).toHaveBeenCalledWith('fr');
        expect(mocks.momentLocale).toHaveBeenCalledWith('fr');
    });

    it('switchTo defaults to english when the code is unsupported', async () => {
        const { switchTo } = useLocale();

        await switchTo('de');

        expect(mocks.loadLanguageAsync).not.toHaveBeenCalled();
        expect(mocks.momentLocale).not.toHaveBeenCalled();
    });
});

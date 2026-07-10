import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    loadLanguageAsync: vi.fn().mockResolvedValue(undefined),
    momentLocale: vi.fn(),
    usePage: vi.fn(),
    routerPatch: vi.fn(),
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
    router: { patch: mocks.routerPatch },
}));

vi.mock('@/actions/App/Http/Controllers/Settings/LocaleController', () => ({
    default: {
        update: {
            url: () => '/settings/locale',
        },
    },
}));

// Stub the Reka Select wrappers so items render inline (no teleport) and
// each option is a clickable button targetable by its locale code. A
// provide/inject bridge lets SelectItem communicate the selection back to
// the Select stub, mirroring Reka's own context mechanism.
vi.mock('@/components/ui/select', async () => {
    const { provide, inject } = await import('vue');
    const key = Symbol('selectEmit');

    return {
        Select: {
            props: ['modelValue'],
            emits: ['update:modelValue'],
            setup(_props: any, { emit, slots }: any) {
                provide(key, emit);
                return () => slots.default?.();
            },
        },
        SelectTrigger: { template: '<div><slot /></div>' },
        SelectValue: { template: '<span><slot /></span>' },
        SelectContent: { template: '<div><slot /></div>' },
        SelectItem: {
            props: ['value'],
            setup(props: any) {
                const emit = inject(key) as ((event: string, ...args: unknown[]) => void) | undefined;
                return { select: () => emit?.('update:modelValue', props.value) };
            },
            template:
                '<button type="button" :data-locale="value" @click="select"><slot /></button>',
        },
    };
});

import LocaleSelect from '@/components/LocaleSelect.vue';

describe('LocaleSelect', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mocks.usePage.mockReturnValue({
            props: {
                locale: 'en',
                supportedLocales: { en: 'English', fr: 'Fran\u00e7ais' },
            },
        });
    });

    it('renders one option per supported locale', () => {
        const wrapper = mount(LocaleSelect);

        expect(wrapper.findAll('[data-locale]')).toHaveLength(2);
    });

    it('patches the locale route with the chosen code', async () => {
        const wrapper = mount(LocaleSelect);

        await wrapper.get('[data-locale="fr"]').trigger('click');
        await flushPromises();

        expect(mocks.routerPatch).toHaveBeenCalledWith('/settings/locale', {
            locale: 'fr',
        });
    });

    it('applies the locale through the i18n loader and moment before patching', async () => {
        const wrapper = mount(LocaleSelect);

        await wrapper.get('[data-locale="fr"]').trigger('click');
        await flushPromises();

        expect(mocks.loadLanguageAsync).toHaveBeenCalledWith('fr');
        expect(mocks.momentLocale).toHaveBeenCalledWith('fr');
    });

    it('does nothing when selecting the already active locale', async () => {
        const wrapper = mount(LocaleSelect);

        await wrapper.get('[data-locale="en"]').trigger('click');
        await flushPromises();

        expect(mocks.routerPatch).not.toHaveBeenCalled();
    });
});

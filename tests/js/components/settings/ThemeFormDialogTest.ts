import ThemeFormDialog from '@/components/settings/ThemeFormDialog.vue';
import type { Theme } from '@/types/models';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const mocks = vi.hoisted(() => ({
    submit: vi.fn(),
    validate: vi.fn(),
    withPrecognition: vi.fn(),
    transformed: null as
        | ((data: Record<string, unknown>) => Record<string, unknown>)
        | null,
}));

vi.mock('@inertiajs/vue3', () => ({
    useForm: (data: Record<string, unknown>) => {
        const form: Record<string, unknown> = {
            ...data,
            errors: {} as Record<string, string>,
            processing: false,
            submit: mocks.submit,
            validate: mocks.validate,
            clearErrors: vi.fn(),
            transform: (
                callback: (
                    data: Record<string, unknown>,
                ) => Record<string, unknown>,
            ) => {
                mocks.transformed = callback;
            },
        };
        form.withPrecognition = (action: unknown) => {
            mocks.withPrecognition(action);
            return form;
        };
        return form;
    },
}));

vi.mock('@/routes/themes', () => ({
    store: () => ({ method: 'post', url: '/settings/themes' }),
    update: (id: number) => ({ method: 'put', url: `/settings/themes/${id}` }),
}));

const stubs = {
    Dialog: { template: '<div><slot /></div>' },
    DialogContent: { template: '<div><slot /></div>' },
    DialogHeader: { template: '<div><slot /></div>' },
    DialogTitle: { template: '<h2><slot /></h2>' },
    DialogDescription: { template: '<p><slot /></p>' },
    DialogFooter: { template: '<div><slot /></div>' },
    DialogClose: { template: '<div><slot /></div>' },
    ThemePreview: {
        props: ['lightColor', 'darkColor'],
        template:
            '<div data-test="preview" :data-light="lightColor" :data-dark="darkColor" />',
    },
};

const forest: Theme = {
    id: 2,
    name: 'Forest',
    light_color: '#15803d',
    dark_color: '#86efac',
    is_active: true,
};

function mountDialog(theme: Theme | null) {
    return mount(ThemeFormDialog, {
        props: { theme, open: true },
        global: { stubs },
    });
}

describe('ThemeFormDialog', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('validates against the store route for a new theme', () => {
        const wrapper = mountDialog(null);

        expect(wrapper.text()).toContain('settings.themes.create_title');
        expect(mocks.withPrecognition).toHaveBeenCalledWith({
            method: 'post',
            url: '/settings/themes',
        });
    });

    it('validates against the update route and fills the form for an existing theme', () => {
        const wrapper = mountDialog(forest);

        expect(wrapper.text()).toContain('settings.themes.edit_title');
        expect(mocks.withPrecognition).toHaveBeenCalledWith({
            method: 'put',
            url: '/settings/themes/2',
        });
        expect(
            (wrapper.find('#theme_name').element as HTMLInputElement).value,
        ).toBe('Forest');
        expect(wrapper.find('#theme_dark_color').exists()).toBe(true);
    });

    it('validates each field when it changes', async () => {
        const wrapper = mountDialog(forest);

        await wrapper.find('#theme_name').trigger('change');
        await wrapper.find('#theme_light_color').trigger('change');
        await wrapper.find('#theme_dark_color').trigger('change');

        expect(mocks.validate.mock.calls).toEqual([
            ['name'],
            ['light_color'],
            ['dark_color'],
        ]);
    });

    it('hides the dark colour and previews one colour for both modes by default', () => {
        const wrapper = mountDialog(null);

        const preview = wrapper.find('[data-test="preview"]');

        expect(wrapper.find('#theme_dark_color').exists()).toBe(false);
        expect(preview.attributes('data-dark')).toBe(
            preview.attributes('data-light'),
        );
    });

    it('sends no dark colour unless a different one is asked for', () => {
        mountDialog(null);
        const withoutDark = mocks.transformed!({
            name: 'Ocean',
            light_color: '#2563eb',
            dark_color: '#2563eb',
        });

        mountDialog(forest);
        const withDark = mocks.transformed!({
            name: 'Forest',
            light_color: '#15803d',
            dark_color: '#86efac',
        });

        expect(withoutDark.dark_color).toBeNull();
        expect(withDark.dark_color).toBe('#86efac');
    });

    it('submits to the route it validates against', async () => {
        const wrapper = mountDialog(forest);

        await wrapper.find('form').trigger('submit');

        expect(mocks.submit).toHaveBeenCalledWith(
            { method: 'put', url: '/settings/themes/2' },
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});

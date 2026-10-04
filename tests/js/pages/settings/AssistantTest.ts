import Assistant from '@/pages/settings/Assistant.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

const mocks = vi.hoisted(() => ({
    routerPatch: vi.fn(),
    routerDelete: vi.fn(),
    form: {
        provider: '',
        api_key: '',
        model: '',
        consent: false,
        errors: {} as Record<string, string>,
        processing: false,
        submit: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
    },
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        router: { patch: mocks.routerPatch, delete: mocks.routerDelete },
        useForm: () => mocks.form,
    };
});

vi.mock('@/routes/assistant', () => ({
    index: { url: () => '/settings/assistant' },
    store: () => ({ method: 'post', url: '/settings/assistant' }),
    update: (id: number) => ({
        method: 'put',
        url: `/settings/assistant/${id}`,
    }),
    makeDefault: (id: number) => ({
        method: 'patch',
        url: `/settings/assistant/${id}/default`,
    }),
    destroy: (id: number) => ({
        method: 'delete',
        url: `/settings/assistant/${id}`,
    }),
}));

const passthrough = (names: string[]) =>
    Object.fromEntries(
        names.map((name) => [name, { template: '<div><slot /></div>' }]),
    );

const stubs = {
    ...passthrough([
        'AppLayout',
        'SettingsLayout',
        'HeadingSmall',
        'InputError',
        'ItemMedia',
        'ItemContent',
        'ItemTitle',
        'ItemDescription',
        'ItemActions',
        'Label',
        'DialogClose',
        'DialogContent',
        'DialogDescription',
        'DialogFooter',
        'DialogHeader',
        'DialogTitle',
        'Checkbox',
        'CharacterCounter',
    ]),
    Item: { template: '<section><slot /></section>' },
    Badge: { template: '<span data-testid="badge"><slot /></span>' },
    Button: { template: '<button><slot /></button>' },
    Dialog: {
        props: ['open'],
        template: '<div v-if="open" data-testid="dialog"><slot /></div>',
    },
    Input: { template: '<input />' },
    PasswordInput: {
        props: ['placeholder'],
        template: '<input type="password" :placeholder="placeholder" />',
    },
};

const providers = [
    { value: 'anthropic', label: 'Anthropic' },
    { value: 'openai', label: 'OpenAI' },
    { value: 'gemini', label: 'Gemini' },
];

const anthropicKey = {
    id: 5,
    provider: 'anthropic',
    key_suffix: 'wxyz',
    model: null,
    is_default: true,
};

const openAiKey = {
    id: 6,
    provider: 'openai',
    key_suffix: '1234',
    model: 'gpt-custom',
    is_default: false,
};

const mountPage = (assistantKeys: (typeof anthropicKey)[] = []) =>
    mount(Assistant, {
        props: { providers, assistantKeys },
        global: { stubs },
    });

const row = (wrapper: ReturnType<typeof mountPage>, provider: string) =>
    wrapper.find(`[data-test="assistant-provider-${provider}"]`);

const buttonIn = (
    scope: ReturnType<ReturnType<typeof mountPage>['find']>,
    label: string,
) => scope.findAll('button').find((button) => button.text() === label);

describe('settings/Assistant', () => {
    beforeEach(() => {
        mocks.routerPatch.mockClear();
        mocks.routerDelete.mockClear();
        mocks.form.submit.mockClear();
    });

    it('lists every provider, offering to connect those without a key', () => {
        const wrapper = mountPage();

        expect(wrapper.findAll('section')).toHaveLength(3);
        expect(row(wrapper, 'gemini').text()).toContain(
            'settings.assistant.not_connected',
        );
        expect(
            buttonIn(row(wrapper, 'gemini'), 'settings.assistant.connect'),
        ).toBeDefined();
    });

    it('shows the key suffix and model of a connected provider, never a key', () => {
        const wrapper = mountPage([anthropicKey, openAiKey]);

        expect(row(wrapper, 'anthropic').text()).toContain('wxyz');
        expect(row(wrapper, 'anthropic').text()).toContain(
            'settings.assistant.default_model',
        );
        expect(row(wrapper, 'openai').text()).toContain('gpt-custom');
    });

    it('badges the default key and offers to use the others', () => {
        const wrapper = mountPage([anthropicKey, openAiKey]);

        expect(
            row(wrapper, 'anthropic').find('[data-testid="badge"]').exists(),
        ).toBe(true);
        expect(
            buttonIn(row(wrapper, 'anthropic'), 'settings.assistant.use'),
        ).toBeUndefined();
        expect(
            row(wrapper, 'openai').find('[data-testid="badge"]').exists(),
        ).toBe(false);
        expect(
            buttonIn(row(wrapper, 'openai'), 'settings.assistant.use'),
        ).toBeDefined();
    });

    it('makes a key the default through the make-default route', async () => {
        const wrapper = mountPage([anthropicKey, openAiKey]);

        await buttonIn(
            row(wrapper, 'openai'),
            'settings.assistant.use',
        )!.trigger('click');

        expect(mocks.routerPatch).toHaveBeenCalledWith(
            '/settings/assistant/6/default',
            {},
            expect.anything(),
        );
    });

    it('connects a new provider through the store route with consent asked', async () => {
        const wrapper = mountPage();

        await buttonIn(
            row(wrapper, 'gemini'),
            'settings.assistant.connect',
        )!.trigger('click');
        await nextTick();

        const dialog = wrapper.find('[data-testid="dialog"]');
        expect(dialog.text()).toContain('settings.assistant.connect_title');
        expect(dialog.text()).toContain('settings.assistant.consent');
        expect(mocks.form.provider).toBe('gemini');

        await dialog.find('form').trigger('submit');

        expect(mocks.form.submit).toHaveBeenCalledWith(
            { method: 'post', url: '/settings/assistant' },
            expect.anything(),
        );
    });

    it('edits an existing key through the update route without asking consent again', async () => {
        const wrapper = mountPage([anthropicKey, openAiKey]);

        await buttonIn(
            row(wrapper, 'openai'),
            'settings.assistant.edit',
        )!.trigger('click');
        await nextTick();

        const dialog = wrapper.find('[data-testid="dialog"]');
        expect(dialog.text()).toContain('settings.assistant.edit_title');
        expect(dialog.text()).not.toContain('settings.assistant.consent');
        expect(mocks.form.model).toBe('gpt-custom');

        await dialog.find('form').trigger('submit');

        expect(mocks.form.submit).toHaveBeenCalledWith(
            { method: 'put', url: '/settings/assistant/6' },
            expect.anything(),
        );
    });

    it('removes a key only after confirmation', async () => {
        const wrapper = mountPage([anthropicKey, openAiKey]);

        await buttonIn(
            row(wrapper, 'openai'),
            'settings.assistant.remove',
        )!.trigger('click');
        await nextTick();

        expect(mocks.routerDelete).not.toHaveBeenCalled();

        const dialog = wrapper.find('[data-testid="dialog"]');
        await buttonIn(dialog, 'settings.assistant.remove')!.trigger('click');

        expect(mocks.routerDelete).toHaveBeenCalledWith(
            '/settings/assistant/6',
            expect.anything(),
        );
    });
});

import ApiTokens from '@/pages/settings/ApiTokens.vue';
import type { PersonalAccessToken } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// Shared between the @inertiajs/vue3 mock factory and the tests so assertions
// can read the form state and the recorded delete call. Hoisted so the mock
// factory (which runs early) sees the same references.
const mocks = vi.hoisted(() => ({
    routerDelete: vi.fn(),
    // The object `useForm()` returns. `abilities` is reassigned by the
    // component on toggle, so we read `createForm.abilities` each time rather
    // than caching the array.
    createForm: {
        name: '',
        abilities: ['read'],
        errors: {},
        processing: false,
        post: vi.fn(),
        submit: vi.fn(),
        reset: vi.fn(),
        validate: vi.fn(),
        withPrecognition: vi.fn().mockReturnThis(),
    },
}));

vi.mock('laravel-vue-i18n', () => ({
    trans: (key: string) => key,
}));

vi.mock('moment', () => ({
    default: () => ({ format: () => 'formatted-date' }),
}));

vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        // <Head> needs the app head manager, absent in unit tests.
        Head: { name: 'Head', render: () => null },
        router: { delete: mocks.routerDelete },
        useForm: () => mocks.createForm,
    };
});

const passthrough = (names: string[]) =>
    Object.fromEntries(
        names.map((n) => [n, { template: '<div><slot /></div>' }]),
    );

// A checkbox that forwards model updates, so toggle behavior is testable.
// `id` arrives as a fallthrough attr and lands on the native input.
const Checkbox = {
    props: { modelValue: { type: Boolean, default: false } },
    template:
        '<input type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
};

// A plain <button> that keeps `variant` as an attribute, so the destructive
// confirm button can be targeted inside the footer.
const Button = { template: '<button><slot /></button>' };

// Renders its value as text so the one-time reveal token is visible to
// assertions (the real Input shows it in an editable field).
const Input = {
    props: { modelValue: { type: [String, Number], default: '' } },
    template: '<div>{{ modelValue }}</div>',
};

const stubs = {
    ...passthrough([
        'AppLayout',
        'SettingsLayout',
        'HeadingSmall',
        'InputError',
        'Separator',
        'Badge',
        'Label',
        'Dialog',
        'DialogClose',
        'DialogContent',
        'DialogDescription',
        'DialogHeader',
        'DialogTitle',
        'DialogTrigger',
    ]),
    // Mark the footer so the revoke confirm button can be scoped within it,
    // distinct from the trigger button (both render the same label).
    DialogFooter: {
        template: '<div data-testid="dialog-footer"><slot /></div>',
    },
    Button,
    Input,
    Checkbox,
};

const makeToken = (
    overrides: Partial<PersonalAccessToken>,
): PersonalAccessToken =>
    ({
        id: 1,
        name: 'Claude Desktop',
        abilities: ['read'],
        last_used_at: null,
        created_at: '2026-01-01T00:00:00Z',
        ...overrides,
    }) as PersonalAccessToken;

const mountPage = (props: {
    tokens?: PersonalAccessToken[];
    newToken?: { name: string; token: string } | null;
}) =>
    mount(ApiTokens, {
        props: { tokens: props.tokens ?? [], newToken: props.newToken ?? null },
        global: { stubs },
    });

describe('settings/ApiTokens', () => {
    it('renders the one-time reveal block only while a new token is present', () => {
        const wrapperWith = mountPage({
            newToken: { name: 'fresh', token: 'plain-secret-value' },
        });

        expect(wrapperWith.text()).toContain(
            'settings.api_tokens.reveal_title',
        );
        expect(wrapperWith.text()).toContain('plain-secret-value');

        const wrapperWithout = mountPage({ newToken: null });

        expect(wrapperWithout.text()).not.toContain('plain-secret-value');
        expect(wrapperWithout.text()).not.toContain(
            'settings.api_tokens.reveal_title',
        );
    });

    it('renders the empty state when there are no tokens', () => {
        const wrapper = mountPage({ tokens: [] });

        expect(wrapper.text()).toContain('settings.api_tokens.empty');
    });

    it('renders a badge per ability using the short translated labels', () => {
        const wrapper = mountPage({
            tokens: [
                makeToken({
                    id: 7,
                    name: 'Cursor',
                    abilities: ['read', 'write'],
                }),
            ],
        });

        expect(wrapper.text()).toContain('Cursor');
        expect(wrapper.text()).toContain(
            'settings.api_tokens.ability_short.read',
        );
        expect(wrapper.text()).toContain(
            'settings.api_tokens.ability_short.write',
        );
    });

    it('toggles an ability into and out of the form array', async () => {
        const wrapper = mountPage({ tokens: [] });

        expect(mocks.createForm.abilities).toEqual(['read']);

        await wrapper.find('#ability-write').setValue(true);
        expect(mocks.createForm.abilities).toEqual(
            expect.arrayContaining(['write']),
        );

        await wrapper.find('#ability-write').setValue(false);
        expect(mocks.createForm.abilities).not.toContain('write');
    });

    it('calls router.delete when the revoke action is confirmed', async () => {
        mocks.routerDelete.mockClear();

        const wrapper = mountPage({
            tokens: [makeToken({ id: 42, abilities: ['read'] })],
        });

        // The confirm button lives inside the dialog footer and is the
        // destructive button there (the trigger shares the same label).
        await wrapper
            .find('[data-testid="dialog-footer"] [variant="destructive"]')
            .trigger('click');

        expect(mocks.routerDelete).toHaveBeenCalledTimes(1);
        expect(mocks.routerDelete.mock.calls[0][0]).toEqual(
            expect.objectContaining({ method: 'delete' }),
        );
        expect(mocks.routerDelete.mock.calls[0][0].url).toContain('42');
    });
});

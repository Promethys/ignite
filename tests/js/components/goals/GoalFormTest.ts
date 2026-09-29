import GoalForm from '@/components/goals/GoalForm.vue';
import type { Goal, User } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

type CapturedForm = Record<string, unknown> & {
    type: string;
    steps: { key: number; title: string; deadline: string }[];
};

const captured = vi.hoisted(() => ({
    form: null as unknown as CapturedForm,
    transform: null as unknown as (
        data: Record<string, unknown>,
    ) => Record<string, unknown>,
}));

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');

    return {
        Link: { template: '<a><slot /></a>' },
        useForm: (...args: unknown[]) => {
            const data = args[args.length - 1] as Record<string, unknown>;
            const form = reactive({
                ...data,
                errors: {} as Record<string, string>,
                processing: false,
                submit: vi.fn(),
                reset: vi.fn(),
                clearErrors: vi.fn(),
                transform: vi.fn(
                    (
                        callback: (
                            data: Record<string, unknown>,
                        ) => Record<string, unknown>,
                    ) => {
                        captured.transform = callback;
                    },
                ),
                validate: vi.fn(),
                withPrecognition: vi.fn(() => form),
            });
            captured.form = form as unknown as CapturedForm;
            return form;
        },
    };
});

vi.mock('@/actions/App/Http/Controllers/Goals/GoalController', () => ({
    store: () => ({ method: 'POST', url: '/goals' }),
    update: () => ({ method: 'PUT', url: '/goals/1' }),
}));

vi.mock('@/routes/categories', () => ({
    default: { index: () => ({ url: '/categories' }) },
}));

vi.mock('@/routes/goals', () => ({
    default: { index: () => ({ url: '/goals' }) },
}));

const stubs = {
    Card: { template: '<div><slot /></div>' },
    CardContent: { template: '<div><slot /></div>' },
    CardFooter: { template: '<div><slot /></div>' },
    Button: { template: '<button><slot /></button>' },
    Input: {
        template: '<input :value="modelValue" />',
        props: ['modelValue'],
    },
    Label: { template: '<label><slot /></label>' },
    Textarea: { template: '<textarea />', props: ['modelValue'] },
    InputError: { template: '<span />', props: ['message'] },
    TextLink: { template: '<a><slot /></a>' },
    HelpTooltip: { template: '<span><slot /></span>' },
    Select: {
        props: ['modelValue'],
        template: '<div class="select" :data-value="modelValue"><slot /></div>',
    },
    SelectTrigger: { template: '<div><slot /></div>' },
    SelectContent: { template: '<div><slot /></div>' },
    SelectItem: { template: '<div />' },
    SelectValue: { template: '<div />' },
};

const mountForm = (props: Record<string, unknown> = {}) =>
    mount(GoalForm, { props, global: { stubs } });

describe('GoalForm', () => {
    it('seeds the category from the selectedCategory prop in create mode', () => {
        const user = {
            id: 1,
            categories: { '1': 'Health', '2': 'Learning' },
        } as unknown as User;

        const wrapper = mountForm({ user, selectedCategory: '1' });

        const categorySelect = wrapper.find('.select#category_id');
        expect(categorySelect.exists()).toBe(true);
        expect(categorySelect.attributes('data-value')).toBe('1');
    });

    it('leaves the category unset when no selectedCategory is provided', () => {
        const user = {
            id: 1,
            categories: { '1': 'Health' },
        } as unknown as User;

        const wrapper = mountForm({ user });

        expect(
            wrapper.find('.select#category_id').attributes('data-value'),
        ).toBeUndefined();
    });

    it('seeds the completion date input from the local calendar date', () => {
        const original = process.env.TZ;
        process.env.TZ = 'Pacific/Kiritimati';

        try {
            const user = { id: 1, categories: {} } as unknown as User;
            const record = {
                completed_at: '2026-08-29T23:30:00.000000Z',
            } as unknown as Goal;

            const wrapper = mountForm({ record, user });

            expect(wrapper.find('input#completed_at').element.value).toBe(
                '2026-08-30',
            );
        } finally {
            process.env.TZ = original;
        }
    });

    describe('staged steps', () => {
        const user = { id: 1, categories: {} } as unknown as User;

        it('hides the steps section unless the goal is multi-step', () => {
            const wrapper = mountForm({ user });

            expect(wrapper.text()).not.toContain('goals.form.steps');
        });

        it('lets steps be added and removed on a new multi-step goal', async () => {
            const wrapper = mountForm({ user });
            captured.form.type = 'multi_step';
            await nextTick();

            const addButton = wrapper
                .findAll('button')
                .find((button) => button.text() === 'steps.add')!;
            await addButton.trigger('click');
            await addButton.trigger('click');

            expect(captured.form.steps).toHaveLength(2);
            expect(wrapper.findAll('li')).toHaveLength(2);

            await wrapper
                .find('li button[aria-label^="goals.form.remove_step"]')
                .trigger('click');

            expect(captured.form.steps).toHaveLength(1);
        });

        it('hides the steps section when editing a goal', async () => {
            const record = { type: 'multi_step' } as unknown as Goal;
            const wrapper = mountForm({ user, record });
            await nextTick();

            expect(wrapper.text()).not.toContain('goals.form.steps');
        });

        it('submits steps only for a new multi-step goal', () => {
            mountForm({ user });
            const steps = [{ title: 'Outline', deadline: '' }];

            expect(
                captured.transform({ type: 'multi_step', steps }).steps,
            ).toEqual([{ title: 'Outline', deadline: null }]);
            expect(
                captured.transform({ type: 'simple', steps }).steps,
            ).toBeUndefined();

            mountForm({ user, record: { type: 'multi_step' } as Goal });

            expect(
                captured.transform({ type: 'multi_step', steps }).steps,
            ).toBeUndefined();
        });
        it('reorders staged steps with the arrow keys', async () => {
            const wrapper = mountForm({ user });
            captured.form.type = 'multi_step';
            captured.form.steps = [
                { key: 1, title: 'Outline', deadline: '' },
                { key: 2, title: 'Draft', deadline: '' },
            ];
            await nextTick();

            await wrapper
                .find('[data-drag-handle="1"]')
                .trigger('keydown', { key: 'ArrowDown' });

            expect(captured.form.steps.map((step) => step.title)).toEqual([
                'Draft',
                'Outline',
            ]);

            await wrapper
                .find('[data-drag-handle="1"]')
                .trigger('keydown', { key: 'ArrowDown' });

            expect(captured.form.steps[1].title).toBe('Outline');
        });

        it('does not submit the client-side step key', () => {
            mountForm({ user });

            expect(
                captured.transform({
                    type: 'multi_step',
                    steps: [{ key: 7, title: 'Outline', deadline: '' }],
                }).steps,
            ).toEqual([{ title: 'Outline', deadline: null }]);
        });
    });

    describe('character counters', () => {
        const user = { id: 1, categories: {} } as unknown as User;

        it('counts the title and unit against their limits', async () => {
            const wrapper = mountForm({ user });
            captured.form.title = 'x'.repeat(250);
            captured.form.unit = 'u'.repeat(45);
            await nextTick();

            expect(wrapper.text()).toContain('250/255');
            expect(wrapper.text()).toContain('45/50');
        });

        it('counts each staged step title', async () => {
            const wrapper = mountForm({ user });
            captured.form.type = 'multi_step';
            await nextTick();
            await wrapper
                .findAll('button')
                .find((button) => button.text() === 'steps.add')!
                .trigger('click');
            captured.form.steps[0].title = 's'.repeat(210);
            await nextTick();

            expect(wrapper.find('li').text()).toContain('210/255');
        });
    });
});

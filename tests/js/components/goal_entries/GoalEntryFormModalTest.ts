import GoalEntryFormModal from '@/components/goal_entries/GoalEntryFormModal.vue';
import type { Goal, GoalEntry } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => ({
    useForm: (...args: unknown[]) => {
        const data = args[args.length - 1] as Record<string, unknown>;
        return {
            ...data,
            errors: {} as Record<string, string>,
            processing: false,
            submit: vi.fn(),
            reset: vi.fn(),
            clearErrors: vi.fn(),
            defaults: vi.fn(),
        };
    },
}));

vi.mock('@/actions/App/Http/Controllers/Goals/GoalEntryController.js', () => ({
    store: () => ({ method: 'post', url: '/goals/1/entries' }),
    update: () => ({ method: 'put', url: '/goals/1/entries/7' }),
}));

const stubs = {
    Dialog: { template: '<div><slot /></div>', props: ['open'] },
    DialogClose: { template: '<div><slot /></div>' },
    DialogContent: { template: '<div><slot /></div>' },
    DialogDescription: { template: '<p><slot /></p>' },
    DialogFooter: { template: '<div><slot /></div>' },
    DialogHeader: { template: '<div><slot /></div>' },
    DialogTitle: { template: '<h2><slot /></h2>' },
    DialogTrigger: { template: '<div><slot /></div>' },
    Button: { template: '<button><slot /></button>' },
    Label: { template: '<label><slot /></label>' },
    Textarea: { template: '<textarea />', props: ['modelValue'] },
    InputError: { template: '<span />', props: ['message'] },
    Input: {
        template: '<input :id="id" :value="modelValue" />',
        props: ['modelValue', 'id', 'type', 'step', 'placeholder'],
    },
};

const mountModal = (goalType: string, props: Record<string, unknown> = {}) =>
    mount(GoalEntryFormModal, {
        props: {
            goal: { id: 1, type: goalType, unit: 'km' } as unknown as Goal,
            ...props,
        },
        global: { stubs },
    });

describe('GoalEntryFormModal', () => {
    // Every non-recurring type logs progress by increment, so hiding the field
    // for anything but `quantifiable` left simple and multi_step goals posting
    // an empty required value with the error rendered inside a hidden block.
    it.each(['quantifiable', 'simple', 'multi_step'])(
        'shows the increment field for a %s goal',
        (goalType) => {
            const wrapper = mountModal(goalType);

            expect(wrapper.find('input#increment').exists()).toBe(true);
        },
    );

    it('prefills the increment from the record when editing', () => {
        const record = {
            id: 7,
            increment_value: 12,
            note: 'Existing note',
        } as unknown as GoalEntry;

        const wrapper = mountModal('quantifiable', { record });

        expect(
            (wrapper.find('input#increment').element as HTMLInputElement).value,
        ).toBe('12');
        expect(wrapper.text()).toContain('goals.entries.form.edit_title');
    });
});

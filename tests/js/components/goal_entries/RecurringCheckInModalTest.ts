import RecurringCheckInModal from '@/components/goal_entries/RecurringCheckInModal.vue';
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
            validate: vi.fn(),
            withPrecognition: vi.fn().mockReturnThis(),
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
    Textarea: { template: '<textarea />', props: ['modelValue'] },
    InputError: { template: '<span />', props: ['message'] },
    Input: {
        template:
            '<input :id="id" :type="type" :max="max" :value="modelValue" />',
        props: ['modelValue', 'id', 'type', 'max'],
    },
};

const goal = {
    id: 1,
    type: 'recurring',
    polarity: 'positive',
} as unknown as Goal;

const mountModal = (props: Record<string, unknown> = {}) =>
    mount(RecurringCheckInModal, {
        props: { goal, today: '2026-07-16', ...props },
        global: { stubs },
    });

describe('RecurringCheckInModal', () => {
    it('collects a date and a note, never an increment', () => {
        const wrapper = mountModal();

        expect(wrapper.find('input#entry_date').exists()).toBe(true);
        expect(wrapper.find('textarea').exists()).toBe(true);
        expect(wrapper.find('input#increment').exists()).toBe(false);
    });

    it('caps the date field at the server-provided today', () => {
        const wrapper = mountModal();

        expect(wrapper.find('input#entry_date').attributes('max')).toBe(
            '2026-07-16',
        );
    });

    it('defaults to today when creating', () => {
        const wrapper = mountModal();

        expect(
            (wrapper.find('input#entry_date').element as HTMLInputElement)
                .value,
        ).toBe('2026-07-16');
    });

    it('prefills the record date when editing, without shifting the day', () => {
        const record = {
            id: 7,
            entry_date: '2026-07-14T00:00:00.000000Z',
            note: 'Existing note',
        } as unknown as GoalEntry;

        const wrapper = mountModal({ record });

        // Sliced from the ISO string: parsing it through the browser timezone
        // would render the 13th for anyone west of UTC.
        expect(
            (wrapper.find('input#entry_date').element as HTMLInputElement)
                .value,
        ).toBe('2026-07-14');
    });

    it('shows the edit labels when given a record', () => {
        const record = {
            id: 7,
            entry_date: '2026-07-14T00:00:00.000000Z',
            note: null,
        } as unknown as GoalEntry;

        const wrapper = mountModal({ record });

        expect(wrapper.text()).toContain('goals.entries.form.edit_title');
        expect(wrapper.text()).not.toContain('goals.checkin.title_positive');
    });

    it('shows the check-in labels when creating', () => {
        const wrapper = mountModal();

        expect(wrapper.text()).toContain('goals.checkin.title_positive');
    });
});

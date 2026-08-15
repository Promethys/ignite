import RecurringCheckInModal from '@/components/goal-entries/RecurringCheckInModal.vue';
import type { Goal, GoalEntry } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { mount, VueWrapper } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// The sibling test file mocks useForm away. These cases need the real one:
// they cover how the form seeds its values and its reset baseline, which a
// mock cannot reproduce.
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

const today = '2026-07-16';
const olderCheckInDate = '2026-07-09';

const record = {
    id: 7,
    entry_date: '2026-07-14T00:00:00.000000Z',
    note: 'Existing note',
} as unknown as GoalEntry;

const mountModal = (props: Record<string, unknown> = {}) =>
    mount(RecurringCheckInModal, {
        props: { goal, today, ...props },
        global: { stubs },
    });

// `<script setup>` bindings never land on `vm`, so the internal instance is the
// only way to reach the form and drive the reset the submit handler performs.
const formOf = (wrapper: VueWrapper) =>
    (wrapper.vm.$ as unknown as { setupState: Record<string, any> }).setupState
        .form;

const dateFieldOf = (wrapper: VueWrapper) =>
    (wrapper.find('input#entry_date').element as HTMLInputElement).value;

// Inertia keeps form state in history under a name. Naming this form made a
// check-in inherit the previous one's date, so both states are pinned here.
const rememberACheckIn = (data: Record<string, unknown>) =>
    router.remember({ data, errors: {} }, 'RecurringCheckInForm');

const forgetCheckIns = () => router.remember(undefined, 'RecurringCheckInForm');

describe('RecurringCheckInModal form state', () => {
    beforeEach(() => {
        forgetCheckIns();
    });

    it('opens a new check-in on today, never on the last one submitted', () => {
        rememberACheckIn({ entry_date: olderCheckInDate, note: 'Older note' });

        expect(dateFieldOf(mountModal())).toBe(today);
    });

    it('resets a new check-in back to today once submitted', () => {
        const form = formOf(mountModal());

        form.entry_date = olderCheckInDate;
        form.note = 'Draft note';
        form.reset();

        expect(form.entry_date).toBe(today);
        expect(form.note).toBe('');
    });

    it('resets to today even after an earlier check-in was submitted', () => {
        rememberACheckIn({ entry_date: olderCheckInDate, note: 'Older note' });

        const form = formOf(mountModal());
        form.reset();

        expect(form.entry_date).toBe(today);
        expect(form.note).toBe('');
    });

    it('keeps an edited check-in on its own record', () => {
        rememberACheckIn({ entry_date: olderCheckInDate, note: 'Older note' });

        expect(dateFieldOf(mountModal({ record }))).toBe('2026-07-14');
    });
});

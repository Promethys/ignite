import GoalEntriesIndex from '@/pages/GoalEntries/Index.vue';
import type { Goal } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

// <Head> needs the app head manager, absent in unit tests; stub only that
// export plus the router and the scroll wrapper, and keep the rest real.
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal<typeof import('@inertiajs/vue3')>();
    return {
        ...actual,
        Head: { name: 'Head', render: () => null },
        router: { reload: vi.fn() },
        InfiniteScroll: { template: '<div><slot /></div>' },
    };
});

// Mocked as modules rather than stubbed by name, so the assertions can tell
// which modal the page chose without depending on how it is referenced.
vi.mock('@/components/goal-entries/GoalEntryFormModal.vue', () => ({
    default: {
        name: 'GoalEntryFormModal',
        template: '<div class="progress-modal" />',
    },
}));

vi.mock('@/components/goal-entries/RecurringCheckInModal.vue', () => ({
    default: {
        name: 'RecurringCheckInModal',
        template: '<div class="checkin-modal" />',
    },
}));

vi.mock('@/components/goal-entries/DeleteEntryDialog.vue', () => ({
    default: {
        name: 'DeleteEntryDialog',
        template: '<div class="delete-dialog" />',
    },
}));

const buildEntry = (note: string | null = null) => ({
    id: 7,
    entry_date: '2026-07-14T00:00:00.000000Z',
    increment_value: 5,
    previous_value: 0,
    value: 5,
    note,
});

const mountPage = (goalType: string, note: string | null = null) =>
    mount(GoalEntriesIndex, {
        props: {
            goal: { id: 1, title: 'A goal', type: goalType } as unknown as Goal,
            entries: { data: [buildEntry(note)] },
            today: '2026-07-16',
        },
        global: { stubs: { AppLayout: { template: '<div><slot /></div>' } } },
    });

describe('GoalEntries/Index', () => {
    // A recurring goal is checked in, not incremented. Offering the progress
    // form here posted to the check-in branch, which requires a date the form
    // never collected, so the modal could not succeed.
    it('offers only the check-in modal for a recurring goal', () => {
        const wrapper = mountPage('recurring');

        // One to log a new check-in, one for the row's edit action.
        expect(wrapper.findAll('.checkin-modal')).toHaveLength(2);
        expect(wrapper.find('.progress-modal').exists()).toBe(false);
    });

    // Every non-recurring type logs progress by increment, so all three must
    // reach the progress form, not just `quantifiable`.
    it.each(['quantifiable', 'simple', 'multi_step'])(
        'offers only the progress modal for a %s goal',
        (goalType) => {
            const wrapper = mountPage(goalType);

            expect(wrapper.findAll('.progress-modal')).toHaveLength(2);
            expect(wrapper.find('.checkin-modal').exists()).toBe(false);
        },
    );

    it('hides the increment summary on a recurring entry', () => {
        const wrapper = mountPage('recurring');

        expect(wrapper.text()).not.toContain('+5');
    });

    it('shows the increment summary on a non-recurring entry', () => {
        const wrapper = mountPage('simple');

        expect(wrapper.text()).toContain('+5');
    });

    it('lists each entry as an item in a single group', () => {
        const wrapper = mountPage('simple');

        expect(wrapper.find('[data-slot="item-group"]').exists()).toBe(true);
        expect(wrapper.findAll('[data-slot="item"]')).toHaveLength(1);
    });

    // The note sits in the item footer so it spans the full card width rather
    // than competing with the actions for space on the first row.
    it('renders the note in the entry footer', () => {
        const wrapper = mountPage('simple', 'Ran in the rain');

        expect(wrapper.find('[data-slot="item-footer"]').text()).toContain(
            'Ran in the rain',
        );
    });

    it('omits the footer on an entry without a note', () => {
        const wrapper = mountPage('simple');

        expect(wrapper.find('[data-slot="item-footer"]').exists()).toBe(false);
    });

    it('offers a delete dialog for each entry', () => {
        const wrapper = mountPage('simple');

        expect(wrapper.findAll('.delete-dialog')).toHaveLength(1);
    });
});

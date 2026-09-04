import EntryRowActions from '@/components/goal-entries/EntryRowActions.vue';
import type { Goal, GoalEntry } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

// The three children are stubbed by name so the assertions can prove which
// edit affordance was chosen and what it was handed, without mounting the
// modals themselves.
const RecurringCheckInModal = {
    name: 'RecurringCheckInModal',
    props: ['goal', 'record', 'today'],
    template: '<div class="recurring-modal"><slot name="trigger" /></div>',
};

const GoalEntryFormModal = {
    name: 'GoalEntryFormModal',
    props: ['goal', 'record'],
    template: '<div class="entry-modal"><slot name="trigger" /></div>',
};

const DeleteEntryDialog = {
    name: 'DeleteEntryDialog',
    props: ['goal', 'record'],
    template: '<div class="delete-dialog"><slot name="trigger" /></div>',
};

const stubs = {
    RecurringCheckInModal,
    GoalEntryFormModal,
    DeleteEntryDialog,
    DropdownMenu: { template: '<div><slot /></div>' },
    DropdownMenuTrigger: { template: '<div><slot /></div>' },
    DropdownMenuContent: { template: '<div><slot /></div>' },
    DropdownMenuGroup: { template: '<div><slot /></div>' },
    DropdownMenuItem: { template: '<button><slot /></button>' },
    Button: { template: '<button><slot /></button>' },
};

const record = { id: 7 } as unknown as GoalEntry;

const goalOfType = (type: string) =>
    ({ id: 1, title: 'A goal', type }) as unknown as Goal;

const mountActions = (type: string, today = '2026-09-04') =>
    mount(EntryRowActions, {
        props: { goal: goalOfType(type), record, today },
        global: { stubs },
    });

describe('EntryRowActions', () => {
    it('offers the recurring check-in modal for a recurring goal', () => {
        const wrapper = mountActions('recurring');

        expect(wrapper.find('.recurring-modal').exists()).toBe(true);
        expect(wrapper.find('.entry-modal').exists()).toBe(false);
    });

    it.each(['simple', 'quantifiable', 'multi_step'])(
        'offers the entry form modal for a %s goal',
        (type) => {
            const wrapper = mountActions(type);

            expect(wrapper.find('.entry-modal').exists()).toBe(true);
            expect(wrapper.find('.recurring-modal').exists()).toBe(false);
        },
    );

    // The whole point of this component is that the goal page gained an edit
    // action it did not have. If the edit affordance stops rendering, the
    // regression is silent in the browser, so pin both actions here.
    it('always offers both an edit and a delete action', () => {
        const quantifiable = mountActions('quantifiable');
        const recurring = mountActions('recurring');

        expect(quantifiable.find('.entry-modal').exists()).toBe(true);
        expect(quantifiable.find('.delete-dialog').exists()).toBe(true);
        expect(recurring.find('.recurring-modal').exists()).toBe(true);
        expect(recurring.find('.delete-dialog').exists()).toBe(true);
    });

    it('hands each child the goal and the entry it was given', () => {
        const wrapper = mountActions('quantifiable');
        const goal = wrapper.props('goal');

        expect(wrapper.getComponent(GoalEntryFormModal).props()).toMatchObject({
            goal,
            record,
        });
        expect(wrapper.getComponent(DeleteEntryDialog).props()).toMatchObject({
            goal,
            record,
        });
    });

    // `today` is server-authoritative in the user's timezone. A check-in modal
    // that falls back to the browser's date can write an entry against the
    // wrong day, so prove the prop is forwarded rather than dropped.
    it('forwards today to the recurring check-in modal', () => {
        const wrapper = mountActions('recurring', '2026-01-31');

        expect(wrapper.getComponent(RecurringCheckInModal).props('today')).toBe(
            '2026-01-31',
        );
    });

    it('names the overflow trigger for assistive technology', () => {
        const wrapper = mountActions('simple');

        expect(wrapper.find('.sr-only').text()).toBe('common.actions.more');
    });
});

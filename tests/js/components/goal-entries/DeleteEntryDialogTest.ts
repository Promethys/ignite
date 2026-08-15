import DeleteEntryDialog from '@/components/goal-entries/DeleteEntryDialog.vue';
import type { Goal, GoalEntry } from '@/types/models';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const routerDelete = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    router: {
        delete: (...args: unknown[]) => routerDelete(...args),
    },
}));

// Returned verbatim so the assertions can check which entry the dialog
// targeted rather than trusting a generated URL string.
vi.mock('@/actions/App/Http/Controllers/Goals/GoalEntryController.js', () => ({
    destroy: (args: unknown) => ({ method: 'delete', args }),
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
};

const goal = { id: 1, title: 'A goal', type: 'simple' } as unknown as Goal;
const record = { id: 7 } as unknown as GoalEntry;

const mountDialog = () =>
    mount(DeleteEntryDialog, { props: { goal, record }, global: { stubs } });

describe('DeleteEntryDialog', () => {
    beforeEach(() => routerDelete.mockClear());

    it('does not delete anything on render', () => {
        mountDialog();

        expect(routerDelete).not.toHaveBeenCalled();
    });

    // The dialog renders the trigger, then cancel, then the confirming
    // delete. Asserting the first two are inert pins that order down, so the
    // confirmation below cannot pass by landing on the wrong button.
    it('does not delete from the trigger or the cancel button', async () => {
        const wrapper = mountDialog();
        const buttons = wrapper.findAll('button');

        expect(buttons).toHaveLength(3);

        await buttons[0].trigger('click');
        await buttons[1].trigger('click');

        expect(routerDelete).not.toHaveBeenCalled();
    });

    it('deletes the entry it was given when confirmed', async () => {
        const wrapper = mountDialog();

        await wrapper.findAll('button')[2].trigger('click');

        expect(routerDelete).toHaveBeenCalledTimes(1);
        expect(routerDelete.mock.calls[0][0]).toEqual({
            method: 'delete',
            args: { goal, goalEntry: record },
        });
    });

    it('renders a trigger override when one is given', () => {
        const wrapper = mount(DeleteEntryDialog, {
            props: { goal, record },
            slots: { trigger: '<button class="custom">Remove</button>' },
            global: { stubs },
        });

        expect(wrapper.find('.custom').exists()).toBe(true);
    });
});

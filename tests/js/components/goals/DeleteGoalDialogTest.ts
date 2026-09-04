import DeleteGoalDialog from '@/components/goals/DeleteGoalDialog.vue';
import type { Goal } from '@/types/models';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const routerDelete = vi.fn();

vi.mock('@inertiajs/vue3', () => ({
    router: {
        delete: (...args: unknown[]) => routerDelete(...args),
    },
}));

// Returned verbatim so the assertions can check which goal the dialog
// targeted rather than trusting a generated URL string.
vi.mock('@/routes/goals', () => ({
    default: {
        destroy: (args: unknown) => ({ method: 'delete', args }),
    },
}));

const stubs = {
    AlertDialog: { template: '<div><slot /></div>' },
    AlertDialogAction: { template: '<button><slot /></button>' },
    AlertDialogCancel: { template: '<button><slot /></button>' },
    AlertDialogContent: { template: '<div><slot /></div>' },
    AlertDialogDescription: { template: '<p><slot /></p>' },
    AlertDialogFooter: { template: '<div><slot /></div>' },
    AlertDialogHeader: { template: '<div><slot /></div>' },
    AlertDialogTitle: { template: '<h2><slot /></h2>' },
    AlertDialogTrigger: { template: '<div><slot /></div>' },
    Button: { template: '<button><slot /></button>' },
};

const record = { id: 3, title: 'A goal' } as unknown as Goal;

const mountDialog = () =>
    mount(DeleteGoalDialog, { props: { record }, global: { stubs } });

describe('DeleteGoalDialog', () => {
    beforeEach(() => routerDelete.mockClear());

    it('does not delete anything on render', () => {
        mountDialog();

        expect(routerDelete).not.toHaveBeenCalled();
    });

    // Trigger first, then cancel, then the confirming action. Pinning the
    // order means the confirmation assertion below cannot pass by landing on
    // the wrong button.
    it('does not delete from the trigger or the cancel button', async () => {
        const wrapper = mountDialog();
        const buttons = wrapper.findAll('button');

        expect(buttons).toHaveLength(3);

        await buttons[0].trigger('click');
        await buttons[1].trigger('click');

        expect(routerDelete).not.toHaveBeenCalled();
    });

    it('deletes the goal it was given when confirmed', async () => {
        const wrapper = mountDialog();

        await wrapper.findAll('button')[2].trigger('click');

        expect(routerDelete).toHaveBeenCalledTimes(1);
        expect(routerDelete.mock.calls[0][0]).toEqual({
            method: 'delete',
            args: record,
        });
    });

    it('renders a trigger override when one is given', () => {
        const wrapper = mount(DeleteGoalDialog, {
            props: { record },
            slots: { trigger: '<button class="custom">Remove</button>' },
            global: { stubs },
        });

        expect(wrapper.find('.custom').exists()).toBe(true);
    });
});

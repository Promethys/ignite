import AssistantConversationList from '@/components/assistant/AssistantConversationList.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/components/ui/alert-dialog', () => {
    const passthrough = { template: '<div><slot /></div>' };
    const button = { template: '<button type="button"><slot /></button>' };

    return {
        AlertDialog: passthrough,
        AlertDialogAction: button,
        AlertDialogCancel: button,
        AlertDialogContent: passthrough,
        AlertDialogDescription: passthrough,
        AlertDialogFooter: passthrough,
        AlertDialogHeader: passthrough,
        AlertDialogTitle: passthrough,
        AlertDialogTrigger: passthrough,
    };
});

const conversations = [
    { id: 'one', title: 'Reading plan', updated_at: '2026-10-01T10:00:00Z' },
    { id: 'two', title: 'Weekly review', updated_at: '2026-10-02T10:00:00Z' },
];

const mountList = (overrides: Record<string, unknown> = {}) => {
    const rename = vi.fn().mockResolvedValue(undefined);
    const remove = vi.fn().mockResolvedValue(undefined);
    const wrapper = mount(AssistantConversationList, {
        props: {
            conversations,
            activeConversationId: 'two',
            rename,
            remove,
            ...overrides,
        },
    });

    return { wrapper, rename, remove };
};

describe('AssistantConversationList', () => {
    it('says so when there is no conversation', () => {
        const { wrapper } = mountList({ conversations: [] });

        expect(wrapper.text()).toContain('assistant.no_history');
    });

    it('opens the conversation that was clicked', async () => {
        const { wrapper } = mountList();

        await wrapper
            .find('[data-test="assistant-conversation"]')
            .trigger('click');

        expect(wrapper.emitted('open')).toEqual([['one']]);
    });

    it('renames a conversation with the trimmed title', async () => {
        const { wrapper, rename } = mountList();

        await wrapper.find('[data-test="assistant-rename"]').trigger('click');
        const input = wrapper.find('[data-test="assistant-rename-input"]');
        expect((input.element as HTMLInputElement).value).toBe('Reading plan');

        await input.setValue('  Books for 2027  ');
        await wrapper
            .find('[data-test="assistant-rename-form"]')
            .trigger('submit');
        await flushPromises();

        expect(rename).toHaveBeenCalledWith('one', 'Books for 2027');
        expect(
            wrapper.find('[data-test="assistant-rename-form"]').exists(),
        ).toBe(false);
    });

    it('does not save an empty title', async () => {
        const { wrapper, rename } = mountList();

        await wrapper.find('[data-test="assistant-rename"]').trigger('click');
        await wrapper
            .find('[data-test="assistant-rename-input"]')
            .setValue('   ');
        await wrapper
            .find('[data-test="assistant-rename-form"]')
            .trigger('submit');

        expect(rename).not.toHaveBeenCalled();
    });

    it('deletes only after the confirmation', async () => {
        const { wrapper, remove } = mountList();

        await wrapper.find('[data-test="assistant-delete"]').trigger('click');
        expect(remove).not.toHaveBeenCalled();

        await wrapper
            .find('[data-test="assistant-delete-confirm"]')
            .trigger('click');

        expect(remove).toHaveBeenCalledWith('one');
    });

    it('keeps the form open and says so when the rename fails', async () => {
        const { wrapper } = mountList({
            rename: vi.fn().mockRejectedValue(new Error('failed')),
        });

        await wrapper.find('[data-test="assistant-rename"]').trigger('click');
        await wrapper
            .find('[data-test="assistant-rename-form"]')
            .trigger('submit');
        await flushPromises();

        expect(wrapper.find('[role="alert"]').text()).toBe(
            'assistant.action_failed',
        );
        expect(
            wrapper.find('[data-test="assistant-rename-form"]').exists(),
        ).toBe(true);
    });
});

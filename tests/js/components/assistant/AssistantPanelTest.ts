import AssistantPanel from '@/components/assistant/AssistantPanel.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref, shallowRef } from 'vue';

const page = {
    props: { assistant: { available: true }, goal: { id: 7 }, locale: 'en' },
};

const chat = {
    messages: shallowRef<any[]>([]),
    status: shallowRef('ready'),
    error: shallowRef<Error | undefined>(undefined),
    sendMessage: vi.fn(),
    addToolApprovalResponse: vi.fn(),
    clearError: vi.fn(),
    conversationId: ref<string | null>(null),
    conversations: ref<any[]>([]),
    isLoadingConversation: ref(false),
    sentAt: vi.fn((message: any) => message.metadata?.createdAt),
    startNewConversation: vi.fn(),
    loadConversations: vi.fn(),
    openConversation: vi.fn(),
    openLatestConversation: vi.fn(),
    renameConversation: vi.fn(),
    deleteConversation: vi.fn(),
};

let goalIdGetter: () => number | null;

const copy = vi.fn();

vi.mock('@vueuse/core', async (original) => ({
    ...(await original<object>()),
    useClipboard: () => ({
        copy,
        copied: ref(false),
        isSupported: ref(true),
    }),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => page,
}));

vi.mock('@/composables/useAssistantChat', async (original) => ({
    ...(await original<object>()),
    useAssistantChat: (currentGoalId: () => number | null) => {
        goalIdGetter = currentGoalId;

        return chat;
    },
}));

vi.mock('@/components/ui/sheet', () => {
    const passthrough = { template: '<div><slot /></div>' };

    return {
        Sheet: {
            props: ['open'],
            emits: ['update:open'],
            template:
                '<div><button data-test="sheet-open" @click="$emit(\'update:open\', true)" /><slot /></div>',
        },
        SheetContent: passthrough,
        SheetDescription: passthrough,
        SheetHeader: passthrough,
        SheetTitle: passthrough,
        SheetTrigger: passthrough,
    };
});

vi.mock('@/components/ui/tooltip', () => {
    const passthrough = { template: '<div><slot /></div>' };

    return {
        Tooltip: passthrough,
        TooltipContent: passthrough,
        TooltipProvider: passthrough,
        TooltipTrigger: passthrough,
    };
});

const deletionWaiting = {
    id: 'assistant-1',
    role: 'assistant',
    parts: [
        {
            type: 'tool-delete_goal',
            toolCallId: 'call-1',
            state: 'approval-requested',
            approval: { id: 'call-1', requestReason: 'Deletes the goal.' },
        },
    ],
};

beforeEach(() => {
    vi.clearAllMocks();
    page.props.assistant.available = true;
    chat.messages.value = [];
    chat.status.value = 'ready';
    chat.error.value = undefined;
    chat.conversations.value = [];
});

describe('AssistantPanel', () => {
    it('renders nothing when the user has no assistant', () => {
        page.props.assistant.available = false;

        expect(mount(AssistantPanel).find('[data-test]').exists()).toBe(false);
    });

    it('gives the chat the goal on screen', () => {
        mount(AssistantPanel);

        expect(goalIdGetter()).toBe(7);
    });

    it('opens the latest conversation the first time only', async () => {
        const wrapper = mount(AssistantPanel);

        await wrapper.find('[data-test="sheet-open"]').trigger('click');
        await wrapper.find('[data-test="sheet-open"]').trigger('click');

        expect(chat.openLatestConversation).toHaveBeenCalledTimes(1);
    });

    it('sends the trimmed prompt and empties the field', async () => {
        const wrapper = mount(AssistantPanel);
        const field = wrapper.find('[data-test="assistant-prompt"]');

        await field.setValue('  How am I doing?  ');
        await wrapper.find('form').trigger('submit');

        expect(chat.sendMessage).toHaveBeenCalledWith({
            text: 'How am I doing?',
        });
        expect((field.element as HTMLTextAreaElement).value).toBe('');
    });

    it('cannot send an empty prompt or while a reply is streaming', async () => {
        const wrapper = mount(AssistantPanel);
        const send = wrapper.find('[data-test="assistant-send"]');

        expect(send.attributes('disabled')).toBeDefined();

        await wrapper.find('[data-test="assistant-prompt"]').setValue('Hello');
        expect(send.attributes('disabled')).toBeUndefined();

        chat.status.value = 'streaming';
        await wrapper.vm.$nextTick();
        expect(send.attributes('disabled')).toBeDefined();
    });

    it('holds new messages until a pending deletion is decided, and posts the decision', async () => {
        chat.messages.value = [deletionWaiting];
        const wrapper = mount(AssistantPanel);

        await wrapper.find('[data-test="assistant-prompt"]').setValue('Hello');
        expect(
            wrapper.find('[data-test="assistant-send"]').attributes('disabled'),
        ).toBeDefined();

        await wrapper.find('[data-test="assistant-approve"]').trigger('click');

        expect(chat.addToolApprovalResponse).toHaveBeenCalledWith({
            id: 'call-1',
            approved: true,
        });
    });

    it('shows user and assistant text and a readable error', async () => {
        chat.messages.value = [
            {
                id: 'user-1',
                role: 'user',
                parts: [{ type: 'text', text: 'How am I doing?' }],
            },
            {
                id: 'assistant-1',
                role: 'assistant',
                parts: [{ type: 'text', text: 'Three goals are active.' }],
            },
        ];
        chat.error.value = new Error('sk-secret provider message');
        const wrapper = mount(AssistantPanel);

        expect(wrapper.text()).toContain('How am I doing?');
        expect(wrapper.text()).toContain('Three goals are active.');
        expect(wrapper.text()).toContain('assistant.error');
        expect(wrapper.text()).not.toContain('sk-secret');
    });

    it('renders the assistant reply as Markdown and the user message as written', () => {
        chat.messages.value = [
            {
                id: 'user-1',
                role: 'user',
                parts: [{ type: 'text', text: '**not bold**' }],
            },
            {
                id: 'assistant-1',
                role: 'assistant',
                parts: [{ type: 'text', text: 'You have **three** goals.' }],
            },
        ];
        const wrapper = mount(AssistantPanel);

        expect(wrapper.text()).toContain('**not bold**');
        expect(
            wrapper.find('[data-test="assistant-markdown"] strong').text(),
        ).toBe('three');
    });

    it('keeps a loading state between a finished tool call and the text that follows', async () => {
        const thinking = () =>
            wrapper.find('[data-test="assistant-thinking"]').exists();
        const assistant = (parts: any[]) => [
            { id: 'assistant-1', role: 'assistant', parts },
        ];
        const tool = (state: string) => ({
            type: 'tool-list_goals',
            toolCallId: 'call-1',
            state,
        });
        chat.status.value = 'streaming';
        chat.messages.value = assistant([tool('input-available')]);
        const wrapper = mount(AssistantPanel);

        expect(thinking()).toBe(false);

        chat.messages.value = assistant([tool('output-available')]);
        await wrapper.vm.$nextTick();
        expect(thinking()).toBe(true);

        chat.messages.value = assistant([
            tool('output-available'),
            { type: 'text', text: 'You have' },
        ]);
        await wrapper.vm.$nextTick();
        expect(thinking()).toBe(false);

        chat.status.value = 'ready';
        chat.messages.value = assistant([tool('output-available')]);
        await wrapper.vm.$nextTick();
        expect(thinking()).toBe(false);
    });

    it('offers to copy each finished message, as Markdown for the assistant', async () => {
        chat.messages.value = [
            {
                id: 'user-1',
                role: 'user',
                parts: [{ type: 'text', text: 'How am I doing?' }],
            },
            {
                id: 'assistant-1',
                role: 'assistant',
                parts: [
                    {
                        type: 'tool-list_goals',
                        toolCallId: 'c',
                        state: 'output-available',
                    },
                    { type: 'text', text: 'You have **three** goals.' },
                ],
            },
        ];
        const wrapper = mount(AssistantPanel);
        const buttons = wrapper.findAll('[data-test="assistant-copy"]');

        expect(buttons).toHaveLength(2);
        await buttons[1].trigger('click');

        expect(copy).toHaveBeenCalledWith('You have **three** goals.');
    });

    it('does not offer to copy a reply that is still being written', () => {
        chat.status.value = 'streaming';
        chat.messages.value = [
            {
                id: 'user-1',
                role: 'user',
                parts: [{ type: 'text', text: 'How am I doing?' }],
            },
            {
                id: 'assistant-1',
                role: 'assistant',
                parts: [{ type: 'text', text: 'You have' }],
            },
        ];

        expect(
            mount(AssistantPanel).findAll('[data-test="assistant-copy"]'),
        ).toHaveLength(1);
    });

    it('lists past conversations and reopens the chosen one', async () => {
        chat.loadConversations.mockImplementation(async () => {
            chat.conversations.value = [
                {
                    id: 'older',
                    title: 'Reading plan',
                    updated_at: '2026-10-01',
                },
            ];
        });
        const wrapper = mount(AssistantPanel);

        await wrapper.find('[data-test="assistant-history"]').trigger('click');
        await vi.waitFor(() =>
            expect(wrapper.text()).toContain('Reading plan'),
        );
        await wrapper
            .find('[data-test="assistant-conversation"]')
            .trigger('click');

        expect(chat.openConversation).toHaveBeenCalledWith('older');
    });

    it('starts a new chat', async () => {
        const wrapper = mount(AssistantPanel);

        await wrapper.find('[data-test="assistant-new-chat"]').trigger('click');

        expect(chat.startNewConversation).toHaveBeenCalled();
    });
});

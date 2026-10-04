import {
    messageChangedData,
    toolActivityKind,
    useAssistantChat,
} from '@/composables/useAssistantChat';
import { router } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';

vi.mock('@inertiajs/vue3', () => ({
    router: { reload: vi.fn() },
}));

vi.mock('@/routes/assistant', () => ({
    chat: { url: () => '/assistant/chat' },
}));

vi.mock('@/routes/assistant/conversations', () => ({
    index: { url: () => '/assistant/conversations' },
    show: { url: (id: string) => `/assistant/conversations/${id}` },
}));

const stream = (
    chunks: Record<string, unknown>[],
    conversationId = 'conversation-1',
) =>
    new Response(
        chunks.map((chunk) => `data: ${JSON.stringify(chunk)}\n\n`).join('') +
            'data: [DONE]\n\n',
        {
            headers: {
                'Content-Type': 'text/event-stream',
                'x-vercel-ai-ui-message-stream': 'v1',
                'X-Conversation-Id': conversationId,
            },
        },
    );

const json = (body: unknown) =>
    new Response(JSON.stringify(body), {
        headers: { 'Content-Type': 'application/json' },
    });

const textReply = (text: string) => [
    { type: 'start', messageId: 'assistant-1' },
    { type: 'start-step' },
    { type: 'text-start', id: 'text-1' },
    { type: 'text-delta', id: 'text-1', delta: text },
    { type: 'text-end', id: 'text-1' },
    { type: 'finish-step' },
    { type: 'finish', finishReason: 'stop' },
];

const pausedDeletion = [
    { type: 'start', messageId: 'assistant-1' },
    { type: 'start-step' },
    {
        type: 'tool-input-available',
        toolCallId: 'call-1',
        toolName: 'delete_goal',
        input: { goal_id: 7 },
    },
    {
        type: 'tool-approval-request',
        toolCallId: 'call-1',
        approvalId: 'call-1',
        reason: 'This deletes the goal "Write a book".',
    },
    { type: 'finish-step' },
    { type: 'finish', finishReason: 'tool-calls' },
];

const completedDeletion = [
    { type: 'start', messageId: 'assistant-1' },
    { type: 'start-step' },
    {
        type: 'tool-output-available',
        toolCallId: 'call-1',
        output: 'Goal deleted.',
    },
    { type: 'finish-step' },
    { type: 'finish', finishReason: 'stop' },
];

const fetchMock = vi.fn();
const requestBody = (call: number) =>
    JSON.parse(fetchMock.mock.calls[call][1].body);

const makeChat = (goalId: number | null = null) =>
    effectScope().run(() => useAssistantChat(() => goalId))!;

beforeEach(() => {
    vi.stubGlobal('fetch', fetchMock);
    document.cookie = 'XSRF-TOKEN=token%3D123';
});

afterEach(() => {
    fetchMock.mockReset();
    vi.mocked(router.reload).mockReset();
    vi.unstubAllGlobals();
});

describe('tool activity', () => {
    it('sorts tools into reading, writing and deleting', () => {
        expect(toolActivityKind({ type: 'tool-list_goals' })).toBe('reading');
        expect(toolActivityKind({ type: 'tool-get_goal' })).toBe('reading');
        expect(toolActivityKind({ type: 'tool-log_progress' })).toBe('writing');
        expect(toolActivityKind({ type: 'tool-delete_entry' })).toBe(
            'deleting',
        );
    });

    it('only counts a finished write as a data change', () => {
        const message = (type: string, state: string) =>
            ({ id: '1', role: 'assistant', parts: [{ type, state }] }) as any;

        expect(
            messageChangedData(message('tool-list_goals', 'output-available')),
        ).toBe(false);
        expect(
            messageChangedData(message('tool-log_progress', 'output-error')),
        ).toBe(false);
        expect(
            messageChangedData(
                message('tool-log_progress', 'output-available'),
            ),
        ).toBe(true);
    });
});

describe('useAssistantChat', () => {
    it('sends only the new message with the csrf token and the goal on screen', async () => {
        fetchMock.mockResolvedValueOnce(stream(textReply('Hello')));
        const chat = makeChat(7);

        await chat.sendMessage({ text: 'How am I doing?' });

        const [url, init] = fetchMock.mock.calls[0];
        expect(url).toBe('/assistant/chat');
        expect(new Headers(init.headers).get('X-XSRF-TOKEN')).toBe('token=123');
        expect(requestBody(0)).toMatchObject({
            conversation_id: null,
            goal_id: 7,
        });
        expect(requestBody(0).messages).toHaveLength(1);
        expect(requestBody(0).messages[0].parts[0].text).toBe(
            'How am I doing?',
        );
    });

    it('continues the conversation the server opened', async () => {
        fetchMock
            .mockResolvedValueOnce(stream(textReply('Hello')))
            .mockResolvedValueOnce(stream(textReply('Again')));
        const chat = makeChat();

        await chat.sendMessage({ text: 'First' });
        await chat.sendMessage({ text: 'Second' });

        expect(chat.conversationId.value).toBe('conversation-1');
        expect(requestBody(1).conversation_id).toBe('conversation-1');
        expect(requestBody(1).messages).toHaveLength(1);
        expect(chat.messages.value).toHaveLength(4);
        expect(router.reload).not.toHaveBeenCalled();
    });

    it('pauses a deletion for approval, then posts the decision and reloads the page', async () => {
        fetchMock
            .mockResolvedValueOnce(stream(pausedDeletion))
            .mockResolvedValueOnce(stream(completedDeletion));
        const chat = makeChat();

        await chat.sendMessage({ text: 'Delete my book goal' });

        const paused: any = chat.messages.value
            .at(-1)!
            .parts.find((part) => part.type === 'tool-delete_goal');
        expect(paused.state).toBe('approval-requested');
        expect(paused.approval.id).toBe('call-1');
        expect(paused.approval.requestReason).toBe(
            'This deletes the goal "Write a book".',
        );
        expect(fetchMock).toHaveBeenCalledTimes(1);

        await chat.addToolApprovalResponse({ id: 'call-1', approved: true });
        await vi.waitFor(() => expect(router.reload).toHaveBeenCalledTimes(1));

        const decision = requestBody(1);
        expect(decision.conversation_id).toBe('conversation-1');
        expect(decision.messages).toHaveLength(1);
        expect(decision.messages[0].role).toBe('assistant');
        expect(decision.messages[0].parts.at(-1)).toMatchObject({
            type: 'tool-delete_goal',
            toolCallId: 'call-1',
            state: 'approval-responded',
            approval: { id: 'call-1', approved: true },
        });

        const settled: any = chat.messages.value
            .at(-1)!
            .parts.find((part) => part.type === 'tool-delete_goal');
        expect(settled.state).toBe('output-available');
    });

    it('opens the latest stored conversation', async () => {
        const messages = [
            {
                id: 'stored-1',
                role: 'user',
                parts: [{ type: 'text', text: 'Earlier question' }],
            },
        ];
        fetchMock
            .mockResolvedValueOnce(
                json({
                    conversations: [
                        { id: 'newest', title: 'Newest', updated_at: '' },
                        { id: 'older', title: 'Older', updated_at: '' },
                    ],
                }),
            )
            .mockResolvedValueOnce(json({ id: 'newest', messages }));
        const chat = makeChat();

        await chat.openLatestConversation();

        expect(fetchMock.mock.calls[1][0]).toBe(
            '/assistant/conversations/newest',
        );
        expect(chat.conversationId.value).toBe('newest');
        expect(chat.messages.value).toEqual(messages);
        expect(chat.conversations.value).toHaveLength(2);
    });

    it('starts a new conversation with an empty panel', async () => {
        fetchMock.mockResolvedValueOnce(stream(textReply('Hello')));
        const chat = makeChat();
        await chat.sendMessage({ text: 'First' });

        chat.startNewConversation();

        expect(chat.conversationId.value).toBeNull();
        expect(chat.messages.value).toEqual([]);
    });
});

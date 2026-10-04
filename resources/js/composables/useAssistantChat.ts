import { chat as chatRoute } from '@/routes/assistant';
import {
    show as conversationRoute,
    index as conversationsRoute,
    destroy as deleteConversationRoute,
    update as renameConversationRoute,
} from '@/routes/assistant/conversations';
import { useChat } from '@ai-sdk/vue';
import { router } from '@inertiajs/vue3';
import {
    DefaultChatTransport,
    lastAssistantMessageIsCompleteWithApprovalResponses,
    type UIMessage,
} from 'ai';
import { ref } from 'vue';

export interface ConversationSummary {
    id: string;
    title: string;
    updated_at: string;
}

export type ToolActivityKind = 'reading' | 'writing' | 'deleting';

export const isToolPart = (part: { type: string }): boolean =>
    part.type.startsWith('tool-');

export const toolActivityKind = (part: { type: string }): ToolActivityKind => {
    const toolName = part.type.slice('tool-'.length);

    if (/^(list|get)_/.test(toolName)) {
        return 'reading';
    }

    return toolName.startsWith('delete_') ? 'deleting' : 'writing';
};

export const messageChangedData = (message: UIMessage): boolean =>
    message.parts.some(
        (part: any) =>
            isToolPart(part) &&
            toolActivityKind(part) !== 'reading' &&
            part.state === 'output-available',
    );

const xsrfToken = (): string =>
    decodeURIComponent(
        document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
    );

const requestJson = async <T>(
    url: string,
    method: 'GET' | 'PATCH' | 'DELETE' = 'GET',
    body?: Record<string, unknown>,
): Promise<T> => {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            ...(method === 'GET'
                ? {}
                : {
                      'Content-Type': 'application/json',
                      'X-XSRF-TOKEN': xsrfToken(),
                  }),
        },
        credentials: 'same-origin',
        body: body ? JSON.stringify(body) : undefined,
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}`);
    }

    return response.status === 204 ? (undefined as T) : response.json();
};

export function useAssistantChat(currentGoalId: () => number | null) {
    const conversationId = ref<string | null>(null);
    const conversations = ref<ConversationSummary[]>([]);
    const isLoadingConversation = ref(false);

    const transport = new DefaultChatTransport({
        api: chatRoute.url(),
        fetch: async (input, init) => {
            const response = await fetch(input, init);

            conversationId.value =
                response.headers.get('X-Conversation-Id') ??
                conversationId.value;

            return response;
        },
        prepareSendMessagesRequest: ({ messages }) => ({
            headers: { 'X-XSRF-TOKEN': xsrfToken() },
            body: {
                messages: messages.slice(-1),
                conversation_id: conversationId.value,
                goal_id: currentGoalId(),
            },
        }),
    });

    const chat = useChat({
        transport,
        sendAutomaticallyWhen:
            lastAssistantMessageIsCompleteWithApprovalResponses,
        onFinish: ({ message }) => {
            if (messageChangedData(message)) {
                router.reload();
            }
        },
    });

    const startNewConversation = (): void => {
        chat.clearError();
        conversationId.value = null;
        chat.messages.value = [];
    };

    const loadConversations = async (): Promise<void> => {
        const { conversations: listed } = await requestJson<{
            conversations: ConversationSummary[];
        }>(conversationsRoute.url());

        conversations.value = listed;
    };

    const openConversation = async (id: string): Promise<void> => {
        isLoadingConversation.value = true;

        try {
            const conversation = await requestJson<{
                id: string;
                messages: UIMessage[];
            }>(conversationRoute.url(id));

            chat.clearError();
            conversationId.value = conversation.id;
            chat.messages.value = conversation.messages;
        } finally {
            isLoadingConversation.value = false;
        }
    };

    const openLatestConversation = async (): Promise<void> => {
        await loadConversations();

        if (conversations.value.length > 0) {
            await openConversation(conversations.value[0].id);
        }
    };

    const renameConversation = async (
        id: string,
        title: string,
    ): Promise<void> => {
        const renamed = await requestJson<{ id: string; title: string }>(
            renameConversationRoute.url(id),
            'PATCH',
            { title },
        );

        conversations.value = conversations.value.map((conversation) =>
            conversation.id === id
                ? { ...conversation, title: renamed.title }
                : conversation,
        );
    };

    const deleteConversation = async (id: string): Promise<void> => {
        await requestJson<void>(deleteConversationRoute.url(id), 'DELETE');

        conversations.value = conversations.value.filter(
            (conversation) => conversation.id !== id,
        );

        if (conversationId.value === id) {
            startNewConversation();
        }
    };

    return {
        ...chat,
        conversationId,
        conversations,
        isLoadingConversation,
        startNewConversation,
        loadConversations,
        openConversation,
        openLatestConversation,
        renameConversation,
        deleteConversation,
    };
}

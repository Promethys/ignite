<script setup lang="ts">
import AssistantMarkdown from '@/components/assistant/AssistantMarkdown.vue';
import AssistantToolActivity from '@/components/assistant/AssistantToolActivity.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { isToolPart, useAssistantChat } from '@/composables/useAssistantChat';
import type { AppPageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import {
    ArrowUp,
    History,
    LoaderCircle,
    Sparkles,
    SquarePen,
} from 'lucide-vue-next';
import moment from 'moment';
import { computed, nextTick, ref, watch } from 'vue';

const MAX_PROMPT_LENGTH = 4000;

const page = usePage<AppPageProps & { goal?: { id: number } }>();

const {
    messages,
    status,
    error,
    sendMessage,
    addToolApprovalResponse,
    clearError,
    conversationId,
    conversations,
    isLoadingConversation,
    startNewConversation,
    loadConversations,
    openConversation,
    openLatestConversation,
} = useAssistantChat(() => page.props.goal?.id ?? null);

const isOpen = ref(false);
const hasOpened = ref(false);
const isShowingHistory = ref(false);
const prompt = ref('');
const messageList = ref<HTMLElement | null>(null);

const isBusy = computed(
    () => status.value === 'submitted' || status.value === 'streaming',
);
const RUNNING_TOOL_STATES = [
    'input-streaming',
    'input-available',
    'approval-responded',
];
const isThinking = computed(() => {
    if (!isBusy.value) {
        return false;
    }

    const lastMessage = messages.value.at(-1);
    const lastPart: any = lastMessage?.parts.at(-1);

    if (!lastMessage || lastMessage.role === 'user' || !lastPart) {
        return true;
    }

    if (lastPart.type === 'text') {
        return lastPart.text === '';
    }

    return !(
        isToolPart(lastPart) && RUNNING_TOOL_STATES.includes(lastPart.state)
    );
});
const isWaitingForApproval = computed(() =>
    messages.value.some((message) =>
        message.parts.some(
            (part: any) =>
                isToolPart(part) && part.state === 'approval-requested',
        ),
    ),
);
const canSend = computed(
    () =>
        prompt.value.trim() !== '' &&
        !isBusy.value &&
        !isWaitingForApproval.value,
);

watch(isOpen, (open) => {
    if (open && !hasOpened.value) {
        hasOpened.value = true;
        void openLatestConversation();
    }
});

watch(
    [messages, status],
    async () => {
        await nextTick();
        if (messageList.value) {
            messageList.value.scrollTop = messageList.value.scrollHeight;
        }
    },
    { deep: true },
);

const send = (): void => {
    if (!canSend.value) {
        return;
    }

    clearError();
    void sendMessage({ text: prompt.value.trim() });
    prompt.value = '';
};

const newChat = (): void => {
    startNewConversation();
    isShowingHistory.value = false;
};

const toggleHistory = async (): Promise<void> => {
    isShowingHistory.value = !isShowingHistory.value;

    if (isShowingHistory.value) {
        await loadConversations();
    }
};

const reopen = async (id: string): Promise<void> => {
    isShowingHistory.value = false;
    await openConversation(id);
};
</script>

<template>
    <Sheet v-if="page.props.assistant?.available" v-model:open="isOpen">
        <TooltipProvider :delay-duration="0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <SheetTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-8"
                            data-test="assistant-open"
                        >
                            <Sparkles class="size-4" />
                            <span class="sr-only">{{
                                $t('assistant.open')
                            }}</span>
                        </Button>
                    </SheetTrigger>
                </TooltipTrigger>
                <TooltipContent>{{ $t('assistant.title') }}</TooltipContent>
            </Tooltip>
        </TooltipProvider>
        <SheetContent class="w-full gap-0 sm:max-w-md">
            <SheetHeader class="border-b p-4">
                <SheetTitle>{{ $t('assistant.title') }}</SheetTitle>
                <SheetDescription class="sr-only">
                    {{ $t('assistant.description') }}
                </SheetDescription>
                <div class="flex gap-2 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        data-test="assistant-new-chat"
                        @click="newChat"
                    >
                        <SquarePen class="size-4" />
                        {{ $t('assistant.new_chat') }}
                    </Button>
                    <Button
                        type="button"
                        :variant="isShowingHistory ? 'secondary' : 'outline'"
                        size="sm"
                        data-test="assistant-history"
                        @click="toggleHistory"
                    >
                        <History class="size-4" />
                        {{ $t('assistant.history') }}
                    </Button>
                </div>
            </SheetHeader>

            <ul
                v-if="isShowingHistory"
                class="flex-1 divide-y overflow-y-auto"
                data-test="assistant-conversations"
            >
                <li
                    v-if="conversations.length === 0"
                    class="p-4 text-sm text-muted-foreground"
                >
                    {{ $t('assistant.no_history') }}
                </li>
                <li
                    v-for="conversation in conversations"
                    :key="conversation.id"
                >
                    <button
                        type="button"
                        class="flex w-full flex-col gap-1 p-4 text-left text-sm hover:bg-accent"
                        :class="{
                            'bg-accent': conversation.id === conversationId,
                        }"
                        @click="reopen(conversation.id)"
                    >
                        <span class="truncate font-medium">
                            {{ conversation.title }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ moment(conversation.updated_at).format('LL') }}
                        </span>
                    </button>
                </li>
            </ul>

            <template v-else>
                <div
                    ref="messageList"
                    class="flex-1 space-y-4 overflow-y-auto p-4"
                    data-test="assistant-messages"
                >
                    <p
                        v-if="isLoadingConversation"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <LoaderCircle class="size-4 animate-spin" />
                        {{ $t('assistant.loading') }}
                    </p>
                    <p
                        v-else-if="messages.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{ $t('assistant.empty') }}
                    </p>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex flex-col gap-2"
                        :class="
                            message.role === 'user'
                                ? 'items-end'
                                : 'items-start'
                        "
                    >
                        <template
                            v-for="(part, index) in message.parts"
                            :key="index"
                        >
                            <template v-if="part.type === 'text' && part.text">
                                <p
                                    v-if="message.role === 'user'"
                                    class="max-w-[85%] bg-primary px-3 py-2 text-sm break-words whitespace-pre-wrap text-primary-foreground"
                                >
                                    {{ part.text }}
                                </p>
                                <AssistantMarkdown v-else :text="part.text" />
                            </template>
                            <AssistantToolActivity
                                v-else-if="isToolPart(part)"
                                :part="part"
                                class="w-full"
                                @decide="
                                    (id, approved) =>
                                        addToolApprovalResponse({
                                            id,
                                            approved,
                                        })
                                "
                            />
                        </template>
                    </div>

                    <p
                        v-if="isThinking"
                        data-test="assistant-thinking"
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <LoaderCircle class="size-3 animate-spin" />
                        {{ $t('assistant.thinking') }}
                    </p>

                    <Alert v-if="error" variant="destructive">
                        <AlertDescription>
                            {{ $t('assistant.error') }}
                        </AlertDescription>
                    </Alert>
                </div>

                <form
                    class="flex items-end gap-2 border-t p-4"
                    @submit.prevent="send"
                >
                    <Textarea
                        v-model="prompt"
                        class="max-h-40 min-h-10 resize-none"
                        rows="2"
                        :maxlength="MAX_PROMPT_LENGTH"
                        :placeholder="$t('assistant.placeholder')"
                        :aria-label="$t('assistant.placeholder')"
                        data-test="assistant-prompt"
                        @keydown.enter.exact.prevent="send"
                    />
                    <Button
                        type="submit"
                        size="icon"
                        :disabled="!canSend"
                        data-test="assistant-send"
                    >
                        <ArrowUp class="size-4" />
                        <span class="sr-only">{{ $t('assistant.send') }}</span>
                    </Button>
                </form>
            </template>
        </SheetContent>
    </Sheet>
</template>

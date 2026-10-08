<script setup lang="ts">
import CharacterCounter from '@/components/CharacterCounter.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ConversationSummary } from '@/composables/useAssistantChat';
import { FIELD_LIMITS } from '@/lib/field-limits';
import { Check, Pencil, Trash, X } from 'lucide-vue-next';
import moment from 'moment';
import { ref } from 'vue';

const props = defineProps<{
    conversations: ConversationSummary[];
    activeConversationId: string | null;
    rename: (id: string, title: string) => Promise<void>;
    remove: (id: string) => Promise<void>;
}>();

defineEmits<{
    open: [id: string];
}>();

const editedConversationId = ref<string | null>(null);
const editedTitle = ref('');
const hasFailed = ref(false);

const startRenaming = (conversation: ConversationSummary): void => {
    editedConversationId.value = conversation.id;
    editedTitle.value = conversation.title;
};

const attempt = async (action: () => Promise<void>): Promise<void> => {
    hasFailed.value = false;

    try {
        await action();
    } catch {
        hasFailed.value = true;
    }
};

const saveTitle = async (): Promise<void> => {
    const id = editedConversationId.value;
    const title = editedTitle.value.trim();

    if (id === null || title === '') {
        return;
    }

    await attempt(async () => {
        await props.rename(id, title);
        editedConversationId.value = null;
    });
};
</script>

<template>
    <div class="flex-1 overflow-y-auto" data-test="assistant-conversations">
        <p
            v-if="hasFailed"
            class="border-b p-4 text-sm text-destructive"
            role="alert"
        >
            {{ $t('assistant.action_failed') }}
        </p>
        <p
            v-if="conversations.length === 0"
            class="p-4 text-sm text-muted-foreground"
        >
            {{ $t('assistant.no_history') }}
        </p>
        <ul class="divide-y">
            <li
                v-for="conversation in conversations"
                :key="conversation.id"
                class="flex items-center gap-1 pr-2"
                :class="{
                    'bg-accent': conversation.id === activeConversationId,
                }"
            >
                <form
                    v-if="editedConversationId === conversation.id"
                    class="flex flex-1 items-center gap-1 p-2"
                    data-test="assistant-rename-form"
                    @submit.prevent="saveTitle"
                >
                    <div class="flex-1 space-y-1">
                        <Input
                            v-model="editedTitle"
                            :maxlength="FIELD_LIMITS.conversationTitle"
                            :aria-label="$t('assistant.title_label')"
                            data-test="assistant-rename-input"
                            @keydown.esc.stop="editedConversationId = null"
                        />
                        <CharacterCounter
                            :value="editedTitle"
                            :max="FIELD_LIMITS.conversationTitle"
                        />
                    </div>
                    <Button
                        type="submit"
                        variant="ghost"
                        size="icon"
                        class="size-8"
                        :disabled="editedTitle.trim() === ''"
                    >
                        <Check class="size-4" />
                        <span class="sr-only">
                            {{ $t('common.actions.save') }}
                        </span>
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-8"
                        @click="editedConversationId = null"
                    >
                        <X class="size-4" />
                        <span class="sr-only">
                            {{ $t('common.actions.cancel') }}
                        </span>
                    </Button>
                </form>
                <template v-else>
                    <button
                        type="button"
                        class="flex min-w-0 flex-1 flex-col gap-1 p-4 text-left text-sm hover:bg-accent"
                        data-test="assistant-conversation"
                        @click="$emit('open', conversation.id)"
                    >
                        <span class="truncate font-medium">
                            {{ conversation.title }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ moment(conversation.updated_at).format('LL') }}
                        </span>
                    </button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="size-8"
                        data-test="assistant-rename"
                        @click="startRenaming(conversation)"
                    >
                        <Pencil class="size-4" />
                        <span class="sr-only">{{
                            $t('assistant.rename')
                        }}</span>
                    </Button>
                    <AlertDialog>
                        <AlertDialogTrigger as-child>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-8 text-destructive"
                                data-test="assistant-delete"
                            >
                                <Trash class="size-4" />
                                <span class="sr-only">
                                    {{ $t('common.actions.delete') }}
                                </span>
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    {{ $t('assistant.delete_title') }}
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    {{ $t('assistant.delete_description') }}
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>
                                    {{ $t('common.actions.cancel') }}
                                </AlertDialogCancel>
                                <AlertDialogAction
                                    variant="destructive"
                                    data-test="assistant-delete-confirm"
                                    @click="
                                        attempt(() => remove(conversation.id))
                                    "
                                >
                                    {{ $t('common.actions.delete') }}
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </template>
            </li>
        </ul>
    </div>
</template>

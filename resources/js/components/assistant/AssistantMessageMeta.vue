<script setup lang="ts">
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useClipboard } from '@vueuse/core';
import { Check, Copy } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    text: string;
    sentAt?: string;
    locale: string;
    isReversed?: boolean;
}>();

const { copy, copied, isSupported } = useClipboard();

const canCopy = computed(() => isSupported.value && props.text !== '');
const formattedDate = computed(() =>
    props.sentAt
        ? new Intl.DateTimeFormat(props.locale, {
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
          }).format(new Date(props.sentAt))
        : null,
);
</script>

<template>
    <div
        v-if="canCopy || formattedDate"
        class="flex items-center gap-2 text-xs text-muted-foreground"
        :class="{ 'flex-row-reverse': isReversed }"
        data-test="assistant-message-meta"
    >
        <TooltipProvider v-if="canCopy" :delay-duration="0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        type="button"
                        class="hover:text-foreground"
                        :aria-label="
                            $t(copied ? 'assistant.copied' : 'assistant.copy')
                        "
                        data-test="assistant-copy"
                        @click="copy(text)"
                    >
                        <Check v-if="copied" class="size-3.5" />
                        <Copy v-else class="size-3.5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent>
                    {{ $t(copied ? 'assistant.copied' : 'assistant.copy') }}
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
        <span v-if="canCopy && formattedDate" aria-hidden="true">•</span>
        <time v-if="formattedDate" :datetime="sentAt">{{ formattedDate }}</time>
    </div>
</template>

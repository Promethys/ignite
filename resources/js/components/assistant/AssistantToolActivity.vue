<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { toolActivityKind } from '@/composables/useAssistantChat';
import { Check, CircleAlert, LoaderCircle, X } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    part: any;
}>();

defineEmits<{
    decide: [approvalId: string, approved: boolean];
}>();

const kind = computed(() => toolActivityKind(props.part));
const approvalPreview = computed(
    () => props.part.approval?.requestReason ?? props.part.approval?.reason,
);
const isRunning = computed(() =>
    ['input-streaming', 'input-available', 'approval-responded'].includes(
        props.part.state,
    ),
);
</script>

<template>
    <div
        v-if="part.state === 'approval-requested'"
        class="space-y-3 border bg-card p-3 text-sm"
        data-test="assistant-approval"
    >
        <p class="font-medium">{{ $t('assistant.approval.title') }}</p>
        <p
            v-if="approvalPreview"
            class="whitespace-pre-wrap text-muted-foreground"
        >
            {{ approvalPreview }}
        </p>
        <div class="flex justify-end gap-2">
            <Button
                type="button"
                variant="outline"
                size="sm"
                data-test="assistant-reject"
                @click="$emit('decide', part.approval.id, false)"
            >
                {{ $t('assistant.approval.reject') }}
            </Button>
            <Button
                type="button"
                variant="destructive"
                size="sm"
                data-test="assistant-approve"
                @click="$emit('decide', part.approval.id, true)"
            >
                {{ $t('assistant.approval.approve') }}
            </Button>
        </div>
    </div>
    <p
        v-else
        class="flex items-center gap-2 text-xs text-muted-foreground"
        data-test="assistant-activity"
    >
        <LoaderCircle v-if="isRunning" class="size-3 animate-spin" />
        <CircleAlert v-else-if="part.state === 'output-error'" class="size-3" />
        <X v-else-if="part.state === 'output-denied'" class="size-3" />
        <Check v-else class="size-3" />
        <span v-if="isRunning">{{ $t('assistant.activity.running') }}</span>
        <span v-else-if="part.state === 'output-error'">
            {{ $t('assistant.activity.failed') }}
        </span>
        <span v-else-if="part.state === 'output-denied'">
            {{ $t('assistant.approval.denied') }}
        </span>
        <span v-else>{{ $t(`assistant.activity.${kind}`) }}</span>
    </p>
</template>

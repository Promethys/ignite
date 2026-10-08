<script setup lang="ts">
import { renderAssistantMarkdown } from '@/lib/assistant-markdown';
import { computed } from 'vue';

const props = defineProps<{
    text: string;
}>();

const html = computed(() => renderAssistantMarkdown(props.text));
</script>

<template>
    <!-- eslint-disable vue/no-v-html -->
    <div
        class="max-w-full space-y-2 text-sm break-words [&_a]:underline [&_blockquote]:border-l [&_blockquote]:pl-3 [&_blockquote]:text-muted-foreground [&_code]:bg-muted [&_code]:px-1 [&_code]:text-xs [&_h1]:font-semibold [&_h2]:font-semibold [&_h3]:font-semibold [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_pre]:overflow-x-auto [&_pre]:bg-muted [&_pre]:p-2 [&_pre_code]:px-0 [&_table]:block [&_table]:overflow-x-auto [&_td]:border [&_td]:px-2 [&_td]:py-1 [&_th]:border [&_th]:px-2 [&_th]:py-1 [&_th]:text-left [&_ul]:list-disc [&_ul]:pl-5"
        data-test="assistant-markdown"
        v-html="html"
    />
</template>

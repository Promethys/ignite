<script setup lang="ts">
import ExternalLink from '@/components/public/ExternalLink.vue';
defineProps<{ rawUrl: string; editUrl: string }>();
</script>

<template>
    <div
        class="flex flex-wrap gap-x-6 gap-y-2 border-t pt-5 text-sm text-muted-foreground"
    >
        <ExternalLink
            :href="rawUrl"
            class="inline-flex min-h-11 items-center underline underline-offset-4 hover:text-foreground"
            >{{ $t('public.docs.view_markdown') }}</ExternalLink
        >
        <ExternalLink
            :href="editUrl"
            class="inline-flex min-h-11 items-center underline underline-offset-4 hover:text-foreground"
            >{{ $t('public.docs.edit_page') }}</ExternalLink
        >
    </div>
</template>

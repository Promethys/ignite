<script setup lang="ts">
import { MoveUpRight } from 'lucide-vue-next';

defineProps<{ href: string }>();
</script>

<template>
    <a
        :href="href"
        target="_blank"
        rel="noopener"
        class="inline-flex items-center"
    >
        <slot /><MoveUpRight
            class="ml-1.5 size-3 shrink-0"
            aria-hidden="true"
        />
        <span class="sr-only"> {{ $t('public.opens_in_new_tab') }}</span>
    </a>
</template>

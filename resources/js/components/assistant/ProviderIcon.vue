<script setup lang="ts">
import Anthropic from '@/components/ui/icon/Anthropic.vue';
import DeepSeek from '@/components/ui/icon/DeepSeek.vue';
import Gemini from '@/components/ui/icon/Gemini.vue';
import Groq from '@/components/ui/icon/Groq.vue';
import Mistral from '@/components/ui/icon/Mistral.vue';
import OpenAi from '@/components/ui/icon/OpenAi.vue';
import OpenRouter from '@/components/ui/icon/OpenRouter.vue';
import Xai from '@/components/ui/icon/Xai.vue';
import { computed, type Component } from 'vue';

const props = defineProps<{
    provider: string;
    label: string;
}>();

const logos: Record<string, { component: Component; class: string }> = {
    anthropic: { component: Anthropic, class: 'size-5' },
    deepseek: { component: DeepSeek, class: 'size-5' },
    gemini: { component: Gemini, class: 'size-5' },
    groq: { component: Groq, class: 'h-5 w-auto' },
    mistral: { component: Mistral, class: 'size-5' },
    openai: { component: OpenAi, class: 'size-5' },
    openrouter: { component: OpenRouter, class: 'h-auto w-6' },
    xai: { component: Xai, class: 'h-auto w-8' },
};

const logo = computed(() => logos[props.provider]);
</script>

<template>
    <span class="flex size-8 items-center justify-center" aria-hidden="true">
        <component :is="logo.component" v-if="logo" :class="logo.class" />
        <span
            v-else
            class="flex size-6 items-center justify-center border bg-muted text-xs font-semibold text-muted-foreground"
        >
            {{ label.charAt(0).toUpperCase() }}
        </span>
    </span>
</template>

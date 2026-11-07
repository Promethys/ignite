<script setup lang="ts">
import { quantifiableGoal } from '@/lib/public-showcase';
import { trans } from 'laravel-vue-i18n';
import { RotateCcw } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    client?: string;
    prompt?: string;
    tool?: string;
    args?: string;
    result?: string;
    reply?: string;
}>();
const exchange = computed(() => ({
    client: props.client ?? trans('public.terminal.client'),
    prompt: props.prompt ?? trans('public.terminal.prompt'),
    tool: props.tool ?? 'log_progress',
    args: props.args ?? '{ goal_id: 12, increment: 12 }',
    result:
        props.result ??
        trans('public.terminal.result', {
            current: String(quantifiableGoal.current_value),
            target: String(quantifiableGoal.target_value ?? 0),
            unit: quantifiableGoal.unit ?? '',
            progress: String(quantifiableGoal.progress_percentage),
        }),
    reply:
        props.reply ??
        trans('public.terminal.reply', {
            current: String(quantifiableGoal.current_value),
            target: String(quantifiableGoal.target_value ?? 0),
            unit: quantifiableGoal.unit ?? '',
        }),
}));
const terminal = ref<HTMLElement | null>(null);
const started = ref(false);
const reducedMotion = ref(false);
const run = ref(0);
const duration = computed(() =>
    Math.max(1.2, exchange.value.prompt.length * 0.05),
);
let observer: IntersectionObserver | undefined;
let preference: MediaQueryList | undefined;
const syncMotion = () => {
    reducedMotion.value = preference?.matches ?? false;
    if (reducedMotion.value) started.value = true;
};
const replay = () => {
    started.value = true;
    run.value += 1;
};

onMounted(() => {
    preference = window.matchMedia('(prefers-reduced-motion: reduce)');
    syncMotion();
    preference.addEventListener('change', syncMotion);
    if (started.value) return;
    if (typeof IntersectionObserver === 'undefined') {
        started.value = true;
        return;
    }
    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                started.value = true;
                observer?.disconnect();
            }
        },
        { threshold: 0.2 },
    );
    if (terminal.value) observer.observe(terminal.value);
});
onUnmounted(() => {
    observer?.disconnect();
    preference?.removeEventListener('change', syncMotion);
});
</script>

<template>
    <figure
        ref="terminal"
        :data-started="started"
        :data-reduced-motion="reducedMotion"
        :data-run="run"
        :aria-label="`${exchange.client}: ${exchange.prompt}. ${exchange.tool} ${exchange.args}. ${exchange.result}. ${exchange.reply}`"
        class="public-terminal -m-5 bg-[var(--terminal)] text-[13px] leading-relaxed sm:-m-6"
    >
        <div class="flex items-center justify-between gap-3 border-b px-4 py-2">
            <span class="text-xs text-muted-foreground">{{
                exchange.client
            }}</span>
            <button
                type="button"
                class="inline-flex min-h-11 items-center gap-2 text-xs text-muted-foreground hover:text-foreground"
                @click="replay"
            >
                <RotateCcw class="size-3.5" aria-hidden="true" />{{
                    $t('public.terminal.replay')
                }}
            </button>
        </div>
        <div
            :key="run"
            class="space-y-4 p-5"
            :class="{ 'terminal-running': started }"
            :style="{
                '--type-duration': `${duration}s`,
                '--type-steps': exchange.prompt.length,
            }"
            aria-hidden="true"
        >
            <div class="flex items-start gap-2">
                <span class="text-[var(--primary-text)]">›</span
                ><span class="terminal-type break-words">{{
                    exchange.prompt
                }}</span
                ><span class="terminal-caret text-[var(--primary-text)]"
                    >▏</span
                >
            </div>
            <div
                class="terminal-reveal border bg-card px-3 py-2"
                :style="{ '--reveal-delay': `${duration + 0.5}s` }"
            >
                <p class="text-[var(--primary-text)]">{{ exchange.tool }}</p>
                <p class="mt-1 break-words text-muted-foreground">
                    {{ exchange.args }}
                </p>
            </div>
            <p
                class="terminal-reveal text-success"
                :style="{ '--reveal-delay': `${duration + 1.3}s` }"
            >
                ✓ {{ exchange.result }}
            </p>
            <p
                class="terminal-reveal font-sans text-sm"
                :style="{ '--reveal-delay': `${duration + 2}s` }"
            >
                {{ exchange.reply }}
            </p>
        </div>
    </figure>
</template>

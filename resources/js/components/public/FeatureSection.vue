<script setup lang="ts">
import ExternalLink from '@/components/public/ExternalLink.vue';
import { ArrowRight, Link2 } from 'lucide-vue-next';
withDefaults(
    defineProps<{
        id: string;
        index: number;
        title: string;
        description: string;
        points?: string[];
        docsHref?: string;
        reverse?: boolean;
    }>(),
    { reverse: false },
);
</script>

<template>
    <section
        :id="id"
        :aria-labelledby="`${id}-title`"
        class="scroll-mt-8 border-t py-14 first:border-t-0 first:pt-0 md:py-20"
    >
        <div
            class="grid items-center gap-8 xl:gap-10"
            :class="$slots.default ? 'xl:grid-cols-2' : ''"
        >
            <div class="min-w-0" :class="reverse ? 'xl:order-2' : ''">
                <p
                    class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
                >
                    {{ String(index).padStart(2, '0') }}
                </p>
                <h2
                    :id="`${id}-title`"
                    class="mt-3 font-display text-3xl leading-tight font-semibold text-balance"
                >
                    {{ title
                    }}<a
                        :href="`#${id}`"
                        :aria-label="$t('public.link_to', { title })"
                        class="ml-2 inline-flex min-h-11 min-w-11 items-center justify-center align-middle text-muted-foreground hover:text-foreground"
                        ><Link2 class="size-5" aria-hidden="true"
                    /></a>
                </h2>
                <p class="mt-4 leading-relaxed text-muted-foreground">
                    {{ description }}
                </p>
                <ul v-if="points?.length" class="mt-5 space-y-3 text-sm">
                    <li v-for="point in points" :key="point" class="flex gap-3">
                        <span
                            class="mt-2 size-1.5 shrink-0 bg-primary"
                            aria-hidden="true"
                        />{{ point }}
                    </li>
                </ul>
                <component
                    :is="
                        /^https?:|^\/\//.test(docsHref ?? '')
                            ? ExternalLink
                            : 'a'
                    "
                    v-if="docsHref"
                    :href="docsHref"
                    class="mt-4 inline-flex min-h-11 items-center gap-2 text-sm text-[var(--primary-text)] underline-offset-4 hover:underline"
                    >{{ $t('public.read_docs')
                    }}<ArrowRight
                        v-if="!/^https?:|^\/\//.test(docsHref)"
                        class="size-4"
                        aria-hidden="true"
                /></component>
            </div>
            <div
                v-if="$slots.default"
                class="min-w-0"
                :class="reverse ? 'xl:order-1' : ''"
            >
                <slot />
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
import { useScrollSpy } from '@/composables/useScrollSpy';
const props = defineProps<{
    items: { id: string; label: string }[];
    label?: string;
}>();
const active = useScrollSpy(() => props.items.map((item) => item.id));
</script>

<template>
    <nav
        :aria-label="label ?? $t('public.on_this_page')"
        class="min-w-0 self-start lg:sticky lg:top-8"
    >
        <p
            class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
        >
            {{ label ?? $t('public.on_this_page') }}
        </p>
        <ol
            class="mt-4 flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:gap-0 lg:overflow-visible"
        >
            <li
                v-for="(item, index) in items"
                :key="item.id"
                class="shrink-0 lg:shrink"
            >
                <a
                    :href="`#${item.id}`"
                    :aria-current="active === item.id ? 'location' : undefined"
                    class="relative flex min-h-11 items-center gap-3 border px-3 py-2 pr-5 text-sm text-muted-foreground after:absolute after:top-1/2 after:right-1 after:size-1.5 after:-translate-y-1/2 after:bg-current after:opacity-0 hover:text-foreground aria-[current=location]:border-primary aria-[current=location]:text-[var(--primary-text)] aria-[current=location]:after:opacity-100 lg:border-transparent lg:pl-0 lg:aria-[current=location]:border-transparent"
                    @click="active = item.id"
                    ><span
                        class="w-5 shrink-0 text-[var(--primary-text)] tabular-nums"
                        >{{ String(index + 1).padStart(2, '0') }}</span
                    >{{ item.label }}</a
                >
            </li>
        </ol>
    </nav>
</template>

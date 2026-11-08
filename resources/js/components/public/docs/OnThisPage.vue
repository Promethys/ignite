<script setup lang="ts">
import { useScrollSpy } from '@/composables/useScrollSpy';
import type { GuideHeading } from './types';
const props = defineProps<{ headings: GuideHeading[] }>();
const active = useScrollSpy(() => props.headings.map((heading) => heading.id));
</script>

<template>
    <nav v-if="headings.length" :aria-label="$t('public.on_this_page')">
        <p
            class="text-xs font-semibold tracking-widest text-muted-foreground uppercase"
        >
            {{ $t('public.on_this_page') }}
        </p>
        <ol class="mt-4 border-l">
            <li v-for="heading in headings" :key="heading.id">
                <a
                    :href="`#${heading.id}`"
                    :aria-current="
                        active === heading.id ? 'location' : undefined
                    "
                    class="-ml-px block border-l-2 border-transparent py-2 pr-1 pl-4 text-sm text-muted-foreground hover:text-foreground aria-[current=location]:border-primary aria-[current=location]:text-[var(--primary-text)]"
                    :class="{ 'pl-7': heading.level === 3 }"
                    @click="active = heading.id"
                    >{{ heading.text }}</a
                >
            </li>
        </ol>
    </nav>
</template>

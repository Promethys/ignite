<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Search, X } from 'lucide-vue-next';
import { computed, ref, useId } from 'vue';
import type { GuideSearchPage } from './types';
const props = defineProps<{ searchIndex: GuideSearchPage[] }>();
const query = ref('');
const input = ref<HTMLInputElement | null>(null);
const clearSearch = () => {
    query.value = '';
    input.value?.focus();
};
const id = useId();
const results = computed(() => {
    const words = query.value.trim().toLocaleLowerCase().split(/\s+/);
    return props.searchIndex.flatMap((page) => {
        const text = [
            page.title,
            page.summary,
            page.text,
            ...page.headings.map((heading) => heading.text),
        ]
            .join(' ')
            .toLocaleLowerCase();
        if (!words.every((word) => text.includes(word))) return [];
        const heading = page.headings.find((heading) =>
            words.every((word) =>
                heading.text.toLocaleLowerCase().includes(word),
            ),
        );
        return [
            {
                ...page,
                url: heading ? `${page.url}#${heading.id}` : page.url,
                matchedHeading: heading?.text,
            },
        ];
    });
});
</script>

<template>
    <section :aria-label="$t('public.docs.search')">
        <label :for="id" class="sr-only">{{ $t('public.docs.search') }}</label>
        <div
            class="flex items-center border bg-background focus-within:border-primary"
        >
            <Search
                class="ml-3 size-4 shrink-0 text-muted-foreground"
                aria-hidden="true"
            />
            <input
                :id="id"
                ref="input"
                v-model="query"
                type="search"
                :placeholder="$t('public.docs.search_placeholder')"
                class="docs-search-input min-h-12 min-w-0 flex-1 bg-transparent px-3 text-sm outline-none"
                :aria-controls="`${id}-results`"
            />
            <button
                v-if="query"
                type="button"
                :aria-label="$t('public.docs.clear_search')"
                class="flex size-11 shrink-0 items-center justify-center text-muted-foreground hover:text-foreground"
                @click="clearSearch"
            >
                <X class="size-4" aria-hidden="true" />
            </button>
        </div>
        <div
            v-if="query.trim()"
            :id="`${id}-results`"
            class="mt-4"
            aria-live="polite"
        >
            <p class="text-sm text-muted-foreground">
                {{
                    $tChoice('public.docs.results', results.length, {
                        count: String(results.length),
                    })
                }}
            </p>
            <ul v-if="results.length" class="mt-3 divide-y border-y">
                <li v-for="result in results" :key="result.topic">
                    <Link
                        :href="result.url"
                        class="block py-4 hover:text-[var(--primary-text)]"
                        ><span class="font-semibold">{{ result.title }}</span>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ result.matchedHeading ?? result.summary }}
                        </p></Link
                    >
                </li>
            </ul>
            <p v-else class="border-b py-6 text-sm text-muted-foreground">
                {{ $t('public.docs.no_results') }}
            </p>
        </div>
    </section>
</template>

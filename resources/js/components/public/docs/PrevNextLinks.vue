<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight } from 'lucide-vue-next';
import type { GuidePage } from './types';
defineProps<{ previous: GuidePage | null; next: GuidePage | null }>();
</script>

<template>
    <nav
        v-if="previous || next"
        :aria-label="$t('public.docs.pagination')"
        class="grid gap-3 sm:grid-cols-2"
    >
        <Link
            v-if="previous"
            :href="previous.url"
            class="group border p-5 hover:border-primary"
            ><span class="flex items-center gap-2 text-xs text-muted-foreground"
                ><ArrowLeft class="size-3" aria-hidden="true" />{{
                    $t('public.docs.previous')
                }}</span
            ><span
                class="mt-2 block font-semibold group-hover:text-[var(--primary-text)]"
                >{{ previous.title }}</span
            ></Link
        >
        <Link
            v-if="next"
            :href="next.url"
            class="group border p-5 text-right hover:border-primary sm:col-start-2"
            ><span
                class="flex items-center justify-end gap-2 text-xs text-muted-foreground"
                >{{ $t('public.docs.next')
                }}<ArrowRight class="size-3" aria-hidden="true" /></span
            ><span
                class="mt-2 block font-semibold group-hover:text-[var(--primary-text)]"
                >{{ next.title }}</span
            ></Link
        >
    </nav>
</template>

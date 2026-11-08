<script setup lang="ts">
import PublicShell from '@/components/public/PublicShell.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { BookOpen, ChevronDown, X } from 'lucide-vue-next';
import { ref } from 'vue';
import DocsNav from './DocsNav.vue';
import OnThisPage from './OnThisPage.vue';
import type { GuideHeading, GuidePage } from './types';
defineProps<{
    pages: GuidePage[];
    currentTopic?: string;
    headings: GuideHeading[];
    title: string;
    description: string;
}>();
const open = ref(false);
</script>

<template>
    <PublicShell :title="title" :description="description">
        <main
            id="main"
            tabindex="-1"
            class="relative mx-auto grid w-full max-w-7xl flex-1 gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[180px_minmax(0,1fr)_180px] lg:gap-8 lg:py-12 xl:grid-cols-[200px_minmax(0,1fr)_190px] xl:gap-12"
        >
            <aside class="hidden min-w-0 lg:block">
                <div
                    class="sticky top-8 max-h-[calc(100vh-4rem)] overflow-y-auto pb-4"
                >
                    <DocsNav :pages="pages" :current-topic="currentTopic" />
                </div>
            </aside>
            <div class="min-w-0">
                <div class="mb-8 space-y-3 lg:hidden">
                    <Sheet v-model:open="open">
                        <SheetTrigger as-child
                            ><Button
                                variant="outline"
                                class="min-h-11 w-full justify-start"
                                ><BookOpen aria-hidden="true" />{{
                                    $t('public.docs.menu')
                                }}</Button
                            ></SheetTrigger
                        >
                        <SheetContent
                            side="left"
                            class="public-surface dark w-[85vw] overflow-y-auto bg-background text-foreground sm:max-w-sm [&>button:last-child]:hidden"
                        >
                            <SheetHeader
                                ><SheetTitle>{{
                                    $t('public.nav.docs')
                                }}</SheetTitle
                                ><SheetDescription class="sr-only">{{
                                    $t('public.docs.navigation')
                                }}</SheetDescription></SheetHeader
                            >
                            <SheetClose as-child
                                ><Button
                                    variant="ghost"
                                    size="icon"
                                    class="absolute top-3 right-3 size-11"
                                    :aria-label="$t('public.nav.close_menu')"
                                    ><X aria-hidden="true" /></Button
                            ></SheetClose>
                            <div class="px-5 pb-8">
                                <DocsNav
                                    :pages="pages"
                                    :current-topic="currentTopic"
                                    @navigate="open = false"
                                />
                            </div>
                        </SheetContent>
                    </Sheet>
                    <details
                        v-if="headings.length"
                        class="group border bg-background px-4"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between py-3 text-sm [&::-webkit-details-marker]:hidden"
                        >
                            {{ $t('public.on_this_page') }}
                            <ChevronDown
                                class="size-4 transition-transform group-open:rotate-180 motion-reduce:transition-none"
                                aria-hidden="true"
                            />
                        </summary>
                        <OnThisPage
                            :headings="headings"
                            class="pb-4 [&>p]:hidden"
                        />
                    </details>
                </div>
                <slot />
            </div>
            <aside class="hidden min-w-0 lg:block">
                <OnThisPage :headings="headings" class="sticky top-8" />
            </aside>
        </main>
    </PublicShell>
</template>

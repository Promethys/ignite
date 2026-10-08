<script setup lang="ts">
import Breadcrumbs from '@/components/app/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { AppPageProps, BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { defineAsyncComponent } from 'vue';

const AssistantPanel = defineAsyncComponent(
    () => import('@/components/assistant/AssistantPanel.vue'),
);

const page = usePage<AppPageProps>();

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="ml-auto">
            <AssistantPanel v-if="page.props.assistant?.available" />
        </div>
    </header>
</template>

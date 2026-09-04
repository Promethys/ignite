<script setup lang="ts">
import CategoryFormModal from '@/components/categories/CategoryFormModal.vue';
import DeleteCategoryDialog from '@/components/categories/DeleteCategoryDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import AppLayout from '@/layouts/AppLayout.vue';
import categories from '@/routes/categories';
import goals from '@/routes/goals';
import { BreadcrumbItem } from '@/types';
import { Category } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { Edit, Ellipsis, Plus, Tags, Trash } from 'lucide-vue-next';

interface Props {
    items: Category[];
    openCreate: boolean;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'categories.breadcrumb',
        href: categories.index().url,
    },
];

const filterUrl = (category: Category) =>
    `${goals.index().url}?category=${category.id}`;

const completion = (category: Category) => {
    const total = category.goals_count ?? 0;
    if (total === 0) return 0;
    return Math.round(((category.completed_goals_count ?? 0) / total) * 100);
};
</script>

<template>
    <Head :title="$t('categories.head')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader
                :title="$t('categories.title')"
                :description="
                    $tChoice('categories.subtitle', items.length, {
                        count: items.length.toString(),
                    })
                "
            >
                <template #actions>
                    <CategoryFormModal :open="openCreate" />
                </template>
            </PageHeader>

            <Empty v-if="items.length === 0">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Tags />
                    </EmptyMedia>
                    <EmptyTitle>{{ $t('categories.empty.title') }}</EmptyTitle>
                    <EmptyDescription>
                        {{ $t('categories.empty.description') }}
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent>
                    <CategoryFormModal :open="openCreate" />
                </EmptyContent>
            </Empty>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="category in items"
                    :key="category.id"
                    :href="filterUrl(category)"
                    class="block h-full"
                >
                    <div
                        class="flex h-full flex-col gap-3 rounded-lg border bg-card p-4 transition-colors hover:border-primary/40"
                    >
                        <!-- Head: name + controls -->
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="size-3 shrink-0 rounded-full"
                                    :style="{ backgroundColor: category.color }"
                                />
                                <span
                                    class="truncate font-display text-base font-semibold"
                                >
                                    {{ category.name }}
                                </span>
                            </div>
                            <div
                                class="flex shrink-0 items-center"
                                @click.stop.prevent
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8"
                                        >
                                            <Ellipsis />
                                            <span class="sr-only">{{
                                                $t('common.actions.more')
                                            }}</span>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuGroup>
                                            <CategoryFormModal
                                                :record="category"
                                            >
                                                <template #trigger>
                                                    <DropdownMenuItem
                                                        class="cursor-pointer"
                                                        @select.prevent
                                                    >
                                                        <Edit />
                                                        {{
                                                            $t(
                                                                'common.actions.edit',
                                                            )
                                                        }}
                                                    </DropdownMenuItem>
                                                </template>
                                            </CategoryFormModal>
                                            <DeleteCategoryDialog
                                                :record="category"
                                            >
                                                <template #trigger>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        class="cursor-pointer"
                                                        @select.prevent
                                                    >
                                                        <Trash />
                                                        {{
                                                            $t(
                                                                'common.actions.delete',
                                                            )
                                                        }}
                                                    </DropdownMenuItem>
                                                </template>
                                            </DeleteCategoryDialog>
                                        </DropdownMenuGroup>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>

                        <!-- Description (hidden when empty) -->
                        <p
                            v-if="category.description"
                            class="line-clamp-1 text-xs text-muted-foreground"
                        >
                            {{ category.description }}
                        </p>

                        <!-- Footer: counts + completion bar -->
                        <div class="mt-auto space-y-2 pt-2">
                            <div
                                class="flex gap-4 text-xs text-muted-foreground"
                            >
                                <span>
                                    <span class="font-semibold text-foreground">
                                        {{ category.goals_count ?? 0 }} </span
                                    >&nbsp;{{ $t('categories.counts.goals') }}
                                </span>
                                <span>
                                    <span class="font-semibold text-foreground">
                                        {{
                                            category.active_goals_count ?? 0
                                        }} </span
                                    >&nbsp;{{ $t('categories.counts.active') }}
                                </span>
                                <span>
                                    <span class="font-semibold text-foreground">
                                        {{
                                            category.completed_goals_count ?? 0
                                        }} </span
                                    >&nbsp;{{ $t('categories.counts.done') }}
                                </span>
                            </div>
                            <div
                                class="h-1.5 overflow-hidden rounded-sm bg-muted"
                            >
                                <div
                                    class="h-full rounded-sm transition-all"
                                    :style="{
                                        width: `${completion(category)}%`,
                                        backgroundColor: category.color,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </Link>

                <!-- Dashed new-category tile -->
                <CategoryFormModal>
                    <template #trigger>
                        <button
                            class="flex min-h-32 w-full items-center justify-center gap-2 rounded-xl border border-dashed text-sm font-medium text-muted-foreground transition-colors hover:border-primary/40 hover:text-foreground"
                        >
                            <Plus class="size-4" />
                            {{ $t('categories.new') }}
                        </button>
                    </template>
                </CategoryFormModal>
            </div>
        </div>
    </AppLayout>
</template>

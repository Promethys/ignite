<script setup lang="ts">
import CategoryFormModal from '@/components/categories/CategoryFormModal.vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
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
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Tags, Trash } from 'lucide-vue-next';

interface Props {
    items: Category[];
    openCreate: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: categories.index().url,
    },
];

const subtitle = `${props.items.length} ${props.items.length === 1 ? 'category' : 'categories'}`;

const filterUrl = (category: Category) =>
    `${goals.index().url}?category=${category.id}`;

const completion = (category: Category) => {
    const total = category.goals_count ?? 0;
    if (total === 0) return 0;
    return Math.round(((category.completed_goals_count ?? 0) / total) * 100);
};
</script>

<template>
    <Head title="Categories" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader title="Categories" :description="subtitle">
                <template #actions>
                    <CategoryFormModal :open="openCreate" />
                </template>
            </PageHeader>

            <Empty v-if="items.length === 0">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Tags />
                    </EmptyMedia>
                    <EmptyTitle>No Category Yet</EmptyTitle>
                    <EmptyDescription>
                        You don't have any category yet. Create one to organize
                        your goals.
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
                        class="flex h-full flex-col gap-3 rounded-xl border bg-card p-4 shadow-sm transition-colors hover:border-primary/40"
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
                                <CategoryFormModal :record="category" />
                                <AlertDialog>
                                    <AlertDialogTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 hover:bg-destructive/10 hover:text-destructive"
                                        >
                                            <Trash class="h-4 w-4" />
                                        </Button>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle
                                                >Are you absolutely
                                                sure?</AlertDialogTitle
                                            >
                                            <AlertDialogDescription>
                                                This action cannot be undone.
                                                This will permanently delete
                                                your category.
                                            </AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel
                                                >Cancel</AlertDialogCancel
                                            >
                                            <AlertDialogAction
                                                variant="destructive"
                                                @click="
                                                    router.delete(
                                                        categories.destroy(
                                                            category,
                                                        ),
                                                    )
                                                "
                                            >
                                                Delete
                                            </AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
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
                                    >&nbsp;goals
                                </span>
                                <span>
                                    <span class="font-semibold text-foreground">
                                        {{
                                            category.active_goals_count ?? 0
                                        }} </span
                                    >&nbsp;active
                                </span>
                                <span>
                                    <span class="font-semibold text-foreground">
                                        {{
                                            category.completed_goals_count ?? 0
                                        }} </span
                                    >&nbsp;done
                                </span>
                            </div>
                            <div
                                class="h-1.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full transition-all"
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
                            New category
                        </button>
                    </template>
                </CategoryFormModal>
            </div>
        </div>
    </AppLayout>
</template>

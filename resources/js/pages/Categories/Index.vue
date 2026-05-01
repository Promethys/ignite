<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import categories from '@/routes/categories';
import { BreadcrumbItem } from '@/types';
import { Category } from '@/types/models';
import {
    ArrowRight,
    Target,
    Trash
} from 'lucide-vue-next';
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
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/vue3';
import goals from '@/routes/goals';
import CategoryFormModal from '@/components/categories/CategoryFormModal.vue';

interface Props {
    items: Category[]
}

defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: categories.index().url,
    },
];

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Empty v-if="items.length === 0">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Target />
                </EmptyMedia>
                <EmptyTitle>No Category Yet</EmptyTitle>
                <EmptyDescription>
                    You don't have any category yet.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <div class="flex gap-2">
                    <CategoryFormModal />
                </div>
            </EmptyContent>
        </Empty>
        <div v-else>
            <div class="p-4 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                    <div>
                        <h1 class="text-balance text-2xl font-bold tracking-tight sm:text-3xl">Categories</h1>
                        <p class="mt-1 text-pretty text-sm text-muted-foreground sm:text-base">
                            Organize your goals by category
                        </p>
                    </div>
                    <div>
                        <CategoryFormModal />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="category in items" :key="category.id"
                        class="group cursor-pointer transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle>
                                <div class="flex items-center justify-between">
                                    <div>
                                        {{ category.name }}
                                    </div>
                                    <div class="flex items-center">
                                        <CategoryFormModal :record="category" />
                                        <AlertDialog>
                                            <AlertDialogTrigger as-child>
                                                <Button variant="ghost" size="icon"
                                                    class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-destructive/30 dark:hover:bg-destructive/30">
                                                    <Trash class="h-4 w-4" />
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        This action cannot be undone. This will permanently delete your
                                                        category.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                                    <AlertDialogAction class="bg-destructive text-white shadow-xs hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60"
                                                        @click="router.delete(categories.destroy(category))">
                                                        Delete
                                                    </AlertDialogAction>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    </div>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <p class="text-sm text-muted-foreground">{{ category.description }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">
                                    {{ category.goals_count ?? 0 }} {{ (category.goals_count ?? 0) === 1 ? "goal" :
                                    "goals" }}
                                </span>
                                <Button variant="link" class="h-auto p-0" as-child>
                                    <Link :href="`${goals.index().url}?category=${category.id}`">
                                        View Goals
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

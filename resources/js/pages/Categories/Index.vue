<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import categories from '@/routes/categories';
import { BreadcrumbItem } from '@/types';
import { Category } from '@/types/models';
import { ArrowRight, Edit, Plus, Target } from 'lucide-vue-next';
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
import { Link } from '@inertiajs/vue3';
import goals from '@/routes/goals';

interface Props {
    items: Category[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: categories.index().url,
    },
];

</script>

<template>
    <AppLayout>
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
                    <Button as-child>
                        <Link :href="categories.create().url">
                        <Plus />
                        Category
                        </Link>
                    </Button>
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
                    <Button as-child class="w-full sm:w-auto">
                        <Link :href="categories.create().url">
                        <Plus />
                        Category
                        </Link>
                    </Button>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="category in items" :key="category.id" class="group cursor-pointer transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle>
                                <div class="flex items-center justify-between">
                                    <div>
                                        {{ category.name }}
                                    </div>
                                    <Button variant="ghost" size="icon"
                                        class="h-8 w-8 opacity-0 transition-opacity group-hover:opacity-100">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <p class="text-sm text-muted-foreground">{{ category.description }}</p>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">
                                    {{ category.goals_count ?? 0 }} {{ (category.goals_count ?? 0) === 1 ? "goal" : "goals" }}
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

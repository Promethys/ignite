<script setup lang="ts">
import { Category, Goal } from '@/types/models';
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Plus, Search, Target } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Link } from '@inertiajs/vue3';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from "@/components/ui/empty";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import GoalCard from '@/components/GoalCard.vue';
import { Input } from '@/components/ui/input';
import Separator from '@/components/ui/separator/Separator.vue';
import { computed, ref } from 'vue';

interface Props {
    items: Goal[],
    categories: Category[]
    category_id?: string|null
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
];

const selectedCategoryId = ref(props.category_id ? parseInt(props.category_id) : 'all');

const filteredItems = computed(() => {
    if(selectedCategoryId.value === 'all') {
        return props.items;
    } else {
        return props.items.filter((item) => item.category_id === selectedCategoryId.value)
    }
});

</script>

<template>

    <Head title="Goals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Empty v-if="items.length === 0">
            <EmptyHeader>
                <EmptyMedia variant="icon">
                    <Target />
                </EmptyMedia>
                <EmptyTitle>No Goal Yet</EmptyTitle>
                <EmptyDescription>
                    You don't have any goal yet. Get started by creating your first
                    goal.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <div class="flex gap-2">
                    <Button>
                        <Link :href="goals.create().url">
                            Define a Goal
                        </Link>
                    </Button>
                </div>
            </EmptyContent>
        </Empty>
        <div v-else>
            <div class="p-4 space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                    <div>
                        <h1 class="text-balance text-2xl font-bold tracking-tight sm:text-3xl">All Goals</h1>
                        <p class="mt-1 text-pretty text-sm text-muted-foreground sm:text-base">
                            Manage and track all your goals in one place
                        </p>
                    </div>
                    <Button as-child class="w-full sm:w-auto">
                        <Link :href="goals.create().url">
                            <Plus />
                            Goal
                        </Link>
                    </Button>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:gap-4">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search goals..." class="pl-9" />
                    </div>
                    <Select v-model="selectedCategoryId">
                        <SelectTrigger>
                            <SelectValue placeholder="Category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">
                                All categories
                            </SelectItem>
                            <SelectItem :value="null">
                                No category
                            </SelectItem>
                            <Separator class="my-2" />
                            <SelectItem v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-wrap gap-4">
                    <GoalCard :item="goal" v-for="goal in filteredItems" variant="default" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

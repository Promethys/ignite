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
import GoalCard from '@/components/goals/GoalCard.vue';
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
const searchQuery = ref('');

const filteredItems = computed(() => {
    let items = props.items;

    // Search input
    if(searchQuery.value !== null && searchQuery.value !== '') {
        items = items.filter((item) => {
            return item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) 
                || item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
        });
    }

    // Filter input
    if(selectedCategoryId.value === 'all') {
        return items;
    } else {
        return items.filter((item) => item.category_id === selectedCategoryId.value)
    }
});

</script>

<template>

    <Head title="Goals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div>
                    <h1 class="text-balance text-2xl font-bold tracking-tight sm:text-3xl">All Goals</h1>
                    <p class="mt-1 text-pretty text-sm text-muted-foreground sm:text-base">
                        Manage and track all your goals in one place
                    </p>
                </div>
                <Button v-if="items.length > 0" as-child class="w-full sm:w-auto">
                    <Link :href="goals.create().url">
                        <Plus />
                        Goal
                    </Link>
                </Button>
            </div>

            <div v-if="items.length > 0" class="flex flex-col gap-3 sm:flex-row sm:gap-4">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="searchQuery" placeholder="Search goals..." class="pl-9" />
                </div>
                <div>
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
            </div>
            <Empty v-if="items.length === 0 || filteredItems.length === 0">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Target />
                    </EmptyMedia>
                    <EmptyTitle>
                        <template v-if="items.length === 0">No Goal Yet</template>
                        <template v-else-if="filteredItems.length === 0">No Goal found</template>
                    </EmptyTitle>
                    <EmptyDescription>
                        <template v-if="items.length === 0">You don't have any goal yet. Get started by creating your first goal.</template>
                        <template v-else-if="filteredItems.length === 0">You don't have any goals that match this search/filter</template>
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent v-if="items.length === 0">
                    <div class="flex gap-2">
                        <Button as-child>
                            <Link :href="goals.create().url">
                                <Plus />
                                Define a Goal
                            </Link>
                        </Button>
                    </div>
                </EmptyContent>
            </Empty>
            <div v-else class="grid gap-4 xl:grid-cols-2 2xl:grid-cols-3">
                <GoalCard :item="goal" v-for="goal in filteredItems" :key="goal.id" />
            </div>
        </div>
    </AppLayout>
</template>

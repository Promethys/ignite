<script setup lang="ts">
import GoalCard from '@/components/goals/GoalCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import Separator from '@/components/ui/separator/Separator.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Category, Goal } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Search, Target } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    items: Goal[];
    categories: Category[];
    category_id?: string | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
];

const statusFilters = [
    { value: 'all', label: 'All' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'paused', label: 'Paused' },
    { value: 'completed', label: 'Completed' },
    { value: 'abandoned', label: 'Abandoned' },
];

const selectedCategoryId = ref(
    props.category_id ? parseInt(props.category_id) : 'all',
);
const selectedStatus = ref('all');
const searchQuery = ref('');

const activeCount = computed(
    () => props.items.filter((item) => item.status === 'in_progress').length,
);

const subtitle = computed(
    () =>
        `${props.items.length} ${props.items.length === 1 ? 'goal' : 'goals'} · ${activeCount.value} active`,
);

const filteredItems = computed(() => {
    let items = props.items;

    if (searchQuery.value !== null && searchQuery.value !== '') {
        const query = searchQuery.value.toLowerCase();
        items = items.filter(
            (item) =>
                item.title.toLowerCase().includes(query) ||
                item.description?.toLowerCase().includes(query),
        );
    }

    if (selectedCategoryId.value !== 'all') {
        items = items.filter(
            (item) => item.category_id === selectedCategoryId.value,
        );
    }

    if (selectedStatus.value !== 'all') {
        items = items.filter((item) => item.status === selectedStatus.value);
    }

    return items;
});
</script>

<template>
    <Head title="Goals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader title="Goals" :description="subtitle">
                <template #actions>
                    <Button
                        v-if="items.length > 0"
                        as-child
                        class="w-full sm:w-auto"
                    >
                        <Link :href="goals.create().url">
                            <Plus />
                            New goal
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <template v-if="items.length > 0">
                <div class="flex flex-col gap-3 sm:flex-row sm:gap-4">
                    <div class="relative flex-1">
                        <Search
                            class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="searchQuery"
                            placeholder="Search goals..."
                            class="pl-9"
                        />
                    </div>
                    <Select v-model="selectedCategoryId">
                        <SelectTrigger class="sm:w-48">
                            <SelectValue placeholder="Category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            <SelectItem :value="null">No category</SelectItem>
                            <template v-if="categories.length > 0">
                                <Separator class="my-2" />
                            </template>
                            <SelectItem
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="mr-1 text-xs text-muted-foreground">
                        Status
                    </span>
                    <Button
                        v-for="filter in statusFilters"
                        :key="filter.value"
                        size="sm"
                        class="rounded-full"
                        :variant="
                            selectedStatus === filter.value
                                ? 'default'
                                : 'outline'
                        "
                        @click="selectedStatus = filter.value"
                    >
                        {{ filter.label }}
                    </Button>
                </div>
            </template>

            <Empty v-if="items.length === 0 || filteredItems.length === 0">
                <EmptyHeader>
                    <EmptyMedia variant="icon">
                        <Target />
                    </EmptyMedia>
                    <EmptyTitle>
                        <template v-if="items.length === 0"
                            >No Goal Yet</template
                        >
                        <template v-else>No Goal found</template>
                    </EmptyTitle>
                    <EmptyDescription>
                        <template v-if="items.length === 0"
                            >You don't have any goal yet. Get started by
                            creating your first goal.</template
                        >
                        <template v-else
                            >No goals match this search or filter.</template
                        >
                    </EmptyDescription>
                </EmptyHeader>
                <EmptyContent v-if="items.length === 0">
                    <Button as-child>
                        <Link :href="goals.create().url">
                            <Plus />
                            Define a Goal
                        </Link>
                    </Button>
                </EmptyContent>
            </Empty>
            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <GoalCard
                    v-for="goal in filteredItems"
                    :key="goal.id"
                    :item="goal"
                />
            </div>
        </div>
    </AppLayout>
</template>

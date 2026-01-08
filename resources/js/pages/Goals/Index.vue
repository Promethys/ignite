<script setup lang="ts">
import { Goal } from '@/types/models';
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
import GoalCard from '@/components/GoalCard.vue';
import { Input } from '@/components/ui/input';

interface Props {
    items: Goal[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
];
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
            <div class="p-4">
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
            </div>
            <div class="flex flex-wrap gap-4 p-4">
                <GoalCard :item="goal" v-for="goal in items" variant="default" />
            </div>
        </div>
    </AppLayout>
</template>

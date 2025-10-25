<script setup lang="ts">
import { Goal } from '@/types/models';
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Target } from "lucide-vue-next";
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

interface Props {
    items: Goal[]
}

defineProps<Props>()

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
                <Button>
                    <Link :href="goals.create().url">
                        Goal
                    </Link> 
                </Button>
            </div>
            <div class="grid grid-cols-4 gap-4 p-4">
                <GoalCard :item="goal" v-for="goal in items"/>
            </div>
        </div>
    </AppLayout>
</template>

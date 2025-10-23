<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal, User } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    goal: Goal;
    user: User;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
    {
        title: `View "${props.goal.title}"`,
        href: '',
    },
];
</script>

<template>
    <Head title="Goals" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <GoalForm :record="goal" :user="user" />
    </AppLayout>
</template>

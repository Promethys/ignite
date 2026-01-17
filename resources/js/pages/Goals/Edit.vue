<script setup lang="ts">
import GoalForm from '@/components/goals/GoalForm.vue';
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
        title: `Edit "${props.goal.title}"`,
        href: '',
    },
];
</script>

<template>
    <Head :title="'Edit ' + goal.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <GoalForm :record="goal" :user="user" />
    </AppLayout>
</template>

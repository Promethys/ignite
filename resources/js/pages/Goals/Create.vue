<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import GoalForm from '@/components/goals/GoalForm.vue';
import { User } from '@/types/models';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Goals',
        href: goals.index().url,
    },
    {
        title: 'Create',
        href: ''
    },
];

defineProps<{
    user: User;
}>();

</script>

<template>

    <Head title="New goal" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <GoalForm :user="user" />
    </AppLayout>
</template>

<script setup lang="ts">
import GoalForm from '@/components/goals/GoalForm.vue';
import PageHeader from '@/components/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { User } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'goals.breadcrumb.index',
        href: goals.index().url,
    },
    {
        title: 'goals.breadcrumb.create',
        href: '',
    },
];

defineProps<{
    user: User;
    selectedCategory?: string | null;
}>();
</script>

<template>
    <Head :title="$t('goals.head.create')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader
                :title="$t('goals.form.create_title')"
                :description="$t('goals.form.create_description')"
            />

            <GoalForm
                :user="user"
                :selected-category="selectedCategory ?? undefined"
            />
        </div>
    </AppLayout>
</template>

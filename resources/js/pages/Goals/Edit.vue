<script setup lang="ts">
import GoalForm from '@/components/goals/GoalForm.vue';
import MilestoneFormModal from '@/components/milestones/MilestoneFormModal.vue';
import MilestoneRowActions from '@/components/milestones/MilestoneRowActions.vue';
import PageHeader from '@/components/PageHeader.vue';
import Table from '@/components/ui/table/Table.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import goals from '@/routes/goals';
import { type BreadcrumbItem } from '@/types';
import { Goal, Milestone, User } from '@/types/models';
import { Head } from '@inertiajs/vue3';
import {
    createColumnHelper,
    getCoreRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { trans } from 'laravel-vue-i18n';
import { ListChecks, Pencil } from 'lucide-vue-next';
import { h, ref, watch } from 'vue';

const props = defineProps<{
    goal: Goal;
    user: User;
}>();

const tab = ref(
    new URLSearchParams(window.location.search).get('tab') ?? 'goal',
);

watch(tab, (v) => {
    const url = new URL(window.location.href);
    if (v === 'goal') url.searchParams.delete('tab');
    else url.searchParams.set('tab', v);
    window.history.replaceState(window.history.state, '', url);
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'goals.breadcrumb.index',
        href: goals.index().url,
    },
    {
        title: `"${props.goal.title}"`,
        href: goals.show(props.goal).url,
    },
    {
        title: 'goals.breadcrumb.edit',
        href: '',
    },
];

const goalType = props.goal.type;
const labelNamespace = goalType === 'multi_step' ? 'steps' : 'milestones';

const columnHelper = createColumnHelper<Milestone>();

const columns = [
    columnHelper.accessor('title', {
        header: trans('milestones.table.title'),
    }),
    columnHelper.accessor('target_value', {
        header: trans('milestones.table.target_value'),
    }),
    columnHelper.accessor('completed_at', {
        header: trans('milestones.table.completed_at'),
        cell: (props) => {
            const completedAt = props.getValue();
            return completedAt
                ? new Date(completedAt).toISOString().split('T')[0]
                : '-';
        },
    }),
    columnHelper.display({
        id: 'actions',
        cell: (props) =>
            h(MilestoneRowActions, {
                row: props.row,
                goalType,
            }),
    }),
];

const table = useVueTable({
    get data() {
        return props.goal.milestones ?? [];
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
});
</script>

<template>
    <Head :title="$t('goals.form.edit_title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="m-4">
            <Tabs v-model="tab">
                <TabsList>
                    <TabsTrigger value="goal">
                        <span class="flex items-center gap-2">
                            <Pencil class="size-4" />
                            {{ $t('milestones.manage.tab_general') }}
                        </span>
                    </TabsTrigger>
                    <TabsTrigger value="milestones">
                        <span class="flex items-center gap-2">
                            <ListChecks class="size-4" />
                            {{ $t(`${labelNamespace}.manage.tab_milestones`) }}
                        </span>
                    </TabsTrigger>
                </TabsList>
                <TabsContent value="goal">
                    <div class="space-y-6">
                        <PageHeader
                            :title="$t('goals.form.edit_title')"
                            :description="$t('goals.form.edit_description')"
                        />

                        <GoalForm :record="goal" :user="user" />
                    </div>
                </TabsContent>
                <TabsContent value="milestones">
                    <div class="space-y-6">
                        <PageHeader
                            :title="$t(`${labelNamespace}.manage.title`)"
                            :description="
                                $t(`${labelNamespace}.manage.description`)
                            "
                        >
                            <template #actions>
                                <MilestoneFormModal :goal_id="goal.id" :goal_type="goal.type" />
                            </template>
                        </PageHeader>
                        <Table :table />
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>

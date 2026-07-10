<script setup lang="ts">
import { formatDate } from '@/lib/utils';
import { Goal, Milestone } from '@/types/models';
import { Check, Plus, Target } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '../ui/badge';
import MilestoneFormModal from './MilestoneFormModal.vue';

const props = defineProps<{
    record: Goal;
}>();

const toggleMilestone = (id: number) => {
    return id; // placeholder code for now
    // TODO: call controller route... Probably create a new route for toggling the value.
    // setMilestones((prev) =>
    //     prev.map((m) =>
    //         m.id === id
    //             ? {
    //                 ...m,
    //                 completed: !m.completed,
    //                 completed_at: !m.completed
    //                     ? new Date().toISOString().split('T')[0]
    //                     : undefined,
    //             }
    //             : m
    //     )
    // )
};

const isAutoComplete = (milestone: Milestone) => {
    return milestone.target_value != null;
};

const isAutoCompleted = (milestone: Milestone) => {
    return (
        milestone.target_value !== undefined &&
        props.record.current_value >= (milestone.target_value ?? 0)
    );
};

const isCompleted = (milestone: Milestone) => {
    return milestone.is_completed || isAutoCompleted(milestone);
};

const getProgress = (milestone: Milestone) => {
    if (!milestone.target_value) return 0;
    return Math.min(
        100,
        (props.record.current_value / milestone.target_value) * 100,
    );
};

const activeIndex = computed(() =>
    props.record.milestones?.findIndex(
        (milestone) => !milestone.is_completed && !isAutoCompleted(milestone),
    ),
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="relative flex flex-col">
            <div
                v-for="(milestone, index) in record.milestones"
                :key="milestone.id"
                class="relative flex gap-4"
            >
                <div
                    class="absolute top-8 left-4 h-[calc(100%-16px)] w-0.5"
                    :class="{
                        'bg-success': isCompleted(milestone),
                        'bg-border': !isCompleted(milestone),
                    }"
                    v-if="index !== (record.milestones?.length ?? 0) - 1"
                />

                <!-- Circle Indicator -->
                <div class="relative z-10 flex-shrink-0">
                    <button
                        @click="
                            () =>
                                !isAutoComplete(milestone) &&
                                !isCompleted(milestone) &&
                                toggleMilestone(milestone.id)
                        "
                        :disabled="
                            isAutoComplete(milestone) || isCompleted(milestone)
                        "
                        :class="{
                            'flex size-8 items-center justify-center rounded-full border-2 transition-all': true,
                            'border-success bg-success text-success-foreground':
                                isCompleted(milestone),
                            'border-warning bg-warning/20 ring-4 ring-warning/20':
                                index === activeIndex &&
                                !isCompleted(milestone),
                            'border-border bg-background hover:border-muted-foreground':
                                index !== activeIndex &&
                                !isCompleted(milestone),
                            'cursor-default':
                                isAutoComplete(milestone) &&
                                !isCompleted(milestone),
                            'cursor-pointer':
                                !isAutoComplete(milestone) &&
                                !isCompleted(milestone),
                        }"
                    >
                        <template v-if="isCompleted(milestone)">
                            <Check class="size-4" />
                        </template>
                        <template v-else>
                            <div v-if="isAutoComplete(milestone)">
                                <div
                                    class="size-5 rounded-full border-2 border-muted-foreground"
                                    :style="{
                                        background: `conic-gradient(var(--warning) ${getProgress(milestone)}%, transparent ${getProgress(milestone)}%)`,
                                    }"
                                />
                            </div>
                            <div v-else>
                                <div
                                    :class="{
                                        'size-2 rounded-full': true,
                                        'bg-warning': index === activeIndex,
                                        'bg-muted-foreground/50':
                                            index !== activeIndex,
                                    }"
                                />
                            </div>
                        </template>
                    </button>
                </div>

                <!-- Content -->
                <div
                    :class="{
                        'flex flex-1 flex-col gap-1 pb-6': true,
                        'opacity-60': isCompleted(milestone),
                    }"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="font-medium"
                            :class="{
                                'text-warning': index === activeIndex,
                                'line-through': isCompleted(milestone),
                            }"
                        >
                            {{ milestone.title }}
                        </span>
                        <Badge
                            v-if="index === activeIndex"
                            class="border-warning/30 bg-warning/20 text-xs text-warning"
                        >
                            {{ $t('milestones.next_up') }}
                        </Badge>
                    </div>

                    <div
                        class="flex items-center gap-3 text-sm text-muted-foreground"
                    >
                        <span
                            v-if="milestone.target_value"
                            class="flex items-center gap-1"
                        >
                            <Target class="size-3.5" />
                            {{ milestone.target_value.toLocaleString() }}
                            {{ record.unit }}
                        </span>
                        <!-- <span v-if="milestone.deadline" class="flex items-center gap-1">
                            <Calendar class="size-3.5" />
                            {{ formatDate(milestone.deadline) }}
                        </span> -->
                    </div>

                    <div
                        v-if="
                            isAutoComplete(milestone) && !isCompleted(milestone)
                        "
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        {{
                            $t('milestones.auto_completes', {
                                value:
                                    milestone.target_value?.toLocaleString() ??
                                    '',
                                unit: record.unit ?? '',
                                percent: Math.round(
                                    getProgress(milestone),
                                ).toString(),
                            })
                        }}
                    </div>

                    <div
                        v-if="isCompleted(milestone) && milestone.completed_at"
                        class="text-xs text-success"
                    >
                        {{
                            $t('milestones.completed_on', {
                                date: formatDate(milestone.completed_at),
                            })
                        }}
                    </div>
                </div>
            </div>

            <!-- Add milestone button at the end -->
            <div class="flex items-center gap-4">
                <MilestoneFormModal :goal_id="props.record.id">
                    <template #trigger>
                        <button
                            class="flex size-8 cursor-pointer items-center justify-center rounded-full border-2 border-dashed border-border transition-colors hover:border-muted-foreground"
                        >
                            <Plus class="size-4 text-muted-foreground" />
                        </button>
                    </template>
                </MilestoneFormModal>
                <span class="text-sm text-muted-foreground">{{
                    $t('milestones.add')
                }}</span>
            </div>
        </div>
    </div>
</template>

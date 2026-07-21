<script setup lang="ts">
import { formatDate } from '@/lib/utils';
import milestones from '@/routes/milestones';
import { Goal, Milestone } from '@/types/models';
import { router } from '@inertiajs/vue3';
import { Check, Plus, RotateCcw, Target } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '../ui/badge';
import MilestoneFormModal from './MilestoneFormModal.vue';

const props = defineProps<{
    record: Goal;
}>();

const labelNamespace =
    props.record.type === 'multi_step' ? 'steps' : 'milestones';

const toggleMilestone = (milestone: Milestone) => {
    const url = isCompleted(milestone)
        ? milestones.uncomplete({ goal: props.record, milestone })
        : milestones.complete({ goal: props.record, milestone });

    router.patch(url);
};

const isAutoComplete = (milestone: Milestone) => {
    return milestone.target_value != null;
};

const isAutoCompleted = (milestone: Milestone) => milestone.is_reached;

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
                                toggleMilestone(milestone)
                        "
                        :aria-label="
                            !isAutoComplete(milestone)
                                ? isCompleted(milestone)
                                    ? $t('milestones.mark_incomplete')
                                    : $t('milestones.mark_complete')
                                : undefined
                        "
                        :class="{
                            'group flex size-8 items-center justify-center rounded-full border-2 transition-all': true,
                            'cursor-pointer': !isAutoComplete(milestone),
                            'cursor-default': isAutoComplete(milestone),
                            'border-success bg-success text-success-foreground':
                                isCompleted(milestone),
                            'border-warning bg-warning/20 ring-4 ring-warning/20':
                                index === activeIndex &&
                                !isCompleted(milestone),
                            'border-border bg-background':
                                index !== activeIndex &&
                                !isCompleted(milestone),
                            'hover:border-success':
                                !isAutoComplete(milestone) &&
                                !isCompleted(milestone),
                            'hover:border-warning':
                                !isAutoComplete(milestone) &&
                                isCompleted(milestone),
                        }"
                    >
                        <template v-if="isCompleted(milestone)">
                            <Check class="inline size-4 group-hover:hidden" />
                            <RotateCcw
                                class="hidden size-4 group-hover:inline"
                            />
                        </template>
                        <template v-else>
                            <!-- Auto-completing (quantifiable) milestone: non-interactive progress ring -->
                            <div v-if="isAutoComplete(milestone)">
                                <div
                                    class="size-5 rounded-full border-2 border-muted-foreground"
                                    :style="{
                                        background: `conic-gradient(var(--warning) ${getProgress(milestone)}%, transparent ${getProgress(milestone)}%)`,
                                    }"
                                />
                            </div>
                            <!-- Manual step: empty checkbox that previews a check on hover -->
                            <Check
                                v-else
                                class="size-4 text-success opacity-0 transition-opacity group-hover:opacity-100"
                            />
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
                <MilestoneFormModal
                    :goal_id="props.record.id"
                    :goal_type="props.record.type"
                >
                    <template #trigger>
                        <button
                            class="flex size-8 cursor-pointer items-center justify-center rounded-full border-2 border-dashed border-border transition-colors hover:border-muted-foreground"
                        >
                            <Plus class="size-4 text-muted-foreground" />
                        </button>
                    </template>
                </MilestoneFormModal>
                <span class="text-sm text-muted-foreground">{{
                    $t(`${labelNamespace}.add`)
                }}</span>
            </div>
        </div>
    </div>
</template>

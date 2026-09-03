<script setup lang="ts">
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import {
    cellLabel,
    cellLevel,
    columnStarts,
    isDenseCadence,
    monthLabels,
    rowCount,
    scrollToLatest,
    tooltipDate,
    weekdayLabels,
    type HeatmapCell,
    type HeatmapPayload,
} from '@/lib/heatmap';
import { unitByRecurrence } from '@/lib/streak';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps<HeatmapPayload>();

const CELL_SIZE = '11px';
const CELL_GAP = '3px';

const unit = computed(() => unitByRecurrence[props.cadence]);
const isDense = computed(() => isDenseCadence(props.cadence));
const columns = computed(() => columnStarts(props.cells, props.cadence));
const labels = computed(() => monthLabels(props.cells, props.cadence));

/**
 * Only every other weekday is named, as three labels read cleanly against
 * eleven-pixel rows where seven would collide.
 */
const weekdays = computed(() =>
    weekdayLabels().map((day, index) => (index % 2 === 0 ? day : '')),
);

/**
 * Message keys and their arguments are built here, but resolved with `$t` in
 * the template. `trans()` reads the store once and would freeze the string on a
 * locale switch, where the template helper re-renders.
 */
const summaryKey = computed(() => `goals.heatmap.summary.${unit.value}`);

const summaryArgs = computed(() => ({
    done: props.total.toString(),
    total: props.cells.length.toString(),
}));

const gridStyle = computed(() => ({
    gridAutoFlow: 'column',
    gridTemplateRows: `repeat(${rowCount(props.cadence)}, ${CELL_SIZE})`,
    gridAutoColumns: CELL_SIZE,
    gap: CELL_GAP,
}));

const stripStyle = computed(() => ({
    gridTemplateColumns: `repeat(${columns.value.length}, ${CELL_SIZE})`,
    gap: CELL_GAP,
}));

const weekdayStyle = computed(() => ({
    gridTemplateRows: `repeat(${rowCount(props.cadence)}, ${CELL_SIZE})`,
    gap: CELL_GAP,
}));

const cellClass = (cell: HeatmapCell) =>
    cellLevel(cell.value) > 0 ? 'bg-primary' : 'bg-muted';

const tooltipKey = (cell: HeatmapCell) =>
    `goals.heatmap.tooltip.${cellLevel(cell.value) > 0 ? 'logged' : 'empty'}.${unit.value}`;

const tooltipArgs = (cell: HeatmapCell) => ({
    date: tooltipDate(cell, props.cadence),
});

const scroller = ref<HTMLElement | null>(null);

const parkAtLatest = () => scrollToLatest(scroller.value);

onMounted(parkAtLatest);

/**
 * `flush: 'post'` so the grid has been re-rendered at its new width before the
 * scroll offset is set; a default pre-flush watcher would measure the old one.
 */
watch(() => props.cells, parkAtLatest, { flush: 'post' });
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm text-muted-foreground">
            {{ $tChoice(summaryKey, cells.length, summaryArgs) }}
        </p>

        <TooltipProvider :delay-duration="100">
            <div
                ref="scroller"
                data-slot="heatmap-scroller"
                role="img"
                :aria-label="$tChoice(summaryKey, cells.length, summaryArgs)"
                class="overflow-x-auto pb-1"
                :class="isDense ? '' : 'flex'"
            >
                <!-- Dense cadences: a calendar grid under a month strip -->
                <div v-if="isDense" class="flex w-max gap-1.5">
                    <div
                        v-if="cadence === 'daily'"
                        data-slot="heatmap-weekdays"
                        class="grid pt-[15px] text-[9px] leading-none text-muted-foreground"
                        :style="weekdayStyle"
                    >
                        <span
                            v-for="(day, index) in weekdays"
                            :key="index"
                            class="flex items-center"
                            >{{ day }}</span
                        >
                    </div>

                    <div class="space-y-1">
                        <div
                            data-slot="heatmap-month-strip"
                            class="grid text-[9px] leading-none text-muted-foreground"
                            :style="stripStyle"
                        >
                            <span
                                v-for="(label, index) in labels"
                                :key="index"
                                class="overflow-visible whitespace-nowrap"
                                >{{ label }}</span
                            >
                        </div>

                        <div :style="gridStyle" class="grid">
                            <Tooltip
                                v-for="cell in cells"
                                :key="cell.date"
                                :delay-duration="100"
                            >
                                <TooltipTrigger as="div" tabindex="-1">
                                    <span
                                        data-slot="heatmap-cell"
                                        aria-hidden="true"
                                        class="block size-full rounded-[2px]"
                                        :class="cellClass(cell)"
                                    />
                                </TooltipTrigger>
                                <TooltipContent>{{
                                    $t(tooltipKey(cell), tooltipArgs(cell))
                                }}</TooltipContent>
                            </Tooltip>
                        </div>
                    </div>
                </div>

                <!-- Sparse cadences: wide cells that each carry their label -->
                <div v-else class="flex w-max gap-2">
                    <Tooltip
                        v-for="cell in cells"
                        :key="cell.date"
                        :delay-duration="100"
                    >
                        <TooltipTrigger as="div" tabindex="-1">
                            <div class="flex flex-col items-center gap-1">
                                <span
                                    data-slot="heatmap-cell"
                                    aria-hidden="true"
                                    class="block size-9 rounded-md"
                                    :class="cellClass(cell)"
                                />
                                <span
                                    data-slot="heatmap-cell-label"
                                    class="text-[10px] leading-none text-muted-foreground"
                                    >{{ cellLabel(cell, cadence) }}</span
                                >
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>{{
                            $t(tooltipKey(cell), tooltipArgs(cell))
                        }}</TooltipContent>
                    </Tooltip>
                </div>
            </div>
        </TooltipProvider>

        <div
            class="flex items-center justify-end gap-1.5 text-[10px] text-muted-foreground"
        >
            <span>{{ $t('goals.heatmap.legend_less') }}</span>
            <span class="size-[11px] rounded-[2px] bg-muted" />
            <span class="size-[11px] rounded-[2px] bg-primary" />
            <span>{{ $t('goals.heatmap.legend_more') }}</span>
        </div>
    </div>
</template>

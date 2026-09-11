<script setup lang="ts">
import {
    captionDate,
    cellLabel,
    cellLevel,
    columnStarts,
    isDenseCadence,
    monthLabels,
    rowCount,
    scrollToLatest,
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

const selectedDate = ref<string | null>(null);

const captionCell = computed(
    () =>
        props.cells.find((cell) => cell.date === selectedDate.value) ??
        props.cells.at(-1) ??
        null,
);

const captionKey = computed(() =>
    captionCell.value
        ? `goals.heatmap.caption.${cellLevel(captionCell.value.value) > 0 ? 'logged' : 'empty'}.${unit.value}`
        : '',
);

const captionArgs = computed(() => ({
    date: captionCell.value
        ? captionDate(captionCell.value, props.cadence)
        : '',
}));

const selectCell = (cell: HeatmapCell) => {
    selectedDate.value = cell.date;
};

/**
 * A mouse selects on hover and lets go when it leaves the grid. Touch has no
 * hover, and fires `pointerleave` on every lift, so it selects on release and
 * keeps the selection until the next tap.
 */
const onCellPointerEnter = (event: PointerEvent, cell: HeatmapCell) => {
    if (event.pointerType === 'mouse') {
        selectCell(cell);
    }
};

const onGridPointerLeave = (event: PointerEvent) => {
    if (event.pointerType === 'mouse') {
        selectedDate.value = null;
    }
};

const cellClass = (cell: HeatmapCell) => [
    cellLevel(cell.value) > 0 ? 'bg-primary' : 'bg-muted',
    { 'ring-1 ring-foreground': cell.date === selectedDate.value },
];

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

        <div
            ref="scroller"
            data-slot="heatmap-scroller"
            role="img"
            :aria-label="$tChoice(summaryKey, cells.length, summaryArgs)"
            class="overflow-x-auto pb-1"
            :class="isDense ? '' : 'flex'"
            @pointerleave="onGridPointerLeave"
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
                        <span
                            v-for="cell in cells"
                            :key="cell.date"
                            data-slot="heatmap-cell"
                            aria-hidden="true"
                            class="block size-full rounded-sm"
                            :class="cellClass(cell)"
                            @pointerenter="onCellPointerEnter($event, cell)"
                            @pointerup="selectCell(cell)"
                        />
                    </div>
                </div>
            </div>

            <!-- Sparse cadences: wide cells that each carry their label -->
            <div v-else class="flex w-max gap-2">
                <div
                    v-for="cell in cells"
                    :key="cell.date"
                    class="flex flex-col items-center gap-1"
                    @pointerenter="onCellPointerEnter($event, cell)"
                    @pointerup="selectCell(cell)"
                >
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
            </div>
        </div>

        <p
            v-if="captionCell"
            data-slot="heatmap-caption"
            class="text-xs text-muted-foreground"
        >
            {{ $t(captionKey, captionArgs) }}
        </p>

        <div
            class="flex items-center justify-end gap-1.5 text-[10px] text-muted-foreground"
        >
            <span>{{ $t('goals.heatmap.legend_less') }}</span>
            <span class="size-[11px] rounded-sm bg-muted" />
            <span class="size-[11px] rounded-sm bg-primary" />
            <span>{{ $t('goals.heatmap.legend_more') }}</span>
        </div>
    </div>
</template>

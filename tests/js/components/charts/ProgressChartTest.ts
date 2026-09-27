import ProgressChart from '@/components/charts/ProgressChart.vue';
import type { GoalEntry } from '@/types/models';
import { mount } from '@vue/test-utils';
import type { ApexOptions } from 'apexcharts';
import { describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

vi.mock('laravel-vue-i18n', async () => {
    const { computed } = await import('vue');
    return {
        wTrans: (key: string, replacements: Record<string, string> = {}) =>
            computed(() => [key, ...Object.values(replacements)].join(' ')),
    };
});

vi.mock('@/composables/useAppearance', () => ({
    getBinaryTheme: () => 'light',
}));

const entries: GoalEntry[] = [
    {
        id: 1,
        goal_id: 1,
        value: 10,
        previous_value: 0,
        increment_value: 10,
        note: null,
        entry_date: '2026-01-01',
        attachment_path: null,
        attachment_type: null,
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
    },
    {
        id: 2,
        goal_id: 1,
        value: 25,
        previous_value: 10,
        increment_value: 15,
        note: null,
        entry_date: '2026-02-01',
        attachment_path: null,
        attachment_type: null,
        created_at: '2026-02-01',
        updated_at: '2026-02-01',
    },
];

vi.mock('vue3-apexcharts', () => ({
    default: {
        name: 'apexchart',
        template: '<div class="apexchart-mock" />',
        props: ['type', 'width', 'height', 'options', 'series'],
    },
}));

describe('ProgressChart', () => {
    it('renders chart when entries are provided', () => {
        const wrapper = mount(ProgressChart, {
            props: {
                entries,
                targetValue: 50,
                unit: 'books',
            },
            global: {
                stubs: {
                    apexchart: {
                        template: '<div class="apexchart-mock" />',
                        props: ['type', 'width', 'height', 'options', 'series'],
                    },
                },
            },
        });

        expect(wrapper.find('.apexchart-mock').exists()).toBe(true);
    });

    it('shows empty state when entries array is empty', () => {
        const wrapper = mount(ProgressChart, {
            props: {
                entries: [],
                targetValue: 50,
                unit: 'books',
            },
            global: {
                stubs: {
                    apexchart: {
                        template: '<div class="apexchart-mock" />',
                        props: ['type', 'width', 'height', 'options', 'series'],
                    },
                },
            },
        });

        expect(wrapper.find('.apexchart-mock').exists()).toBe(true);
    });
});

describe('ProgressChart time range and axis', () => {
    const entry = (entry_date: string, value: number) => ({
        entry_date,
        value,
    });

    const twoYears = [
        entry('2024-06-01', 100),
        entry('2025-06-01', 9000),
        entry('2026-03-01', 400),
        entry('2026-06-01', 500),
    ];

    const mountChart = (
        chartEntries: ReturnType<typeof entry>[],
        targetValue: number | null = null,
    ) =>
        mount(ProgressChart, {
            props: { entries: chartEntries, targetValue, unit: null },
        });

    const optionsOf = (wrapper: ReturnType<typeof mountChart>) =>
        wrapper
            .findComponent({ name: 'apexchart' })
            .props('options') as ApexOptions;

    const yAxisOf = (wrapper: ReturnType<typeof mountChart>) =>
        optionsOf(wrapper).yaxis as { min: number; max: number };

    it('opens on the three months up to the latest entry', () => {
        const options = optionsOf(mountChart(twoYears));

        expect(options.xaxis?.min).toBe(new Date('2026-03-01').getTime());
        expect(options.xaxis?.max).toBe(new Date('2026-06-01').getTime());
    });

    it('scales the y-axis to the visible window, not the whole history', () => {
        const yAxis = yAxisOf(mountChart(twoYears));

        expect(yAxis.max).toBeLessThan(9000);
    });

    it('shows the whole history on "All"', async () => {
        const wrapper = mountChart(twoYears);

        await wrapper
            .findAll('button')
            .find((button) => button.text() === 'goals.chart.range.all')!
            .trigger('mousedown');
        await nextTick();

        expect(optionsOf(wrapper).xaxis?.min).toBe(
            new Date('2024-06-01').getTime(),
        );
        expect(yAxisOf(wrapper).max).toBeGreaterThanOrEqual(9000);
    });

    it('stretches the y-axis to keep a distant target in view', () => {
        const yAxis = yAxisOf(mountChart(twoYears, 20000));

        expect(yAxis.max).toBeGreaterThanOrEqual(20000);
    });

    it('keeps a target below the data in view', () => {
        const yAxis = yAxisOf(mountChart(twoYears, 50));

        expect(yAxis.min).toBeLessThanOrEqual(50);
    });

    it('never dips the y-axis below zero for data that does not', () => {
        const yAxis = yAxisOf(mountChart(twoYears, 0));

        expect(yAxis.min).toBe(0);
    });

    it('hides the range picker when all entries fit in a month', () => {
        const wrapper = mountChart([
            entry('2026-06-01', 10),
            entry('2026-06-20', 15),
        ]);

        expect(wrapper.text()).not.toContain('goals.chart.range.all');
    });

    it('leaves the y-axis to ApexCharts when there is nothing to plot', () => {
        const yAxis = yAxisOf(mountChart([]));

        expect(yAxis.min).toBeUndefined();
        expect(yAxis.max).toBeUndefined();
    });

    it('labels the target line with its value and unit, on the right', () => {
        const wrapper = mount(ProgressChart, {
            props: {
                entries: [entry('2026-06-01', 10)],
                targetValue: 42,
                unit: 'books',
            },
        });
        const annotation = optionsOf(wrapper).annotations?.yaxis?.[0];

        expect(annotation?.label?.text).toContain('42 books');
        expect(annotation?.label?.position).toBe('right');
        expect(annotation?.label?.textAnchor).toBe('end');
    });
});

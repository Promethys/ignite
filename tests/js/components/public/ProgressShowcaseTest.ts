import ProgressShowcase from '@/components/public/ProgressShowcase.vue';
import { progressEntries, quantifiableGoal } from '@/lib/public-showcase';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('vue3-apexcharts', () => ({
    default: {
        name: 'VueApexCharts',
        props: ['options', 'series'],
        template: '<div />',
    },
}));
vi.mock('laravel-vue-i18n', () => ({
    trans: (key: string) => key,
}));
vi.mock('@/lib/chart-theme', () => ({
    lineChartOptions: () => ({
        yaxis: { labels: { style: { colors: 'grey' } } },
    }),
}));

afterEach(() => {
    vi.useRealTimers();
    vi.resetModules();
});

describe('ProgressShowcase', () => {
    it('uses a fixed distance scale while keeping the shared label styling', () => {
        const wrapper = mount(ProgressShowcase, {
            props: { entries: progressEntries, goal: quantifiableGoal },
        });
        const chart = wrapper.getComponent({ name: 'VueApexCharts' });

        expect(chart.props('options').yaxis).toMatchObject({
            min: 0,
            max: 400,
            tickAmount: 4,
            forceNiceScale: false,
            title: { text: 'public.showcase.distance_axis' },
            labels: { style: { colors: 'grey' } },
        });
        wrapper.unmount();
    });

    it('ends the deterministic progress and heatmap fixtures today across years', async () => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date(2027, 0, 5, 12));
        const { progressEntries, heatmap } = await import(
            '@/lib/public-showcase'
        );

        expect(progressEntries.at(-1)?.entry_date).toBe('2027-01-05');
        expect(heatmap.cells.at(-1)?.date).toBe('2027-01-05');
        expect(progressEntries.map((entry) => entry.value)).toEqual([
            0, 12, 36, 51, 80, 96, 120, 152, 177, 205, 235, 259,
        ]);
        expect(progressEntries[0].entry_date).toBe('2026-09-28');
    });
});

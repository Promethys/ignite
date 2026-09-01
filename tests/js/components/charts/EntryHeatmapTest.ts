import EntryHeatmap from '@/components/charts/EntryHeatmap.vue';
import type { HeatmapCadence, HeatmapCell } from '@/lib/heatmap';
import { mount } from '@vue/test-utils';
import moment from 'moment';
import { describe, expect, it } from 'vitest';

const daysFrom = (
    start: string,
    count: number,
    lit: string[] = [],
): HeatmapCell[] =>
    Array.from({ length: count }, (_, index) => {
        const date = moment(start, 'YYYY-MM-DD')
            .add(index, 'days')
            .format('YYYY-MM-DD');

        return { date, value: lit.includes(date) ? 1 : 0 };
    });

const render = (cadence: HeatmapCadence, cells: HeatmapCell[]) =>
    mount(EntryHeatmap, {
        props: {
            cadence,
            cells,
            total: cells.filter((cell) => cell.value > 0).length,
        },
    });

const cells = (wrapper: ReturnType<typeof render>) =>
    wrapper.findAll('[data-slot="heatmap-cell"]');

describe('EntryHeatmap', () => {
    it('renders one cell per payload entry', () => {
        const wrapper = render('daily', daysFrom('2026-08-31', 21));

        expect(cells(wrapper)).toHaveLength(21);
    });

    it('colours a logged period with the primary token and an empty one muted', () => {
        const wrapper = render(
            'daily',
            daysFrom('2026-08-31', 3, ['2026-09-01']),
        );

        const classes = cells(wrapper).map((cell) => cell.classes());

        expect(classes[0]).toContain('bg-muted');
        expect(classes[1]).toContain('bg-primary');
        expect(classes[2]).toContain('bg-muted');
    });

    it('renders a fully muted grid when nothing was logged', () => {
        const wrapper = render('daily', daysFrom('2026-08-31', 14));

        expect(
            cells(wrapper).every((cell) => cell.classes().includes('bg-muted')),
        ).toBe(true);
    });

    it('names the cadence unit in the summary line, with both counts', () => {
        const wrapper = render(
            'daily',
            daysFrom('2026-08-31', 10, ['2026-08-31', '2026-09-02']),
        );

        expect(wrapper.text()).toContain('goals.heatmap.summary.day');
        expect(wrapper.text()).toContain('2 10');
    });

    it('switches the summary key with the cadence', () => {
        expect(render('weekly', daysFrom('2026-08-31', 2)).text()).toContain(
            'goals.heatmap.summary.week',
        );
        expect(render('monthly', daysFrom('2026-08-01', 2)).text()).toContain(
            'goals.heatmap.summary.month',
        );
        expect(render('annually', daysFrom('2026-01-01', 2)).text()).toContain(
            'goals.heatmap.summary.year',
        );
    });

    it('gives a daily grid seven rows and a weekday strip', () => {
        const wrapper = render('daily', daysFrom('2026-08-31', 21));
        const weekdays = wrapper.find('[data-slot="heatmap-weekdays"]');

        expect(weekdays.exists()).toBe(true);
        expect(weekdays.attributes('style')).toContain('repeat(7, 11px)');
    });

    it('gives a weekly grid a single row and no weekday strip', () => {
        const wrapper = render('weekly', daysFrom('2026-08-31', 8));

        expect(wrapper.find('[data-slot="heatmap-weekdays"]').exists()).toBe(
            false,
        );
        expect(wrapper.find('[data-slot="heatmap-month-strip"]').exists()).toBe(
            true,
        );
    });

    it('gives the sparse cadences a per-cell label instead of a month strip', () => {
        const wrapper = render('monthly', [
            { date: '2026-07-01', value: 1 },
            { date: '2026-08-01', value: 0 },
        ]);

        expect(wrapper.find('[data-slot="heatmap-month-strip"]').exists()).toBe(
            false,
        );
        expect(
            wrapper
                .findAll('[data-slot="heatmap-cell-label"]')
                .map((label) => label.text()),
        ).toEqual(['Jul', 'Aug']);
    });

    it('labels annual cells with their year', () => {
        const wrapper = render('annually', [
            { date: '2025-01-01', value: 1 },
            { date: '2026-01-01', value: 0 },
        ]);

        expect(
            wrapper
                .findAll('[data-slot="heatmap-cell-label"]')
                .map((label) => label.text()),
        ).toEqual(['2025', '2026']);
    });

    it('describes the whole grid to assistive tech and hides the cells', () => {
        const wrapper = render('daily', daysFrom('2026-08-31', 7));
        const grid = wrapper.find('[role="img"]');

        expect(grid.attributes('aria-label')).toContain(
            'goals.heatmap.summary.day',
        );
        expect(
            cells(wrapper).every(
                (cell) => cell.attributes('aria-hidden') === 'true',
            ),
        ).toBe(true);
    });

    it('keeps the grid out of the tab order', () => {
        const wrapper = render('daily', daysFrom('2026-08-31', 7));

        expect(
            wrapper
                .findAll('[data-slot="tooltip-trigger"]')
                .every((trigger) => trigger.attributes('tabindex') === '-1'),
        ).toBe(true);
    });
});

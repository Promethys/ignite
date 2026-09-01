import {
    cellLabel,
    cellLevel,
    columnStarts,
    isDenseCadence,
    monthLabels,
    parseCellDate,
    rowCount,
    tooltipDate,
    weekdayLabels,
    type HeatmapCell,
} from '@/lib/heatmap';
import moment from 'moment';
import { describe, expect, it } from 'vitest';

const daysFrom = (start: string, count: number): HeatmapCell[] =>
    Array.from({ length: count }, (_, index) => ({
        date: moment(start, 'YYYY-MM-DD')
            .add(index, 'days')
            .format('YYYY-MM-DD'),
        value: 0,
    }));

const cell = (date: string, value = 0): HeatmapCell => ({ date, value });

describe('parseCellDate', () => {
    /**
     * The assertion holds in every timezone. `new Date('2026-08-31')` reads the
     * string as UTC midnight and would land on 30 August west of Greenwich,
     * failing the day component and the Monday check.
     */
    it('reads the anchor as a calendar date, not a UTC instant', () => {
        const parsed = parseCellDate('2026-08-31');

        expect(parsed.year()).toBe(2026);
        expect(parsed.month()).toBe(7);
        expect(parsed.date()).toBe(31);
        expect(parsed.isSame(moment([2026, 7, 31]), 'day')).toBe(true);
    });

    it('keeps the weekday the backend anchored the cell to', () => {
        expect(parseCellDate('2026-08-31').locale('en').format('dddd')).toBe(
            'Monday',
        );
    });

    it('round-trips every anchor it is given', () => {
        for (const date of ['2025-01-01', '2026-02-28', '2026-12-31']) {
            expect(parseCellDate(date).format('YYYY-MM-DD')).toBe(date);
        }
    });
});

describe('cellLevel', () => {
    it('maps an empty period to the bottom step', () => {
        expect(cellLevel(0)).toBe(0);
    });

    it('maps any logged period to the top step', () => {
        expect(cellLevel(1)).toBe(1);
        expect(cellLevel(7)).toBe(1);
    });
});

describe('rowCount', () => {
    it('flows a daily grid down seven weekday rows', () => {
        expect(rowCount('daily')).toBe(7);
    });

    it('keeps every other cadence on a single band', () => {
        expect(rowCount('weekly')).toBe(1);
        expect(rowCount('monthly')).toBe(1);
        expect(rowCount('annually')).toBe(1);
    });
});

describe('isDenseCadence', () => {
    it('treats daily and weekly as dense', () => {
        expect(isDenseCadence('daily')).toBe(true);
        expect(isDenseCadence('weekly')).toBe(true);
    });

    it('treats monthly and annually as sparse', () => {
        expect(isDenseCadence('monthly')).toBe(false);
        expect(isDenseCadence('annually')).toBe(false);
    });
});

describe('columnStarts', () => {
    it('takes every seventh cell for a daily grid', () => {
        const starts = columnStarts(daysFrom('2026-08-31', 21), 'daily');

        expect(starts.map((item) => item.date)).toEqual([
            '2026-08-31',
            '2026-09-07',
            '2026-09-14',
        ]);
    });

    it('keeps a trailing partial column', () => {
        expect(columnStarts(daysFrom('2026-08-31', 10), 'daily')).toHaveLength(
            2,
        );
    });

    it('gives every weekly cell its own column', () => {
        const cells = [cell('2026-08-24'), cell('2026-08-31')];

        expect(columnStarts(cells, 'weekly')).toHaveLength(2);
    });
});

describe('monthLabels', () => {
    it('labels the first column and every column that opens a new month', () => {
        const labels = monthLabels(daysFrom('2026-08-31', 42), 'daily');

        expect(labels).toEqual(['Aug', 'Sep', null, null, null, 'Oct']);
    });

    it('labels weekly columns off their own anchors', () => {
        const cells = [
            cell('2026-08-24'),
            cell('2026-08-31'),
            cell('2026-09-07'),
        ];

        expect(monthLabels(cells, 'weekly')).toEqual(['Aug', null, 'Sep']);
    });

    it('returns nothing for the sparse cadences, which label their own cells', () => {
        expect(monthLabels([cell('2026-08-01')], 'monthly')).toEqual([]);
        expect(monthLabels([cell('2026-01-01')], 'annually')).toEqual([]);
    });
});

describe('cellLabel', () => {
    it('names the month for a monthly cell', () => {
        expect(cellLabel(cell('2026-08-01'), 'monthly')).toBe('Aug');
    });

    it('names the year for an annual cell', () => {
        expect(cellLabel(cell('2026-01-01'), 'annually')).toBe('2026');
    });
});

describe('tooltipDate', () => {
    it('names the full date for a daily or weekly cell', () => {
        expect(tooltipDate(cell('2026-08-31'), 'daily')).toBe(
            moment([2026, 7, 31]).format('LL'),
        );
        expect(tooltipDate(cell('2026-08-31'), 'weekly')).toBe(
            moment([2026, 7, 31]).format('LL'),
        );
    });

    it('drops the day for the sparse cadences', () => {
        expect(tooltipDate(cell('2026-08-01'), 'monthly')).toBe('August 2026');
        expect(tooltipDate(cell('2026-01-01'), 'annually')).toBe('2026');
    });
});

describe('weekdayLabels', () => {
    it('runs Monday first, matching the window the backend opens on', () => {
        const labels = weekdayLabels();

        expect(labels).toHaveLength(7);
        expect(labels[0]).toBe(moment().startOf('isoWeek').format('ddd'));
        expect(labels[6]).toBe(
            moment().startOf('isoWeek').add(6, 'days').format('ddd'),
        );
    });
});

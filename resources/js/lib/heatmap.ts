import moment, { type Moment } from 'moment';

export type HeatmapCadence = 'daily' | 'weekly' | 'monthly' | 'annually';

export interface HeatmapCell {
    date: string;
    value: number;
}

export interface HeatmapPayload {
    cadence: HeatmapCadence;
    cells: HeatmapCell[];
    total: number;
}

/**
 * Parse a cell's `YYYY-MM-DD` anchor as a calendar date.
 *
 * The explicit format keeps the day intact. `new Date('2026-08-31')` reads the
 * string as UTC midnight, which lands on the previous day for anyone west of
 * Greenwich, and the backend deliberately stores `entry_date` as a calendar
 * date rather than an instant.
 */
export const parseCellDate = (date: string): Moment =>
    moment(date, 'YYYY-MM-DD');

/**
 * The colour step a cell's value maps to.
 *
 * A recurring goal holds at most one entry per recurrence period, so the scale
 * has two stops today. It stays a function so a quantifiable heatmap can add
 * buckets without touching the payload or the layout.
 */
export const cellLevel = (value: number): number => (value > 0 ? 1 : 0);

/**
 * How many rows the grid flows its cells down.
 *
 * Seven rows turn a contiguous run of days into calendar weeks, one row per
 * weekday. Every other cadence is a single band.
 */
export const rowCount = (cadence: HeatmapCadence): number =>
    cadence === 'daily' ? 7 : 1;

/**
 * Whether the cadence renders as a dense grid with a month strip, or as a row
 * of wide cells that each carry their own label.
 */
export const isDenseCadence = (cadence: HeatmapCadence): boolean =>
    cadence === 'daily' || cadence === 'weekly';

/**
 * The first cell of each grid column, in order.
 *
 * A daily grid fills seven cells per column; every other cadence puts one cell
 * in each.
 */
export const columnStarts = (
    cells: HeatmapCell[],
    cadence: HeatmapCadence,
): HeatmapCell[] =>
    cadence === 'daily'
        ? cells.filter((_, index) => index % 7 === 0)
        : [...cells];

/**
 * A month name above each column that opens a new month, and null elsewhere.
 *
 * The first column is always labelled, since the window starts mid-month and
 * the reader would otherwise have no anchor until the next change.
 */
export const monthLabels = (
    cells: HeatmapCell[],
    cadence: HeatmapCadence,
): (string | null)[] => {
    if (!isDenseCadence(cadence)) {
        return [];
    }

    let previousMonth: number | null = null;

    return columnStarts(cells, cadence).map((cell) => {
        const start = parseCellDate(cell.date);
        const month = start.month();

        if (month === previousMonth) {
            return null;
        }

        previousMonth = month;

        return start.format('MMM');
    });
};

/**
 * The standalone label a wide cell carries beneath it.
 */
export const cellLabel = (cell: HeatmapCell, cadence: HeatmapCadence): string =>
    parseCellDate(cell.date).format(cadence === 'monthly' ? 'MMM' : 'YYYY');

/**
 * The date a tooltip names, formatted for the period the cell covers.
 */
export const tooltipDate = (
    cell: HeatmapCell,
    cadence: HeatmapCadence,
): string => {
    const anchor = parseCellDate(cell.date);

    return anchor.format(
        {
            daily: 'LL',
            weekly: 'LL',
            monthly: 'MMMM YYYY',
            annually: 'YYYY',
        }[cadence],
    );
};

/**
 * The weekday names running down the left of a daily grid, starting Monday.
 *
 * Weeks start Monday to match `Carbon::startOfWeek()` and the ISO week the
 * backend already buckets weekly goals into, so the grid and the streak agree
 * on where a week begins.
 */
export const weekdayLabels = (): string[] =>
    Array.from({ length: 7 }, (_, index) =>
        moment().startOf('isoWeek').add(index, 'days').format('ddd'),
    );

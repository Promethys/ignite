import moment from 'moment';

export const readCssVar = (name: string): string =>
    typeof document !== 'undefined'
        ? getComputedStyle(document.documentElement)
              .getPropertyValue(name)
              .trim()
        : '';

export const prefersReducedMotion = (): boolean =>
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Format a chronological list of `YYYY-MM` months for a chart axis.
 *
 * Shows `MMM`, adding a two-digit year at the two points where the year is
 * otherwise unknowable: the first label, and every January. Twelve full
 * `MMM, YYYY` labels do not fit a half-width card and force rotation.
 */
export const formatMonthLabels = (months: string[]): string[] =>
    months.map((month, index) => {
        const parsed = moment(month, 'YYYY-MM');
        const needsYear = index === 0 || parsed.month() === 0;

        return parsed.format(needsYear ? "MMM 'YY" : 'MMM');
    });

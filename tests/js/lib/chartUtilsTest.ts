import { formatMonthLabels } from '@/lib/chart-utils';
import { describe, expect, it } from 'vitest';

describe('formatMonthLabels', () => {
    it('anchors the first label and each January with a year', () => {
        const months = [
            '2025-08',
            '2025-09',
            '2025-10',
            '2025-11',
            '2025-12',
            '2026-01',
            '2026-02',
            '2026-03',
            '2026-04',
            '2026-05',
            '2026-06',
            '2026-07',
        ];

        expect(formatMonthLabels(months)).toEqual([
            "Aug '25",
            'Sep',
            'Oct',
            'Nov',
            'Dec',
            "Jan '26",
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
        ]);
    });

    it('does not repeat the year when the window opens in January', () => {
        expect(formatMonthLabels(['2026-01', '2026-02'])).toEqual([
            "Jan '26",
            'Feb',
        ]);
    });

    it('spans more than one rollover', () => {
        const labels = formatMonthLabels([
            '2024-12',
            '2025-01',
            '2025-12',
            '2026-01',
        ]);

        expect(labels).toEqual(["Dec '24", "Jan '25", 'Dec', "Jan '26"]);
    });

    it('returns an empty array for no months', () => {
        expect(formatMonthLabels([])).toEqual([]);
    });
});

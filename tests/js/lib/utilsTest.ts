import { describe, expect, it } from 'vitest';
import { getDateDiffFromNow } from '@/lib/utils';

describe('getDateDiffFromNow', () => {
    it('returns 0 for today', () => {
        const today = new Date().toISOString();

        expect(getDateDiffFromNow(today)).toBe(0);
    });

    it('returns negative value for past dates', () => {
        const past = new Date(Date.now() - 5 * 24 * 60 * 60 * 1000).toISOString();

        expect(getDateDiffFromNow(past)).toBeLessThan(0);
    });

    it('returns positive value for future dates', () => {
        const future = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString();

        expect(getDateDiffFromNow(future)).toBeGreaterThan(0);
    });
});

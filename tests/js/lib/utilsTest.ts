import { describe, expect, it } from 'vitest';
import {
    cn,
    formatDate,
    getDateDiffFromNow,
    nullToEmpty,
    nullToUndefined,
    toTitleCase,
    toUrl,
    urlIsActive,
} from '@/lib/utils';

// =========================================================================
// getDateDiffFromNow
// =========================================================================

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

// =========================================================================
// cn (className merge)
// =========================================================================

describe('cn', () => {
    it('merges class names', () => {
        expect(cn('px-2', 'py-1')).toBe('px-2 py-1');
    });

    it('deduplicates conflicting tailwind classes', () => {
        expect(cn('px-2', 'px-4')).toBe('px-4');
    });

    it('handles conditional classes', () => {
        expect(cn('base', false && 'hidden', 'extra')).toBe('base extra');
    });

    it('returns empty string for no arguments', () => {
        expect(cn()).toBe('');
    });

    it('handles undefined and null values', () => {
        expect(cn('base', undefined, null, 'end')).toBe('base end');
    });
});

// =========================================================================
// toTitleCase
// =========================================================================

describe('toTitleCase', () => {
    it('capitalizes the first letter of each word', () => {
        expect(toTitleCase('hello world')).toBe('Hello World');
    });

    it('lowercases remaining letters of each word', () => {
        expect(toTitleCase('HELLO WORLD')).toBe('Hello World');
    });

    it('handles single word', () => {
        expect(toTitleCase('in_progress')).toBe('In_progress');
    });

    it('handles empty string', () => {
        expect(toTitleCase('')).toBe('');
    });
});

// =========================================================================
// toUrl
// =========================================================================

describe('toUrl', () => {
    it('returns string href as-is', () => {
        expect(toUrl('/goals')).toBe('/goals');
    });

    it('extracts url from object href', () => {
        expect(toUrl({ url: '/goals/1', method: 'get' })).toBe('/goals/1');
    });

    it('returns undefined for undefined href', () => {
        expect(toUrl(undefined)).toBeUndefined();
    });
});

// =========================================================================
// urlIsActive
// =========================================================================

describe('urlIsActive', () => {
    it('returns true when string url matches current url', () => {
        expect(urlIsActive('/goals', '/goals')).toBe(true);
    });

    it('returns false when urls differ', () => {
        expect(urlIsActive('/goals', '/categories')).toBe(false);
    });

    it('compares object href url property against current url', () => {
        expect(urlIsActive({ url: '/goals', method: 'get' }, '/goals')).toBe(true);
    });
});

// =========================================================================
// nullToEmpty
// =========================================================================

describe('nullToEmpty', () => {
    it('returns empty string for null', () => {
        expect(nullToEmpty(null)).toBe('');
    });

    it('returns empty string for undefined', () => {
        expect(nullToEmpty(undefined)).toBe('');
    });

    it('returns the string value when provided', () => {
        expect(nullToEmpty('hello')).toBe('hello');
    });

    it('returns empty string for empty string input', () => {
        expect(nullToEmpty('')).toBe('');
    });
});

// =========================================================================
// nullToUndefined
// =========================================================================

describe('nullToUndefined', () => {
    it('returns undefined for null', () => {
        expect(nullToUndefined(null)).toBeUndefined();
    });

    it('returns undefined for undefined', () => {
        expect(nullToUndefined(undefined)).toBeUndefined();
    });

    it('returns the number value when provided', () => {
        expect(nullToUndefined(42)).toBe(42);
    });

    it('returns zero when zero is provided', () => {
        expect(nullToUndefined(0)).toBe(0);
    });
});

// =========================================================================
// formatDate
// =========================================================================

describe('formatDate', () => {
    it('formats a date string with month and day', () => {
        const result = formatDate('2026-03-15');
        expect(result).toContain('Mar');
        expect(result).toContain('15');
    });

    it('formats a full ISO date string', () => {
        const result = formatDate('2026-12-01T00:00:00Z');
        expect(result).toContain('Dec');
        expect(result).toContain('1');
    });
});

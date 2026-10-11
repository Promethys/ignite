import { contrastingTextColor } from '@/lib/contrasting-text-color';
import { describe, expect, it } from 'vitest';

describe('contrastingTextColor', () => {
    it.each([
        ['#000000', '#ffffff'],
        ['#ffffff', '#111111'],
        ['#7c3bed', '#ffffff'],
        ['#2563eb', '#ffffff'],
        ['#fde047', '#111111'],
        ['#f97316', '#111111'],
        ['#FDE047', '#111111'],
    ])('picks the readable text colour on %s', (background, expected) => {
        expect(contrastingTextColor(background)).toBe(expected);
    });
});

import {
    barChartOptions,
    baseChartOptions,
    donutChartOptions,
    lineChartOptions,
} from '@/lib/chart-theme';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/lib/chart-utils', () => ({
    readCssVar: (name: string) => `var-stub(${name})`,
    prefersReducedMotion: () => reducedMotion,
}));

let reducedMotion = false;

beforeEach(() => {
    reducedMotion = false;
});

// A colour literal anywhere in the config means a value bypassed readCssVar and
// will not follow the theme. Covers hex, rgb()/rgba() and hsl()/hsla().
const COLOUR_LITERAL = /#[0-9a-f]{3,8}\b|\brgba?\(|\bhsla?\(/i;

// Walks the config collecting every key path, so a `theme` key buried under
// `tooltip` (ApexCharts' own tooltip theme, which defaults to light) is caught,
// not just a top-level one.
const keyPaths = (value: unknown, prefix = ''): string[] => {
    if (value === null || typeof value !== 'object') {
        return [];
    }

    if (Array.isArray(value)) {
        return value.flatMap((item, i) => keyPaths(item, `${prefix}[${i}]`));
    }

    return Object.entries(value as Record<string, unknown>).flatMap(
        ([key, child]) => {
            const path = prefix ? `${prefix}.${key}` : key;

            return [path, ...keyPaths(child, path)];
        },
    );
};

const factories = {
    base: baseChartOptions,
    line: lineChartOptions,
    bar: barChartOptions,
    donut: donutChartOptions,
};

describe('chart theme factories', () => {
    it.each(Object.entries(factories))(
        '%s config carries no colour literal',
        (_name, factory) => {
            expect(JSON.stringify(factory())).not.toMatch(COLOUR_LITERAL);
        },
    );

    it.each(Object.entries(factories))(
        '%s config never sets theme at any depth',
        (_name, factory) => {
            const paths = keyPaths(factory());
            const themePaths = paths.filter(
                (p) => p === 'theme' || p.endsWith('.theme'),
            );
            expect(themePaths).toEqual([]);
        },
    );

    it.each(Object.entries(factories))(
        '%s config hides the toolbar',
        (_name, factory) => {
            const chart = factory().chart as Record<string, any>;
            expect(chart.toolbar.show).toBe(false);
        },
    );

    it('enables animation by default', () => {
        const chart = baseChartOptions().chart as Record<string, any>;
        expect(chart.animations.enabled).toBe(true);
    });

    it('disables animation under prefers-reduced-motion', () => {
        reducedMotion = true;
        const chart = baseChartOptions().chart as Record<string, any>;
        expect(chart.animations.enabled).toBe(false);
    });

    it('draws no vertical grid and a horizontal grid', () => {
        const grid = baseChartOptions().grid as Record<string, any>;
        expect(grid.xaxis.lines.show).toBe(false);
        expect(grid.yaxis.lines.show).toBe(true);
    });

    it('rounds bars at the data end only', () => {
        const plot = barChartOptions().plotOptions as Record<string, any>;
        expect(plot.bar.borderRadius).toBe(4);
        expect(plot.bar.borderRadiusApplication).toBe('end');
    });

    it('keeps a transparent chart background', () => {
        const chart = baseChartOptions().chart as Record<string, any>;
        expect(chart.background).toBe('transparent');
    });

    it('gives every donut centre label a non-empty colour', () => {
        const labels = (donutChartOptions().plotOptions as Record<string, any>)
            .pie.donut.labels;

        expect(labels.name.color).toBeTruthy();
        expect(labels.value.color).toBeTruthy();
        expect(labels.total.color).toBeTruthy();
    });
});

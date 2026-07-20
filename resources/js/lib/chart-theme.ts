import { prefersReducedMotion, readCssVar } from '@/lib/chart-utils';

export type ChartOptions = Record<string, unknown>;

/**
 * Shared ApexCharts configuration.
 *
 * `theme.mode` is deliberately never set. It paints its own plot background and
 * text colours, which override the app tokens and produce a grey slab that
 * fights the card surface in dark mode. Every colour here resolves through
 * readCssVar instead, so a chart inherits whatever the active theme defines.
 */
export function baseChartOptions(): ChartOptions {
    const border = readCssVar('--border');
    const muted = readCssVar('--muted-foreground');

    return {
        chart: {
            background: 'transparent',
            fontFamily: 'inherit',
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: !prefersReducedMotion() },
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: border,
            strokeDashArray: 0,
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
            padding: { left: 8, right: 8 },
        },
        xaxis: {
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: muted, fontSize: '11px' } },
        },
        yaxis: {
            labels: { style: { colors: muted, fontSize: '11px' } },
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            markers: { size: 6 },
            labels: { colors: muted },
            fontSize: '12px',
        },
        tooltip: {
            style: { fontSize: '12px' },
        },
        stroke: { curve: 'smooth', width: 2, lineCap: 'round' },
    };
}

export function lineChartOptions(): ChartOptions {
    const base = baseChartOptions();

    return {
        ...base,
        chart: { ...(base.chart as object), type: 'line' },
        markers: { size: 0, hover: { size: 5 } },
        colors: [readCssVar('--chart-1')],
    };
}

export function barChartOptions(): ChartOptions {
    const base = baseChartOptions();

    return {
        ...base,
        chart: { ...(base.chart as object), type: 'bar' },
        plotOptions: {
            bar: {
                borderRadius: 4,
                // Rounds the data end only. Rounding the baseline end detaches
                // the bar from its own axis.
                borderRadiusApplication: 'end',
                columnWidth: '55%',
            },
        },
        colors: [readCssVar('--chart-1')],
    };
}

export function donutChartOptions(): ChartOptions {
    const base = baseChartOptions();
    const foreground = readCssVar('--foreground');
    const muted = readCssVar('--muted-foreground');

    return {
        ...base,
        chart: { ...(base.chart as object), type: 'donut' },
        // No colours here. The donut is driven by each Category.color, which is
        // user-chosen and deliberately left ungoverned.
        stroke: { width: 2, colors: [readCssVar('--card')] },
        legend: {
            ...(base.legend as object),
            position: 'right',
            // ApexCharts draws the marker flush against its label by default.
            // Negative offsetX pulls the marker away from the text.
            markers: { size: 6, offsetX: -6 },
        },
        plotOptions: {
            pie: {
                donut: {
                    labels: {
                        show: true,
                        // ApexCharts tunes these for a white background, so the
                        // centre "Total" label and value render as dark grey on
                        // the dark card. Pull every label colour from the theme.
                        name: { color: muted },
                        value: { color: foreground },
                        total: {
                            show: true,
                            showAlways: true,
                            // total.color colours the label text. total.label
                            // itself is a string (the centre wording), set by
                            // each consumer.
                            color: muted,
                        },
                    },
                },
            },
        },
    };
}

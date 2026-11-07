import { useChartTheme } from '@/composables/useChartTheme';
import { baseChartOptions } from '@/lib/chart-theme';
import { mount } from '@vue/test-utils';
import type { ApexOptions } from 'apexcharts';
import { currentLocale } from 'laravel-vue-i18n';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick, type Ref } from 'vue';

vi.mock('laravel-vue-i18n', async () => {
    const { ref } = await import('vue');
    return { currentLocale: ref('en') };
});

const locale = currentLocale as Ref<string>;
const yAxis = (options: ApexOptions) =>
    options.yaxis as Exclude<ApexOptions['yaxis'], unknown[]>;
const tooltip = (options: ApexOptions) =>
    options.tooltip?.y as Exclude<
        NonNullable<ApexOptions['tooltip']>['y'],
        unknown[]
    >;

afterEach(() => {
    locale.value = 'en';
});

describe('chart locale', () => {
    it('registers French dates and formats axis and tooltip numbers in French', () => {
        locale.value = 'fr';
        const options = baseChartOptions();

        expect(options.chart?.defaultLocale).toBe('fr');
        const registered = options.chart?.locales ?? [];
        expect(registered.map((entry) => entry.name)).toEqual(['en', 'fr']);
        expect(
            registered.find((entry) => entry.name === 'fr')?.options
                ?.shortMonths?.[7],
        ).toBe('août');
        expect(yAxis(options)?.labels?.formatter?.(419.4)).toBe('419,4');
        expect(tooltip(options)?.formatter?.(1419.4, {})).toBe('1\u202f419,4');
    });

    it('recomputes mounted chart options after a locale switch', async () => {
        let options!: Ref<ApexOptions>;
        const wrapper = mount(
            defineComponent({
                setup() {
                    options = useChartTheme(baseChartOptions);
                    return () => null;
                },
            }),
        );

        expect(options.value.chart?.defaultLocale).toBe('en');
        expect(yAxis(options.value)?.labels?.formatter?.(419.4)).toBe('419.4');

        locale.value = 'fr';
        await nextTick();

        expect(options.value.chart?.defaultLocale).toBe('fr');
        expect(yAxis(options.value)?.labels?.formatter?.(419.4)).toBe('419,4');

        locale.value = 'en';
        await nextTick();

        expect(options.value.chart?.defaultLocale).toBe('en');
        expect(tooltip(options.value)?.formatter?.(1419.4, {})).toBe('1,419.4');
        wrapper.unmount();
    });
});

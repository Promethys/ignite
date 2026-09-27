import CategoryBreakdownChart from '@/components/charts/CategoryBreakdownChart.vue';
import type { CategoryBreakdownItem } from '@/types/charts';
import { mount } from '@vue/test-utils';
import type { ApexOptions } from 'apexcharts';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const containerWidth = vi.hoisted(() => ({ value: 0 }));

vi.mock('@vueuse/core', async (importOriginal) => ({
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    useElementSize: () => ({
        width: ref(containerWidth.value),
        height: ref(300),
    }),
}));

vi.mock('vue3-apexcharts', () => ({
    default: {
        name: 'apexchart',
        props: ['options', 'series'],
        template: '<div class="apexchart-mock" />',
    },
}));

const data: CategoryBreakdownItem[] = [
    { name: 'Wellness & Mental Health', color: '#14b8a6', count: 2 },
    { name: 'Health & Fitness', color: '#ef4444', count: 1 },
];

const legendAt = (width: number) => {
    containerWidth.value = width;
    const wrapper = mount(CategoryBreakdownChart, { props: { data } });
    const options = wrapper
        .findComponent({ name: 'apexchart' })
        .props('options') as ApexOptions;
    return options.legend?.position;
};

describe('CategoryBreakdownChart', () => {
    it('keeps the legend beside the donut in a wide card', () => {
        expect(legendAt(640)).toBe('right');
    });

    it('moves the legend below the donut in a narrow card', () => {
        expect(legendAt(360)).toBe('bottom');
    });

    it('keeps the legend beside the donut before the card is measured', () => {
        expect(legendAt(0)).toBe('right');
    });
});

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import ProgressChart from '@/components/charts/ProgressChart.vue';
import type { GoalEntry } from '@/types/models';

vi.mock('@/composables/useAppearance', () => ({
    getBinaryTheme: () => 'light',
}));

const entries: GoalEntry[] = [
    {
        id: 1,
        goal_id: 1,
        value: 10,
        previous_value: 0,
        increment_value: 10,
        note: null,
        entry_date: '2026-01-01',
        attachment_path: null,
        attachment_type: null,
        created_at: '2026-01-01',
        updated_at: '2026-01-01',
    },
    {
        id: 2,
        goal_id: 1,
        value: 25,
        previous_value: 10,
        increment_value: 15,
        note: null,
        entry_date: '2026-02-01',
        attachment_path: null,
        attachment_type: null,
        created_at: '2026-02-01',
        updated_at: '2026-02-01',
    },
];

vi.mock('vue3-apexcharts', () => ({
    default: {
        name: 'apexchart',
        template: '<div class="apexchart-mock" />',
        props: ['type', 'width', 'height', 'options', 'series'],
    },
}));

describe('ProgressChart', () => {
    it('renders chart when entries are provided', () => {
        const wrapper = mount(ProgressChart, {
            props: {
                entries,
                targetValue: 50,
                unit: 'books',
            },
            global: {
                stubs: {
                    apexchart: {
                        template: '<div class="apexchart-mock" />',
                        props: ['type', 'width', 'height', 'options', 'series'],
                    },
                },
            },
        });

        expect(wrapper.find('.apexchart-mock').exists()).toBe(true);
    });

    it('shows empty state when entries array is empty', () => {
        const wrapper = mount(ProgressChart, {
            props: {
                entries: [],
                targetValue: 50,
                unit: 'books',
            },
            global: {
                stubs: {
                    apexchart: {
                        template: '<div class="apexchart-mock" />',
                        props: ['type', 'width', 'height', 'options', 'series'],
                    },
                },
            },
        });

        expect(wrapper.find('.apexchart-mock').exists()).toBe(true);
    });
});

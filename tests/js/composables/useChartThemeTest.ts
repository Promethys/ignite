import { useChartTheme } from '@/composables/useChartTheme';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';

// Stands in for the real factory: returns whatever the `dark` class implies, so
// the test asserts the re-read happens rather than asserting a specific colour.
const factory = () => ({
    colors: [document.documentElement.classList.contains('dark') ? 'D' : 'L'],
});

const Probe = defineComponent({
    setup() {
        const options = useChartTheme(factory);

        return () => h('span', JSON.stringify(options.value));
    },
});

afterEach(() => {
    document.documentElement.classList.remove('dark');
});

describe('useChartTheme', () => {
    it('produces the current theme options on mount', () => {
        expect(mount(Probe).text()).toContain('"L"');
    });

    it('re-reads the options when the dark class is added', async () => {
        const wrapper = mount(Probe);
        expect(wrapper.text()).toContain('"L"');

        document.documentElement.classList.add('dark');
        await new Promise((resolve) => setTimeout(resolve, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('"D"');
    });

    it('re-reads the options when the dark class is removed', async () => {
        document.documentElement.classList.add('dark');
        const wrapper = mount(Probe);
        expect(wrapper.text()).toContain('"D"');

        document.documentElement.classList.remove('dark');
        await new Promise((resolve) => setTimeout(resolve, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('"L"');
    });
});

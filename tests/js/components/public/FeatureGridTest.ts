import FeatureGrid from '@/components/public/FeatureGrid.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('FeatureGrid', () => {
    it('renders one cell per feature', () => {
        const wrapper = mount(FeatureGrid);

        expect(wrapper.findAll('li').length).toBeGreaterThan(0);
        expect(wrapper.findAll('li')).toHaveLength(
            wrapper.findAll('h4').length,
        );
    });

    it('asks for a title and a description key for every feature', () => {
        const wrapper = mount(FeatureGrid);

        const titles = wrapper.findAll('li > h4').map((node) => node.text());
        const descriptions = wrapper
            .findAll('li > p')
            .map((node) => node.text());

        expect(titles).toHaveLength(descriptions.length);

        titles.forEach((title, position) => {
            expect(title).toMatch(/^landing\.features\.[a-z0-9]+\.title$/);
            expect(descriptions[position]).toBe(
                title.replace(/\.title$/, '.description'),
            );
        });
    });

    it('renders the section heading and the feature count', () => {
        const wrapper = mount(FeatureGrid);

        const heading = wrapper.find('section > div');

        expect(heading.text()).toContain('landing.features.section_title');
        expect(heading.text()).toContain(String(wrapper.findAll('li').length));
    });

    it('renders translated copy rather than raw keys when translations exist', () => {
        const wrapper = mount(FeatureGrid, {
            global: {
                mocks: {
                    $t: (key: string) =>
                        key === 'landing.features.mcp.title'
                            ? 'Manage goals from your AI client'
                            : key,
                },
            },
        });

        expect(wrapper.text()).toContain('Manage goals from your AI client');
    });
});

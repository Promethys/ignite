import FeatureRows from '@/components/public/FeatureRows.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const rowSelector = 'section > div > div';

describe('FeatureRows', () => {
    it('renders one row per feature', () => {
        const wrapper = mount(FeatureRows);

        expect(wrapper.findAll(rowSelector).length).toBeGreaterThan(0);
        expect(wrapper.findAll(rowSelector)).toHaveLength(
            wrapper.findAll('h4').length,
        );
    });

    it('numbers the rows one-based and zero-padded', () => {
        const wrapper = mount(FeatureRows);

        const indexes = wrapper
            .findAll(`${rowSelector} > span`)
            .map((node) => node.text());

        expect(indexes[0]).toBe('01');
        expect(indexes[1]).toBe('02');
        expect(indexes).toEqual(
            indexes.map((_, position) =>
                String(position + 1).padStart(2, '0'),
            ),
        );
    });

    it('asks for a title and a description key for every feature', () => {
        const wrapper = mount(FeatureRows);

        const titles = wrapper.findAll('h4').map((node) => node.text());
        const descriptions = wrapper
            .findAll(`${rowSelector} > p`)
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
        const wrapper = mount(FeatureRows);

        const [heading] = wrapper.findAll('section > div');

        expect(heading.text()).toContain('landing.features.section_title');
        expect(heading.text()).toContain(String(wrapper.findAll('h4').length));
    });

    it('renders translated copy rather than raw keys when translations exist', () => {
        const wrapper = mount(FeatureRows, {
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

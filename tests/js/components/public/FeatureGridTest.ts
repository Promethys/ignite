import FeatureGrid from '@/components/public/FeatureGrid.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const linkStub = {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
};

const mountGrid = () =>
    mount(FeatureGrid, {
        global: { stubs: { Link: linkStub } },
    });

describe('FeatureGrid', () => {
    it('renders one cell per feature', () => {
        const wrapper = mountGrid();

        expect(wrapper.findAll('li')).toHaveLength(9);
        expect(wrapper.get('h2').text()).toBe('landing.features.section_title');
        expect(wrapper.findAll('li')).toHaveLength(
            wrapper.findAll('h3').length,
        );
    });

    it('asks for a title and a description key for every feature', () => {
        const wrapper = mountGrid();

        const titles = wrapper.findAll('li > h3').map((node) => node.text());
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
        const wrapper = mountGrid();

        const heading = wrapper.find('section > div');

        expect(heading.text()).toContain('landing.features.section_title');
        expect(heading.text()).toContain(String(wrapper.findAll('li').length));
    });

    it('renders translated copy rather than raw keys when translations exist', () => {
        const wrapper = mount(FeatureGrid, {
            global: {
                stubs: { Link: linkStub },
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

    it('links every card to the corresponding features section', () => {
        const wrapper = mountGrid();

        expect(
            wrapper.findAll('li a').map((link) => link.attributes('href')),
        ).toEqual([
            '/features#goal-types',
            '/features#progress',
            '/features#streaks',
            '/features#milestones',
            '/features#make-it-yours',
            '/features#assistant',
            '/features#mcp',
            '/features#your-data',
            '/features#make-it-yours',
        ]);
    });
});

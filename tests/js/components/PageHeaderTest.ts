import PageHeader from '@/components/PageHeader.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('PageHeader', () => {
    it('renders the title', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Milestones' },
        });

        expect(wrapper.find('h1').text()).toBe('Milestones');
    });

    it('renders the description when provided', () => {
        const wrapper = mount(PageHeader, {
            props: {
                title: 'Milestones',
                description: 'Track your checkpoints',
            },
        });

        expect(wrapper.text()).toContain('Track your checkpoints');
    });

    it('does not render a description paragraph when none is given', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Milestones' },
        });

        expect(wrapper.find('p').exists()).toBe(false);
    });

    it('renders content passed to the actions slot', () => {
        const wrapper = mount(PageHeader, {
            props: { title: 'Milestones' },
            slots: { actions: '<button>Add milestone</button>' },
        });

        expect(wrapper.find('button').exists()).toBe(true);
        expect(wrapper.text()).toContain('Add milestone');
    });
});

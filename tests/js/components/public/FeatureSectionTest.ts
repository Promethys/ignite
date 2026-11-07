import FeatureSection from '@/components/public/FeatureSection.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const props = {
    id: 'progress',
    index: 2,
    title: 'See your progress',
    description: 'Every entry moves you forward.',
};

describe('FeatureSection', () => {
    it('exposes a keyboard reachable permalink and a padded index', () => {
        const wrapper = mount(FeatureSection, { props });

        expect(wrapper.attributes('id')).toBe('progress');
        expect(wrapper.get('h2 a').attributes('href')).toBe('#progress');
        expect(wrapper.get('h2 a').attributes('tabindex')).not.toBe('-1');
        expect(wrapper.text()).toContain('02');
    });

    it('renders the description, points and optional documentation link', () => {
        const wrapper = mount(FeatureSection, {
            props: {
                ...props,
                points: ['Daily entries', 'A clear history'],
                docsHref: 'https://example.test/guide/progress',
            },
        });

        expect(wrapper.text()).toContain(props.description);
        expect(wrapper.findAll('li').map((point) => point.text())).toEqual([
            'Daily entries',
            'A clear history',
        ]);
        expect(
            wrapper
                .find('a[href="https://example.test/guide/progress"]')
                .exists(),
        ).toBe(true);
    });

    it('uses the shared external link only for off-site documentation', async () => {
        const wrapper = mount(FeatureSection, {
            props: {
                ...props,
                docsHref:
                    'https://ignite-docs.promethys.dev/features/encryption',
            },
        });
        const link = wrapper.get(
            'a[href="https://ignite-docs.promethys.dev/features/encryption"]',
        );
        expect(link.text()).toContain('public.read_developer_docs');
        expect(link.attributes('target')).toBe('_blank');
        expect(link.attributes('rel')).toBe('noopener');
        expect(link.get('.sr-only').text()).toBe('public.opens_in_new_tab');
        expect(link.findAll('svg')).toHaveLength(1);
        await wrapper.setProps({ docsHref: '/docs/goal-types' });
        expect(wrapper.get('a[href="/docs/goal-types"]').text()).toContain(
            'public.read_docs',
        );
        expect(
            wrapper.get('a[href="/docs/goal-types"]').attributes('target'),
        ).toBeUndefined();
        wrapper.unmount();
    });

    it('reverses the desktop visual order without losing its content', async () => {
        const wrapper = mount(FeatureSection, {
            props,
            slots: { default: '<div>Live visual</div>' },
        });
        expect(
            wrapper
                .get('h2')
                .element.parentElement?.classList.contains('xl:order-2'),
        ).toBe(false);

        await wrapper.setProps({ reverse: true });

        expect(
            wrapper
                .get('h2')
                .element.parentElement?.classList.contains('xl:order-2'),
        ).toBe(true);
        expect(wrapper.get('div.grid > div:nth-child(2)').classes()).toContain(
            'xl:order-1',
        );
        expect(wrapper.text()).toContain('Live visual');
        expect(wrapper.get('h2').text()).toContain(props.title);
    });

    it('omits optional lists and documentation links', () => {
        const wrapper = mount(FeatureSection, { props });

        expect(wrapper.find('ul').exists()).toBe(false);
        expect(wrapper.findAll('a')).toHaveLength(1);
    });
});

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppLogoIcon', () => {
    it('renders the three chevrons of the mark', () => {
        const wrapper = mount(AppLogoIcon);

        expect(wrapper.findAll('path')).toHaveLength(3);
    });

    it('tapers the stroke from base to tip', () => {
        const wrapper = mount(AppLogoIcon);

        const widths = wrapper
            .findAll('path')
            .map((path) => Number(path.attributes('stroke-width')));

        expect(widths[0]).toBeGreaterThan(widths[1]);
        expect(widths[1]).toBeGreaterThan(widths[2]);
    });

    it('colours each chevron from its own theme token by default', () => {
        const wrapper = mount(AppLogoIcon);

        const strokes = wrapper
            .findAll('path')
            .map((path) => path.attributes('stroke'));

        expect(strokes).toEqual([
            'var(--brand-mark-base)',
            'var(--brand-mark-mid)',
            'var(--brand-mark-tip)',
        ]);
    });

    it('falls back to the inherited colour when monochrome', () => {
        const wrapper = mount(AppLogoIcon, { props: { monochrome: true } });

        const strokes = wrapper
            .findAll('path')
            .map((path) => path.attributes('stroke'));

        expect(strokes).toEqual([
            'currentColor',
            'currentColor',
            'currentColor',
        ]);
    });

    it('passes attributes through to the svg element', () => {
        const wrapper = mount(AppLogoIcon, {
            attrs: { class: 'size-8', 'aria-hidden': 'true' },
        });

        const svg = wrapper.get('svg');

        expect(svg.classes()).toContain('size-8');
        expect(svg.attributes('aria-hidden')).toBe('true');
    });
});

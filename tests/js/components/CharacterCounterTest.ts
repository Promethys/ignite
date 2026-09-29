import CharacterCounter from '@/components/CharacterCounter.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountCounter = (value: string | null, max = 10) =>
    mount(CharacterCounter, { props: { value, max } });

describe('CharacterCounter', () => {
    it('stays empty below 80% of the limit', () => {
        const wrapper = mountCounter('1234567');

        expect(wrapper.text()).toBe('');
    });

    it('shows the count in the warning colour from 80% of the limit', () => {
        const wrapper = mountCounter('12345678');

        expect(wrapper.text()).toContain('8/10');
        expect(wrapper.classes()).toContain('text-warning');
    });

    it('turns destructive once over the limit', () => {
        const wrapper = mountCounter('12345678901');

        expect(wrapper.text()).toContain('11/10');
        expect(wrapper.classes()).toContain('text-destructive');
    });

    it('stays warning at exactly the limit', () => {
        expect(mountCounter('1234567890').classes()).toContain('text-warning');
    });

    it('counts an emoji as one character, like the server', () => {
        const wrapper = mountCounter('🔥🔥🔥🔥🔥🔥🔥🔥');

        expect(wrapper.text()).toContain('8/10');
    });

    it('treats a null value as empty', () => {
        expect(mountCounter(null).text()).toBe('');
    });

    it('announces the count to screen readers', () => {
        const wrapper = mountCounter('12345678');

        expect(wrapper.attributes('aria-live')).toBe('polite');
        expect(wrapper.find('.sr-only').text()).toBe(
            'common.form.character_count 8 10',
        );
    });
});

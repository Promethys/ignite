import EntryNote from '@/components/goal_entries/EntryNote.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountNote = (note: string, overflows = false) =>
    mount(EntryNote, {
        props: { note },
        global: {
            mocks: {
                $t: (key: string) => key,
            },
        },
        attachTo: overflows ? document.body : undefined,
    });

describe('EntryNote', () => {
    it('renders the note as text so markup in it is never executed', () => {
        const wrapper = mountNote('<img src=x onerror="alert(1)">');

        expect(wrapper.text()).toContain('<img src=x onerror="alert(1)">');
        expect(wrapper.find('img').exists()).toBe(false);
        expect(wrapper.html()).toContain('&lt;img');
    });

    it('preserves line breaks with css rather than markup', () => {
        const wrapper = mountNote('First line\nSecond line');
        const paragraph = wrapper.get('p');

        expect(paragraph.classes()).toContain('whitespace-pre-line');
        expect(wrapper.find('br').exists()).toBe(false);
        // Exact, not trimmed: with pre-line, template indentation would render.
        expect(paragraph.element.textContent).toBe('First line\nSecond line');
    });

    it('clamps the note until it is expanded', async () => {
        const wrapper = mountNote('a long note');

        expect(wrapper.get('p').classes()).toContain('line-clamp-3');
    });

    it('hides the toggle when the note does not overflow', () => {
        const wrapper = mountNote('short');

        expect(wrapper.find('button').exists()).toBe(false);
    });

    it('unclamps and relabels when the toggle is used', async () => {
        const wrapper = mountNote('a long note');

        // jsdom reports no layout, so drive the state the measurement sets.
        (wrapper.vm as unknown as { overflows: boolean }).overflows = true;
        await wrapper.vm.$nextTick();

        const toggle = wrapper.get('button');
        expect(toggle.text()).toBe('goals.entries.note_show_more');

        await toggle.trigger('click');

        expect(wrapper.get('p').classes()).not.toContain('line-clamp-3');
        expect(wrapper.get('button').text()).toBe(
            'goals.entries.note_show_less',
        );
    });
});

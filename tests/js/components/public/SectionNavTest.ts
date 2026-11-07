import SectionNav from '@/components/public/SectionNav.vue';
import { useScrollSpy } from '@/composables/useScrollSpy';
import { mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';

const items = [
    { id: 'goal-types', label: 'Goal types' },
    { id: 'progress', label: 'Progress' },
    { id: 'streaks', label: 'Streaks' },
];
let tops: Record<string, number>;
let frames: FrameRequestCallback[];
let wrapper: VueWrapper | undefined;

async function scroll() {
    window.dispatchEvent(new Event('scroll'));
    frames.splice(0).forEach((callback) => callback(0));
    await nextTick();
}

describe('SectionNav and useScrollSpy', () => {
    beforeEach(() => {
        tops = { 'goal-types': 100, progress: 700, streaks: 1300 };
        frames = [];
        vi.stubGlobal(
            'requestAnimationFrame',
            (callback: FrameRequestCallback) => {
                frames.push(callback);
                return frames.length;
            },
        );
        vi.stubGlobal('cancelAnimationFrame', vi.fn());
        vi.spyOn(
            document.documentElement,
            'scrollHeight',
            'get',
        ).mockReturnValue(2400);
        for (const item of items) {
            const section = document.createElement('section');
            section.id = item.id;
            vi.spyOn(section, 'getBoundingClientRect').mockImplementation(
                () =>
                    ({
                        top: tops[item.id],
                    }) as DOMRect,
            );
            document.body.append(section);
        }
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = undefined;
        items.forEach((item) => document.getElementById(item.id)?.remove());
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it('renders the section links with the first section active', () => {
        wrapper = mount(SectionNav, {
            props: { items, label: 'On this page' },
        });

        expect(wrapper.get('nav').attributes('aria-label')).toBe(
            'On this page',
        );
        expect(
            wrapper.findAll('a').map((link) => link.attributes('href')),
        ).toEqual(['#goal-types', '#progress', '#streaks']);
        expect(
            wrapper.get('a[aria-current="location"]').attributes('href'),
        ).toBe('#goal-types');
    });

    it('moves the active item as sections pass the offset', async () => {
        wrapper = mount(SectionNav, { props: { items } });
        tops.progress = 130;

        await scroll();

        expect(
            wrapper.get('a[aria-current="location"]').attributes('href'),
        ).toBe('#progress');
        expect(wrapper.findAll('a[aria-current]')).toHaveLength(1);
    });

    it('updates the active item immediately when its anchor is selected', async () => {
        wrapper = mount(SectionNav, { props: { items } });

        await wrapper.get('a[href="#streaks"]').trigger('click');

        expect(
            wrapper.get('a[aria-current="location"]').attributes('href'),
        ).toBe('#streaks');
    });

    it('honors a custom offset and removes listeners after unmounting', async () => {
        const removeListener = vi.spyOn(window, 'removeEventListener');
        const Harness = defineComponent({
            setup: () => ({
                active: useScrollSpy(
                    items.map((item) => item.id),
                    50,
                ),
            }),
            template: '<p>{{ active }}</p>',
        });
        wrapper = mount(Harness);
        tops.progress = 75;
        await scroll();
        expect(wrapper.text()).toBe('goal-types');

        tops.progress = 45;
        await scroll();
        expect(wrapper.text()).toBe('progress');

        wrapper.unmount();
        wrapper = undefined;
        expect(removeListener).toHaveBeenCalledWith(
            'scroll',
            expect.any(Function),
        );
        expect(removeListener).toHaveBeenCalledWith(
            'resize',
            expect.any(Function),
        );
    });
});

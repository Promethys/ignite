import TerminalIllustration from '@/components/public/TerminalIllustration.vue';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

let intersectionCallback: IntersectionObserverCallback;
const observe = vi.fn();
const disconnect = vi.fn();

function mockReducedMotion(matches: boolean) {
    vi.stubGlobal(
        'matchMedia',
        vi.fn(() => ({
            matches,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        })),
    );
}

describe('TerminalIllustration', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockReducedMotion(false);
        vi.stubGlobal(
            'IntersectionObserver',
            class {
                constructor(callback: IntersectionObserverCallback) {
                    intersectionCallback = callback;
                }

                observe = observe;
                disconnect = disconnect;
            },
        );
    });

    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('shows the final exchange immediately when reduced motion is requested', async () => {
        mockReducedMotion(true);
        const wrapper = mount(TerminalIllustration, {
            props: {
                client: 'Claude',
                prompt: 'Log my run',
                tool: 'log_progress',
                args: '{ goal_id: 12, increment: 12 }',
                result: 'Logged 12 km. 259 / 400 km (64%)',
                reply: 'Your run is logged.',
            },
        });
        await wrapper.vm.$nextTick();

        expect(wrapper.get('figure').attributes('data-started')).toBe('true');
        expect(wrapper.text()).toContain('Logged 12 km. 259 / 400 km (64%)');
        const label = wrapper.get('figure').attributes('aria-label');
        for (const part of [
            'Log my run',
            'log_progress',
            'Logged 12 km',
            'Your run is logged.',
        ]) {
            expect(label).toContain(part);
        }
        wrapper.unmount();
    });

    it('starts the exchange once the terminal enters the viewport', async () => {
        const wrapper = mount(TerminalIllustration);

        expect(wrapper.get('figure').attributes('data-started')).toBe('false');
        expect(observe).toHaveBeenCalledWith(wrapper.get('figure').element);

        intersectionCallback(
            [
                {
                    isIntersecting: true,
                    target: wrapper.get('figure').element,
                } as IntersectionObserverEntry,
            ],
            {} as IntersectionObserver,
        );
        await wrapper.vm.$nextTick();

        expect(wrapper.get('figure').attributes('data-started')).toBe('true');
        wrapper.unmount();
    });

    it('replays the exchange while keeping reduced motion in the final state', async () => {
        mockReducedMotion(true);
        const wrapper = mount(TerminalIllustration);
        const initialRun = Number(
            wrapper.get('[data-run]').attributes('data-run'),
        );

        await wrapper.get('button').trigger('click');

        expect(Number(wrapper.get('[data-run]').attributes('data-run'))).toBe(
            initialRun + 1,
        );
        expect(wrapper.get('figure').attributes('data-started')).toBe('true');
        wrapper.unmount();
    });
});

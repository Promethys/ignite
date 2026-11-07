import CodeBlock from '@/components/public/CodeBlock.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('laravel-vue-i18n', () => ({ trans: (key: string) => key }));

const clipboardDescriptor = Object.getOwnPropertyDescriptor(
    navigator,
    'clipboard',
);
const permissionsDescriptor = Object.getOwnPropertyDescriptor(
    navigator,
    'permissions',
);
const execCommandDescriptor = Object.getOwnPropertyDescriptor(
    document,
    'execCommand',
);
const mockClipboard = (writeText: ReturnType<typeof vi.fn>) => {
    Object.defineProperty(navigator, 'clipboard', {
        configurable: true,
        value: { writeText },
    });
    Object.defineProperty(navigator, 'permissions', {
        configurable: true,
        value: {
            query: vi
                .fn()
                .mockResolvedValue(
                    Object.assign(new EventTarget(), { state: 'granted' }),
                ),
        },
    });
    Object.defineProperty(document, 'execCommand', {
        configurable: true,
        value: vi.fn(() => {
            throw new Error('Clipboard unavailable');
        }),
    });
};

afterEach(() => {
    for (const [target, key, descriptor] of [
        [navigator, 'clipboard', clipboardDescriptor],
        [navigator, 'permissions', permissionsDescriptor],
        [document, 'execCommand', execCommandDescriptor],
    ] as const) {
        if (descriptor) Object.defineProperty(target, key, descriptor);
        else Reflect.deleteProperty(target, key);
    }
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    vi.useRealTimers();
});

describe('CodeBlock', () => {
    const code = 'git clone https://github.com/Promethys/ignite.git\ncd ignite';

    it('renders code as text with whitespace preserved', () => {
        const wrapper = mount(CodeBlock, {
            props: { code: '<script>\n' + code },
        });
        expect(wrapper.get('pre code').element.textContent).toBe(
            '<script>\n' + code,
        );
        expect(wrapper.find('script').exists()).toBe(false);
        expect(wrapper.get('pre').attributes('tabindex')).toBe('0');
        expect(wrapper.get('button').attributes('aria-label')).toBe(
            'public.docs.copy',
        );
        wrapper.unmount();
    });

    it('copies the code and announces a temporary copied state', async () => {
        vi.useFakeTimers();
        const writeText = vi.fn().mockResolvedValue(undefined);
        mockClipboard(writeText);
        const wrapper = mount(CodeBlock, { props: { code } });
        await flushPromises();
        await wrapper.get('button').trigger('click');
        await flushPromises();
        expect(writeText).toHaveBeenCalledExactlyOnceWith(code);
        expect(wrapper.get('button').text()).toBe('public.docs.copied');
        expect(wrapper.get('button').attributes('aria-live')).toBe('polite');
        await vi.advanceTimersByTimeAsync(2500);
        expect(wrapper.get('button').text()).toBe('public.docs.copy');
        wrapper.unmount();
    });

    it('reports a rejected clipboard write and allows another attempt', async () => {
        const writeText = vi
            .fn()
            .mockRejectedValueOnce(new Error('Denied'))
            .mockResolvedValue(undefined);
        mockClipboard(writeText);
        const wrapper = mount(CodeBlock, { props: { code } });
        await flushPromises();
        await wrapper.get('button').trigger('click');
        await flushPromises();
        expect(wrapper.get('button').text()).toBe('public.docs.copy_failed');
        await wrapper.get('button').trigger('click');
        await flushPromises();
        expect(wrapper.get('button').text()).toBe('public.docs.copied');
        wrapper.unmount();
    });
});

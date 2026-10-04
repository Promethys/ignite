import AssistantMessageMeta from '@/components/assistant/AssistantMessageMeta.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const copy = vi.fn();
const copied = ref(false);
const isSupported = ref(true);

vi.mock('@vueuse/core', async (original) => ({
    ...(await original<object>()),
    useClipboard: () => ({ copy, copied, isSupported }),
}));

vi.mock('@/components/ui/tooltip', () => {
    const passthrough = { template: '<div><slot /></div>' };

    return {
        Tooltip: passthrough,
        TooltipContent: {
            template: '<div data-test="tooltip"><slot /></div>',
        },
        TooltipProvider: passthrough,
        TooltipTrigger: passthrough,
    };
});

const sentAt = new Date(2026, 9, 22, 14, 12).toISOString();

const mountMeta = (props: Record<string, unknown> = {}) =>
    mount(AssistantMessageMeta, {
        props: {
            text: 'You have three goals.',
            sentAt,
            locale: 'en',
            ...props,
        },
    });

beforeEach(() => {
    vi.clearAllMocks();
    copied.value = false;
    isSupported.value = true;
});

describe('AssistantMessageMeta', () => {
    it('copies the message text from an icon with a tooltip', async () => {
        const wrapper = mountMeta();
        const button = wrapper.find('[data-test="assistant-copy"]');

        expect(button.text()).toBe('');
        expect(button.attributes('aria-label')).toBe('assistant.copy');
        expect(wrapper.find('[data-test="tooltip"]').text()).toBe(
            'assistant.copy',
        );

        await button.trigger('click');

        expect(copy).toHaveBeenCalledWith('You have three goals.');
    });

    it('says so once the text is copied', () => {
        copied.value = true;

        expect(mountMeta().find('[data-test="tooltip"]').text()).toBe(
            'assistant.copied',
        );
    });

    it.each([
        ['en', 'Oct 22, 2:12 PM'],
        ['fr', '22 oct., 14:12'],
    ])('formats the time of the message for %s', (locale, expected) => {
        const time = mountMeta({ locale }).find('time');

        expect(time.text().replace(/ /g, ' ')).toBe(expected);
        expect(time.attributes('datetime')).toBe(sentAt);
    });

    it('puts the time first when reversed', () => {
        const classes = (isReversed: boolean) =>
            mountMeta({ isReversed })
                .find('[data-test="assistant-message-meta"]')
                .classes();

        expect(classes(false)).not.toContain('flex-row-reverse');
        expect(classes(true)).toContain('flex-row-reverse');
    });

    it('shows only the time for a message without text', () => {
        const wrapper = mountMeta({ text: '' });

        expect(wrapper.find('[data-test="assistant-copy"]').exists()).toBe(
            false,
        );
        expect(wrapper.text()).not.toContain('•');
        expect(wrapper.find('time').exists()).toBe(true);
    });

    it('shows only the time where the clipboard is unavailable', () => {
        isSupported.value = false;

        expect(mountMeta().find('[data-test="assistant-copy"]').exists()).toBe(
            false,
        );
    });
});

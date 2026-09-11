import HelpTooltip from '@/components/ui/HelpTooltip.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

vi.mock('lucide-vue-next', () => ({
    CircleQuestionMark: { template: '<span class="icon-circle-question" />' },
}));

afterEach(() => {
    document.body.innerHTML = '';
});

const trigger = (wrapper: ReturnType<typeof mount>) => wrapper.find('button');

describe('HelpTooltip', () => {
    it('renders default trigger icon when no trigger slot is provided', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Helpful info' },
        });

        expect(wrapper.find('.icon-circle-question').exists()).toBe(true);
    });

    it('renders custom trigger when trigger slot is provided', () => {
        const wrapper = mount(HelpTooltip, {
            slots: {
                trigger: '<span class="custom-trigger">Custom</span>',
                default: 'Tooltip text',
            },
        });

        expect(wrapper.find('.custom-trigger').exists()).toBe(true);
        expect(wrapper.find('.icon-circle-question').exists()).toBe(false);
    });

    it('names the icon-only trigger for assistive tech', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Info' },
        });

        expect(trigger(wrapper).attributes('aria-label')).toBe(
            'common.actions.help',
        );
    });

    it('keeps the trigger out of the tab order', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Info' },
        });

        expect(trigger(wrapper).attributes('tabindex')).toBe('-1');
    });

    it('opens its content on click, without hover', async () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Some helpful text' },
            attachTo: document.body,
        });

        expect(document.body.textContent).not.toContain('Some helpful text');

        await trigger(wrapper).trigger('click');
        await flushPromises();

        expect(document.body.textContent).toContain('Some helpful text');

        wrapper.unmount();
    });

    it('does not submit the form it sits in', async () => {
        const onSubmit = vi.fn();

        const wrapper = mount(
            defineComponent({
                components: { HelpTooltip },
                setup: () => ({ onSubmit }),
                template:
                    '<form @submit.prevent="onSubmit"><HelpTooltip>Info</HelpTooltip></form>',
            }),
            { attachTo: document.body },
        );

        expect(trigger(wrapper).attributes('type')).toBe('button');

        await trigger(wrapper).trigger('click');

        expect(onSubmit).not.toHaveBeenCalled();

        wrapper.unmount();
    });
});

import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import HelpTooltip from '@/components/ui/HelpTooltip.vue';

vi.mock('lucide-vue-next', () => ({
    CircleQuestionMark: { template: '<span class="icon-circle-question" />' },
}));

const stubs = {
    TooltipProvider: { template: '<div class="tooltip-provider"><slot /></div>' },
    Tooltip: { template: '<div class="tooltip"><slot /></div>' },
    TooltipTrigger: {
        template: '<div class="tooltip-trigger" tabindex="-1"><slot /></div>',
    },
    TooltipContent: {
        template: '<div class="tooltip-content"><slot /></div>',
    },
};

describe('HelpTooltip', () => {
    it('renders default trigger icon when no trigger slot is provided', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Helpful info' },
            global: { stubs },
        });

        expect(wrapper.find('.icon-circle-question').exists()).toBe(true);
    });

    it('renders custom trigger when trigger slot is provided', () => {
        const wrapper = mount(HelpTooltip, {
            slots: {
                trigger: '<span class="custom-trigger">Custom</span>',
                default: 'Tooltip text',
            },
            global: { stubs },
        });

        expect(wrapper.find('.custom-trigger').exists()).toBe(true);
        expect(wrapper.find('.icon-circle-question').exists()).toBe(false);
    });

    it('renders tooltip content from default slot', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Some helpful text' },
            global: { stubs },
        });

        expect(wrapper.text()).toContain('Some helpful text');
    });

    it('sets tabindex="-1" on the trigger', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Info' },
            global: { stubs },
        });

        const trigger = wrapper.find('.tooltip-trigger');
        expect(trigger.attributes('tabindex')).toBe('-1');
    });

    it('wraps content in TooltipProvider', () => {
        const wrapper = mount(HelpTooltip, {
            slots: { default: 'Info' },
            global: { stubs },
        });

        expect(wrapper.find('.tooltip-provider').exists()).toBe(true);
    });
});

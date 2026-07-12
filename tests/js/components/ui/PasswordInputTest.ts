import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import PasswordInput from '@/components/ui/password-input/PasswordInput.vue';

vi.mock('lucide-vue-next', () => ({
    Eye: { template: '<span class="icon-eye" />' },
    EyeOff: { template: '<span class="icon-eye-off" />' },
}));

const en: Record<string, string> = {
    'common.password.show': 'Show password',
    'common.password.hide': 'Hide password',
};

const mountWith = () =>
    mount(PasswordInput, {
        props: { modelValue: 'secret' },
        global: {
            mocks: {
                $t: (key: string) => en[key] ?? key,
            },
        },
    });

describe('PasswordInput', () => {
    it('renders as a password input by default', () => {
        const wrapper = mountWith();

        expect(wrapper.find('input').attributes('type')).toBe('password');
    });

    it('toggles the input type when the reveal button is clicked', async () => {
        const wrapper = mountWith();

        await wrapper.find('button').trigger('click');

        expect(wrapper.find('input').attributes('type')).toBe('text');
    });

    it('updates the aria-label to match the visible state', async () => {
        const wrapper = mountWith();
        const button = wrapper.find('button');

        expect(button.attributes('aria-label')).toBe('Show password');

        await button.trigger('click');

        expect(button.attributes('aria-label')).toBe('Hide password');
    });

    it('renders the toggle as a non-submitting button', () => {
        const wrapper = mountWith();

        expect(wrapper.find('button').attributes('type')).toBe('button');
    });
});

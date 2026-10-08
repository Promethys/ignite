import ProviderIcon from '@/components/assistant/ProviderIcon.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountIcon = (provider: string, label: string) =>
    mount(ProviderIcon, { props: { provider, label } });

describe('ProviderIcon', () => {
    it.each([
        'anthropic',
        'deepseek',
        'gemini',
        'groq',
        'mistral',
        'openai',
        'openrouter',
        'xai',
    ])('renders a logo for %s', (provider) => {
        const wrapper = mountIcon(provider, 'Label');

        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.text()).toBe('');
    });

    it('falls back to the initial of a provider that has no logo yet', () => {
        const wrapper = mountIcon('newcomer', 'Newcomer');

        expect(wrapper.find('svg').exists()).toBe(false);
        expect(wrapper.text()).toBe('N');
    });

    it('is decorative, the provider name is read from the row title', () => {
        expect(mountIcon('openai', 'OpenAI').attributes('aria-hidden')).toBe(
            'true',
        );
    });
});

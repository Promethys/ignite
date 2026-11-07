import AssistantToolActivity from '@/components/assistant/AssistantToolActivity.vue';
import Features from '@/pages/public/Features.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const translations: Record<string, string> = {
    'public.showcase.assistant_prompt':
        'Log 5 km on my half marathon and delete the old 10K goal',
    'public.showcase.assistant_reply':
        "Logged 5 km. You're at :current / :target km.",
    'assistant.activity.writing': 'Updated your data',
    'assistant.approval.title': 'Confirm this deletion',
    'assistant.approval.approve': 'Delete',
    'assistant.approval.reject': 'Keep it',
};

const translate = (key: string, replacements: Record<string, string> = {}) =>
    Object.entries(replacements).reduce(
        (text, [name, value]) => text.replace(`:${name}`, value),
        translations[key] ?? key,
    );

vi.mock('laravel-vue-i18n', () => ({
    trans: (key: string, replacements?: Record<string, string>) =>
        translate(key, replacements),
}));

const mountFeatures = () =>
    mount(Features, {
        global: {
            mocks: { $t: translate },
            stubs: {
                PublicShell: { template: '<div><slot /></div>' },
                PageIntro: true,
                HeroProgressTrack: true,
                SectionNav: true,
                GoalBadges: true,
                QuantifiableProgress: true,
                RecurringProgress: true,
                MultiStepProgress: true,
                EntryHeatmap: true,
                ProgressShowcase: true,
                TerminalIllustration: true,
                FaqAccordion: true,
                ClosingCta: true,
            },
        },
    });

describe('Assistant showcase', () => {
    it('renders the exchange with completed activity and deletion approval', async () => {
        const wrapper = mountFeatures();
        const showcase = wrapper.get('#assistant');

        expect(showcase.text()).toContain(
            translations['public.showcase.assistant_prompt'],
        );
        expect(showcase.get('[data-test="assistant-markdown"]').text()).toBe(
            "Logged 5 km. You're at 264 / 400 km.",
        );
        expect(showcase.get('[data-test="assistant-activity"]').text()).toBe(
            'Updated your data',
        );
        expect(showcase.get('[data-test="assistant-approve"]').text()).toBe(
            'Delete',
        );
        expect(showcase.get('[data-test="assistant-reject"]').text()).toBe(
            'Keep it',
        );

        await showcase.get('[data-test="assistant-approve"]').trigger('click');
        await showcase.get('[data-test="assistant-reject"]').trigger('click');

        const tools = showcase.findAllComponents(AssistantToolActivity);
        expect(tools[1].props('part').state).toBe('approval-requested');
        expect(tools[1].emitted('decide')).toHaveLength(2);
        expect(showcase.find('img').exists()).toBe(false);
        wrapper.unmount();
    });

    it('uses the centralized display dimensions for the themes crop', () => {
        const wrapper = mountFeatures();
        const image = wrapper.get('#make-it-yours img');

        expect(image.attributes('src')).toBe('/images/showcase/themes.png');
        expect(image.attributes('width')).toBe('624');
        expect(image.attributes('height')).toBe('435');
        wrapper.unmount();
    });
});

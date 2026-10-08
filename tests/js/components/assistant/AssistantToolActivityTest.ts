import AssistantToolActivity from '@/components/assistant/AssistantToolActivity.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const mountPart = (part: Record<string, unknown>) =>
    mount(AssistantToolActivity, {
        props: { part: { toolCallId: 'call-1', ...part } },
    });

describe('AssistantToolActivity', () => {
    it.each([
        ['tool-list_goals', 'assistant.activity.reading'],
        ['tool-log_progress', 'assistant.activity.writing'],
        ['tool-delete_goal', 'assistant.activity.deleting'],
    ])('describes a finished %s call', (type, label) => {
        const wrapper = mountPart({ type, state: 'output-available' });

        expect(wrapper.text()).toBe(label);
    });

    it('shows a running call, a failed one and a refused deletion', () => {
        const text = (state: string) =>
            mountPart({ type: 'tool-delete_goal', state }).text();

        expect(text('input-available')).toBe('assistant.activity.running');
        expect(text('approval-responded')).toBe('assistant.activity.running');
        expect(text('output-error')).toBe('assistant.activity.failed');
        expect(text('output-denied')).toBe('assistant.approval.denied');
    });

    it('never shows what the tool received or returned', () => {
        const wrapper = mountPart({
            type: 'tool-get_goal',
            state: 'output-available',
            input: { goal_id: 7 },
            output: 'Write a book',
        });

        expect(wrapper.text()).not.toContain('Write a book');
        expect(wrapper.text()).not.toContain('7');
    });

    it.each([
        ['a streamed', { id: 'call-1', requestReason: 'Deletes 3 entries.' }],
        ['a reloaded', { id: 'call-1', reason: 'Deletes 3 entries.' }],
    ])(
        'shows the preview of %s deletion waiting for approval',
        (_, approval) => {
            const wrapper = mountPart({
                type: 'tool-delete_goal',
                state: 'approval-requested',
                approval,
            });

            expect(
                wrapper.find('[data-test="assistant-approval"]').text(),
            ).toContain('Deletes 3 entries.');
        },
    );

    it('emits the decision of each button', async () => {
        const wrapper = mountPart({
            type: 'tool-delete_goal',
            state: 'approval-requested',
            approval: { id: 'call-1' },
        });

        await wrapper.find('[data-test="assistant-reject"]').trigger('click');
        await wrapper.find('[data-test="assistant-approve"]').trigger('click');

        expect(wrapper.emitted('decide')).toEqual([
            ['call-1', false],
            ['call-1', true],
        ]);
    });
});

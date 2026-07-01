import StatusDot from '@/components/ui/badge/StatusDot.vue';
import type { Goal } from '@/types/models';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const cases: [Goal['status'], string][] = [
    ['in_progress', 'bg-primary'],
    ['paused', 'bg-warning'],
    ['completed', 'bg-success'],
    ['not_started', 'bg-muted-foreground'],
    ['abandoned', 'bg-muted-foreground'],
];

describe('StatusDot', () => {
    it.each(cases)('uses %s → %s colour', (status, expectedClass) => {
        const wrapper = mount(StatusDot, { props: { status } });
        expect(wrapper.find('span').classes()).toContain(expectedClass);
    });
});

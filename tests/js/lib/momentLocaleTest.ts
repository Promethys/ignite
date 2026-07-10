import moment from 'moment';
import 'moment/locale/fr';
import { describe, expect, it } from 'vitest';

describe('moment french locale data', () => {
    it('produces French month names when the locale is fr', () => {
        const en = moment('2026-07-10').locale('en').format('MMMM');
        const fr = moment('2026-07-10').locale('fr').format('MMMM');

        expect(en).toBe('July');
        expect(fr).toBe('juillet');
    });
});

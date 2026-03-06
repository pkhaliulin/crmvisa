import { describe, it, expect } from 'vitest';
import { formatPhone, titleCase } from '../../resources/js/utils/format.js';

describe('formatPhone', () => {
    it('formats Uzbek 12-digit number', () => {
        expect(formatPhone('+998901234567')).toBe('+998 90 123 45 67');
        expect(formatPhone('998901234567')).toBe('+998 90 123 45 67');
    });

    it('formats 9-digit local number (assumes +998)', () => {
        expect(formatPhone('901234567')).toBe('+998 90 123 45 67');
    });

    it('returns dash for empty/null', () => {
        expect(formatPhone(null)).toBe('—');
        expect(formatPhone('')).toBe('—');
        expect(formatPhone(undefined)).toBe('—');
    });

    it('does NOT include PHP serialize prefix s:13:', () => {
        // Bug: encrypted cast returned s:13:"+998999999999" — digits "13" leaked
        const serialized = 's:13:"+998999999999"';
        const result = formatPhone(serialized);
        // Should NOT start with 13
        expect(result).not.toMatch(/^13/);
    });

    it('handles number with spaces', () => {
        expect(formatPhone('+998 99 123 45 67')).toBe('+998 99 123 45 67');
    });

    it('handles different operator codes', () => {
        expect(formatPhone('+998971234567')).toBe('+998 97 123 45 67');
        expect(formatPhone('+998911234567')).toBe('+998 91 123 45 67');
        expect(formatPhone('+998931234567')).toBe('+998 93 123 45 67');
        expect(formatPhone('+998951234567')).toBe('+998 95 123 45 67');
    });

    it('handles international numbers', () => {
        const result = formatPhone('+14155551234');
        expect(result).toContain('+');
    });
});

describe('titleCase', () => {
    it('capitalizes first letter of each word', () => {
        expect(titleCase('john doe')).toBe('John Doe');
        expect(titleCase('JOHN DOE')).toBe('John Doe');
    });

    it('handles single word', () => {
        expect(titleCase('islom')).toBe('Islom');
    });

    it('returns empty string for falsy', () => {
        expect(titleCase('')).toBe('');
        expect(titleCase(null)).toBe('');
        expect(titleCase(undefined)).toBe('');
    });

    it('trims extra whitespace', () => {
        expect(titleCase('  john   doe  ')).toBe('John Doe');
    });

    it('handles hyphenated names', () => {
        expect(titleCase('anna-maria')).toBe('Anna-Maria');
    });

    it('handles Uzbek Latin names', () => {
        expect(titleCase('islom karimov')).toBe('Islom Karimov');
        expect(titleCase('ANVAR ISMOILOV')).toBe('Anvar Ismoilov');
    });
});

/**
 * Форматирование телефона для отображения.
 * +998901234567 -> +998 90 123 45 67
 * Любой другой формат — группировка цифр после кода страны.
 */
export function formatPhone(phone) {
    if (!phone) return '—';
    const digits = phone.replace(/\D/g, '');

    // Узбекский формат: 998 XX XXX XX XX
    if (digits.startsWith('998') && digits.length === 12) {
        return `+998 ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8, 10)} ${digits.slice(10, 12)}`;
    }

    // Другие форматы: +X XXX XXX XX XX (общий)
    if (digits.length >= 10) {
        const cc = phone.startsWith('+') ? '+' : '';
        return cc + digits.replace(/(\d{1,3})(\d{2,3})(\d{3})(\d{2})(\d{2})/, '$1 $2 $3 $4 $5');
    }

    return phone;
}

/**
 * Title Case для имён: каждое слово с заглавной буквы.
 * "john DOE" -> "John Doe"
 * Работает с латиницей, кириллицей и дефисами.
 */
export function titleCase(str) {
    if (!str) return '';
    return str.trim().replace(/\s+/g, ' ')
        .split(/(\s+|-)/)
        .map(part => {
            if (part === ' ' || part === '-') return part;
            return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
        })
        .join('');
}

/**
 * Форматирование телефона для отображения.
 * +998901234567 -> +998 90 123 45 67
 *
 * Структура узбекского номера:
 *   998 — код страны (всегда 3 цифры)
 *   XX  — код оператора (2 цифры, например 90, 91, 93, 97)
 *   XXXXXXX — номер абонента (7 цифр)
 */
export function formatPhone(phone) {
    if (!phone) return '—';
    let raw = String(phone).trim();

    // Защита: если пришла PHP serialize обёртка s:N:"value", извлекаем value
    const serMatch = raw.match(/^s:\d+:"(.+)";?$/);
    if (serMatch) raw = serMatch[1];

    const digits = raw.replace(/\D/g, '');

    if (!digits) return raw;

    // Узбекский формат: 998 + 2 (оператор) + 7 (номер) = 12 цифр
    if (digits.length === 12 && digits.startsWith('998')) {
        const cc = digits.slice(0, 3);   // 998
        const op = digits.slice(3, 5);   // код оператора
        const n  = digits.slice(5);      // 7 цифр номера
        return `+${cc} ${op} ${n.slice(0, 3)} ${n.slice(3, 5)} ${n.slice(5, 7)}`;
    }

    // 9 цифр без кода страны — предполагаем +998
    if (digits.length === 9) {
        const op = digits.slice(0, 2);
        const n  = digits.slice(2);
        return `+998 ${op} ${n.slice(0, 3)} ${n.slice(3, 5)} ${n.slice(5, 7)}`;
    }

    // Любой другой международный формат: код страны + 7 последних цифр (XXX XX XX)
    const hasPlus = raw.startsWith('+');
    if (digits.length >= 7) {
        const local = digits.slice(-7);
        const cc = digits.slice(0, -7);
        const formatted = `${local.slice(0, 3)} ${local.slice(3, 5)} ${local.slice(5, 7)}`;
        if (cc) {
            return `${hasPlus ? '+' : ''}${cc} ${formatted}`;
        }
        return formatted;
    }

    return raw;
}

/**
 * Форматирование даты: 2024-03-15 -> 15.03.2024
 */
export function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('uz-UZ', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

/**
 * Title Case для имён: каждое слово с заглавной буквы.
 * "john DOE" -> "John Doe"
 * Работает с латиницей, кириллицей и дефисами.
 */
export function titleCase(str) {
    if (!str) return '';
    return str.trim().replace(/\s+/g, ' ')
        .split(/(\s|-)/)
        .map(part => {
            if (part === ' ' || part === '-') return part;
            if (!part) return '';
            return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
        })
        .join('');
}

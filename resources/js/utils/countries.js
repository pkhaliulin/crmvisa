/**
 * Утилиты для работы со странами (ISO Alpha-2 коды).
 * Используется в AgencySettings, CasesPage, PublicScoringPage и др.
 */

// Генерация флага emoji из ISO Alpha-2 кода (работает для всех валидных кодов)
export function codeToFlag(code) {
    if (!code || code.length !== 2) return '🌍';
    return code.toUpperCase().split('').map(c =>
        String.fromCodePoint(c.charCodeAt(0) - 65 + 0x1F1E6)
    ).join('');
}

// Расширенный справочник стран (русские названия)
const COUNTRY_NAMES = {
    DE: 'Германия',       FR: 'Франция',          IT: 'Италия',
    ES: 'Испания',        GB: 'Великобритания',    US: 'США',
    CA: 'Канада',         AU: 'Австралия',         NZ: 'Новая Зеландия',
    JP: 'Япония',         KR: 'Южная Корея',       CN: 'Китай',
    AE: 'ОАЭ',            SA: 'Саудовская Аравия', TR: 'Турция',
    PL: 'Польша',         CZ: 'Чехия',             SK: 'Словакия',
    HU: 'Венгрия',        AT: 'Австрия',           CH: 'Швейцария',
    NL: 'Нидерланды',     BE: 'Бельгия',           SE: 'Швеция',
    NO: 'Норвегия',       DK: 'Дания',             FI: 'Финляндия',
    PT: 'Португалия',     GR: 'Греция',             HR: 'Хорватия',
    SI: 'Словения',       EE: 'Эстония',           LV: 'Латвия',
    LT: 'Литва',          LU: 'Люксембург',         MT: 'Мальта',
    IS: 'Исландия',       IE: 'Ирландия',           CY: 'Кипр',
    BG: 'Болгария',       RO: 'Румыния',            RS: 'Сербия',
    ME: 'Черногория',     MK: 'Северная Македония', AL: 'Албания',
    BA: 'Босния',         UA: 'Украина',            GE: 'Грузия',
    AM: 'Армения',        AZ: 'Азербайджан',        MD: 'Молдова',
    KZ: 'Казахстан',      UZ: 'Узбекистан',         KG: 'Кыргызстан',
    TJ: 'Таджикистан',    TM: 'Туркменистан',       RU: 'Россия',
    BY: 'Беларусь',       MX: 'Мексика',            BR: 'Бразилия',
    AR: 'Аргентина',      CL: 'Чили',               CO: 'Колумбия',
    IN: 'Индия',          TH: 'Таиланд',            VN: 'Вьетнам',
    ID: 'Индонезия',      MY: 'Малайзия',           SG: 'Сингапур',
    PH: 'Филиппины',      EG: 'Египет',             MA: 'Марокко',
    TN: 'Тунис',          IL: 'Израиль',             JO: 'Иордания',
    ZA: 'Южная Африка',   LI: 'Лихтенштейн',        NG: 'Нигерия',
    KE: 'Кения',          GH: 'Гана',                PE: 'Перу',
    EC: 'Эквадор',        VE: 'Венесуэла',           CU: 'Куба',
    DO: 'Доминикана',     PA: 'Панама',              CR: 'Коста-Рика',
    LK: 'Шри-Ланка',      NP: 'Непал',               BD: 'Бангладеш',
    PK: 'Пакистан',       MM: 'Мьянма',              KH: 'Камбоджа',
    LA: 'Лаос',           MN: 'Монголия',            QA: 'Катар',
    BH: 'Бахрейн',        KW: 'Кувейт',              OM: 'Оман',
    LB: 'Ливан',
};

export function countryName(code) {
    if (!code) return '';
    return COUNTRY_NAMES[code.toUpperCase()] ?? code;
}

export function countryInfo(code) {
    const c = code?.toUpperCase() ?? '';
    return {
        code: c,
        name: COUNTRY_NAMES[c] ?? c,
        flag: codeToFlag(c),
    };
}

// Полный список для выпадающих списков (отсортировано по алфавиту)
export const ALL_COUNTRIES = Object.entries(COUNTRY_NAMES)
    .map(([code, name]) => ({ code, name, flag: codeToFlag(code) }))
    .sort((a, b) => a.name.localeCompare(b.name, 'ru'));

// Только страны Шенгена + популярные для визовых агентств
export const VISA_COUNTRIES = [
    'DE','FR','IT','ES','GB','US','CA','PL','CZ','AE','TR','KR',
    'AT','CH','NL','BE','SE','NO','DK','FI','PT','GR','HR','HU',
    'SK','SI','EE','LV','LT','LU','MT','IS','IE','CY','BG','RO',
].map(code => ({ code, name: COUNTRY_NAMES[code] ?? code, flag: codeToFlag(code) }))
    .sort((a, b) => a.name.localeCompare(b.name, 'ru'));

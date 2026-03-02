import { createI18n } from 'vue-i18n';
import ru from './locales/ru.json';
import uz from './locales/uz.json';

const savedLocale = localStorage.getItem('locale') || 'ru';

const i18n = createI18n({
    legacy: false,
    locale: savedLocale,
    fallbackLocale: 'ru',
    messages: { ru, uz },
});

export function setLocale(locale) {
    i18n.global.locale.value = locale;
    localStorage.setItem('locale', locale);
    document.documentElement.lang = locale;
}

export function currentLocale() {
    return i18n.global.locale.value;
}

export default i18n;

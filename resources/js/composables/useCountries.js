import { ref } from 'vue';
import { countriesApi } from '@/api/countries';
import { currentLocale } from '@/i18n';

// Синглтон — загружается один раз для всего приложения
const countries = ref([]);
const visaTypes = ref([]);
let loaded = false;

export function useCountries() {
    if (!loaded) {
        loaded = true;
        Promise.all([countriesApi.list(), countriesApi.visaTypes()])
            .then(([cRes, vtRes]) => {
                countries.value = cRes.data.data ?? [];
                visaTypes.value  = vtRes.data.data ?? [];
            })
            .catch(() => {});
    }

    function countryName(code) {
        const c = countries.value.find(c => c.country_code === code);
        if (!c) return code;
        const locale = currentLocale();
        if (locale === 'uz' && c.name_uz) return c.name_uz;
        return c.name ?? code;
    }

    function countryFlag(code) {
        return countries.value.find(c => c.country_code === code)?.flag_emoji ?? '🌍';
    }

    function visaTypeName(slug) {
        const vt = visaTypes.value.find(t => t.slug === slug);
        if (!vt) return slug;
        const locale = currentLocale();
        if (locale === 'uz' && vt.name_uz) return vt.name_uz;
        return vt.name_ru ?? slug;
    }

    return { countries, visaTypes, countryName, countryFlag, visaTypeName };
}

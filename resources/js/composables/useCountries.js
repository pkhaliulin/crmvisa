import { ref } from 'vue';
import { countriesApi } from '@/api/countries';

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
        return countries.value.find(c => c.country_code === code)?.name ?? code;
    }

    function countryFlag(code) {
        return countries.value.find(c => c.country_code === code)?.flag_emoji ?? '🌍';
    }

    function visaTypeName(slug) {
        return visaTypes.value.find(t => t.slug === slug)?.name_ru ?? slug;
    }

    return { countries, visaTypes, countryName, countryFlag, visaTypeName };
}

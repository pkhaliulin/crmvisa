import { ref } from 'vue';
import api from '@/api/index';

// Singleton — загружаем один раз
const data    = ref({});
const loaded  = ref(false);
const loading = ref(false);

export function useReferences() {
    if (!loaded.value && !loading.value) {
        loading.value = true;
        api.get('/references/all')
            .then(res => {
                data.value = res.data.data ?? {};
                loaded.value = true;
            })
            .catch(() => {})
            .finally(() => { loading.value = false; });
    }

    function items(category) {
        return data.value[category] ?? [];
    }

    function activeItems(category) {
        return items(category).filter(i => i.is_active);
    }

    function label(category, code, locale = 'ru') {
        const item = items(category).find(i => i.code === code);
        if (!item) return code;
        if (locale === 'uz' && item.label_uz) return item.label_uz;
        return item.label_ru;
    }

    function codeExists(category, code) {
        return items(category).some(i => i.code === code);
    }

    return { data, loaded, items, activeItems, label, codeExists };
}

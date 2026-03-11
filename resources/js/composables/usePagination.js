import { ref } from 'vue';

/**
 * Composable для пагинации — управление страницами и загрузкой данных.
 *
 * @param {Function} fetchFn — функция загрузки данных, вызывается с номером страницы: fetchFn(page)
 * @returns {{ page, totalPages, total, goToPage, nextPage, prevPage, updateMeta }}
 */
export function usePagination(fetchFn) {
    const page       = ref(1);
    const totalPages = ref(1);
    const total      = ref(0);

    /**
     * Обновить мета-данные из ответа Laravel pagination.
     * @param {{ current_page: number, last_page: number, total: number }} meta
     */
    function updateMeta(meta) {
        if (!meta) return;
        page.value       = meta.current_page ?? page.value;
        totalPages.value = meta.last_page    ?? totalPages.value;
        total.value      = meta.total        ?? total.value;
    }

    function goToPage(p) {
        const target = Math.max(1, Math.min(p, totalPages.value));
        if (target === page.value) return;
        page.value = target;
        fetchFn(page.value);
    }

    function nextPage() {
        if (page.value < totalPages.value) {
            goToPage(page.value + 1);
        }
    }

    function prevPage() {
        if (page.value > 1) {
            goToPage(page.value - 1);
        }
    }

    return { page, totalPages, total, goToPage, nextPage, prevPage, updateMeta };
}

import { ref } from 'vue';

// Синглтон — один массив toast-ов на всё приложение
const toasts = ref([]);
let nextId = 0;

export function useToast() {
    /**
     * @param {string} message — текст уведомления
     * @param {'success'|'error'|'info'} type — тип уведомления
     * @param {number} duration — время показа в мс (по умолчанию 4000)
     */
    function showToast(message, type = 'info', duration = 4000) {
        const id = ++nextId;
        toasts.value.push({ id, message, type });
        setTimeout(() => {
            toasts.value = toasts.value.filter(t => t.id !== id);
        }, duration);
    }

    function removeToast(id) {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }

    return { toasts, showToast, removeToast };
}

/**
 * Composable для debounce — задержка вызова функции.
 *
 * @param {Function} fn — оригинальная функция
 * @param {number} delay — задержка в мс (по умолчанию 300)
 * @returns {{ debouncedFn: Function, cancel: Function }}
 */
export function useDebounce(fn, delay = 300) {
    let timer = null;

    function debouncedFn(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    }

    function cancel() {
        clearTimeout(timer);
        timer = null;
    }

    return { debouncedFn, cancel };
}

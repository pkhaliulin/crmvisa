import axios from 'axios';
import { useToast } from '@/composables/useToast';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Подставляем токен из localStorage автоматически
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Обработка ошибок ответа
api.interceptors.response.use(
    (res) => res,
    (err) => {
        if (err.response?.status === 401) {
            localStorage.removeItem('token');
            window.location.href = '/login';
        } else if (err.response?.status >= 500) {
            const { showToast } = useToast();
            showToast('Ошибка сервера', 'error');
        }
        return Promise.reject(err);
    }
);

export default api;

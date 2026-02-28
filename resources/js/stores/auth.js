import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { authApi } from '@/api/auth';

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('token'));
    const user  = ref(JSON.parse(localStorage.getItem('user') || 'null'));

    const isLoggedIn = computed(() => !!token.value);
    const isOwner    = computed(() => ['owner', 'superadmin'].includes(user.value?.role));

    async function login(credentials) {
        const { data } = await authApi.login(credentials);
        token.value = data.data.access_token;
        user.value  = data.data.user;
        localStorage.setItem('token', token.value);
        localStorage.setItem('user', JSON.stringify(user.value));
    }

    async function register(payload) {
        const { data } = await authApi.register(payload);
        token.value = data.data.access_token;
        user.value  = data.data.user;
        localStorage.setItem('token', token.value);
        localStorage.setItem('user', JSON.stringify(user.value));
    }

    async function logout() {
        try { await authApi.logout(); } catch { /* игнор */ }
        token.value = null;
        user.value  = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
    }

    async function fetchMe() {
        const { data } = await authApi.me();
        user.value = data.data;
        localStorage.setItem('user', JSON.stringify(user.value));
    }

    return { token, user, isLoggedIn, isOwner, login, register, logout, fetchMe };
});

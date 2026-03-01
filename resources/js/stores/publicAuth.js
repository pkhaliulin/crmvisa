import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { publicPortalApi } from '@/api/public';

export const usePublicAuthStore = defineStore('publicAuth', () => {
    const token = ref(localStorage.getItem('public_token'));
    const user  = ref(JSON.parse(localStorage.getItem('public_user') || 'null'));

    const isLoggedIn     = computed(() => !!token.value);
    const profilePercent = computed(() => {
        if (!user.value) return 0;
        const fields = ['name', 'dob', 'citizenship', 'employment_type', 'monthly_income_usd', 'marital_status'];
        const filled = fields.filter(f => user.value[f]).length;
        let pct = Math.round(filled / fields.length * 100);
        if (user.value.has_property || user.value.has_car) pct += 10;
        if (user.value.has_schengen_visa || user.value.has_us_visa) pct += 10;
        if (user.value.passport_number) pct += 10;
        return Math.min(100, pct);
    });

    function setSession(userData, plainToken) {
        token.value = plainToken;
        user.value  = userData;
        localStorage.setItem('public_token', plainToken);
        localStorage.setItem('public_user', JSON.stringify(userData));
    }

    async function fetchMe() {
        const { data } = await publicPortalApi.me();
        user.value = data.data.user;
        localStorage.setItem('public_user', JSON.stringify(user.value));
    }

    function logout() {
        token.value = null;
        user.value  = null;
        localStorage.removeItem('public_token');
        localStorage.removeItem('public_user');
    }

    return { token, user, isLoggedIn, profilePercent, setSession, fetchMe, logout };
});

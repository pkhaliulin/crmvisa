import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { publicPortalApi } from '@/api/public';

export const usePublicAuthStore = defineStore('publicAuth', () => {
    const token = ref(localStorage.getItem('public_token'));
    const user  = ref(JSON.parse(localStorage.getItem('public_user') || 'null'));

    // Серверный процент — единственный источник правды
    const serverPercent = ref(JSON.parse(localStorage.getItem('public_profile_percent') || 'null'));

    const isLoggedIn     = computed(() => !!token.value);
    const profilePercent = computed(() => {
        if (serverPercent.value !== null) return serverPercent.value;
        if (!user.value) return 0;
        const u = user.value;
        // Fallback: клиентский расчёт (веса = PublicUser::profileCompleteness())
        const weights = [
            { w: 12, filled: !!u.name },
            { w: 10, filled: !!u.dob },
            { w:  9, filled: !!u.citizenship },
            { w:  5, filled: !!u.gender },
            { w: 10, filled: !!u.passport_number },
            { w:  7, filled: !!u.passport_expires_at },
            { w:  9, filled: !!u.employment_type },
            { w:  5, filled: u.employed_years !== null && u.employed_years !== undefined && u.employed_years !== '' },
            { w:  5, filled: !!u.education_level },
            { w: 10, filled: u.monthly_income_usd !== null && u.monthly_income_usd !== undefined && u.monthly_income_usd !== '' && u.monthly_income_usd > 0 },
            { w: 10, filled: !!u.marital_status },
            { w:  8, filled: u.visas_obtained_count !== null && u.visas_obtained_count !== undefined && u.visas_obtained_count !== '' },
        ];
        return weights.reduce((sum, f) => sum + (f.filled ? f.w : 0), 0);
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
        serverPercent.value = data.data.profile_percent ?? null;
        localStorage.setItem('public_user', JSON.stringify(user.value));
        localStorage.setItem('public_profile_percent', JSON.stringify(serverPercent.value));
    }

    function logout() {
        token.value = null;
        user.value  = null;
        serverPercent.value = null;
        localStorage.removeItem('public_token');
        localStorage.removeItem('public_user');
        localStorage.removeItem('public_profile_percent');
    }

    return { token, user, isLoggedIn, profilePercent, setSession, fetchMe, logout };
});

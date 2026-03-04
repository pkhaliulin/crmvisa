<template>
    <div class="space-y-4">
        <!-- Назад -->
        <button @click="router.push({ name: 'me.countries' })"
            class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $t('landing.countriesNav') }}
        </button>

        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-48 mb-3"></div>
                <div class="h-4 bg-gray-50 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else-if="country">
            <!-- Заголовок -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-4xl">{{ codeToFlag(country.country_code) }}</span>
                    <div>
                        <h1 class="text-xl font-bold text-[#0A1F44]">{{ localName }}</h1>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full mt-1 inline-block"
                            :class="regimeBadge">
                            {{ $t(`visaRegime.${country.visa_regime}`) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div v-if="country.visa_free_days" class="p-3 bg-gray-50 rounded-xl">
                        <div class="text-xs text-gray-400">{{ $t('visaRegime.label') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ country.visa_free_days }} {{ $t('common.days') }}</div>
                    </div>
                    <div v-if="country.visa_fee" class="p-3 bg-gray-50 rounded-xl">
                        <div class="text-xs text-gray-400">{{ $t('countryCosts.visaFee') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">${{ country.visa_fee }}</div>
                    </div>
                    <div v-if="country.evisa_fee" class="p-3 bg-gray-50 rounded-xl">
                        <div class="text-xs text-gray-400">{{ $t('countryCosts.evisaFee') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">${{ country.evisa_fee }}</div>
                    </div>
                    <div v-if="country.avg_flight_usd" class="p-3 bg-gray-50 rounded-xl">
                        <div class="text-xs text-gray-400">{{ $t('countryCosts.avgFlight') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">${{ country.avg_flight_usd }}</div>
                    </div>
                    <div v-if="country.avg_hotel_usd" class="p-3 bg-gray-50 rounded-xl">
                        <div class="text-xs text-gray-400">{{ $t('countryCosts.avgHotel') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">${{ country.avg_hotel_usd }}</div>
                    </div>
                    <div v-if="country.continent" class="p-3 bg-gray-50 rounded-xl">
                        <div class="text-xs text-gray-400">{{ $t('continent.label') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ $t(`continent.${country.continent}`) }}</div>
                    </div>
                </div>

                <!-- eVisa URL -->
                <a v-if="country.evisa_url" :href="country.evisa_url" target="_blank" rel="noopener"
                    class="mt-4 inline-flex items-center gap-2 text-sm text-[#1BA97F] font-semibold hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    eVisa
                </a>
            </div>

            <!-- Требования -->
            <div v-if="country.requirements?.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryDetail.requiredDocs') }}</h2>
                <div class="space-y-2">
                    <div v-for="r in country.requirements" :key="r" class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-[#1BA97F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $t(`requirements.${r}`, r) }}
                    </div>
                </div>
            </div>

            <!-- Подать заявку -->
            <button @click="createDraftCase"
                :disabled="creatingCase"
                class="w-full py-3.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-sm font-semibold rounded-2xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="creatingCase" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ creatingCase ? $t('portal.creating') : $t('landing.submitApplication') }}
            </button>
        </template>

        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <p class="font-semibold text-[#0A1F44]">{{ $t('countryDetail.notFound') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const route   = useRoute();
const router  = useRouter();
const loading = ref(true);
const country = ref(null);
const creatingCase = ref(false);

const localName = computed(() => {
    if (!country.value) return '';
    const locale = i18n.global.locale.value;
    if (locale === 'uz' && country.value.name_uz) return country.value.name_uz;
    const key = `countries.${country.value.country_code}`;
    return t(key) !== key ? t(key) : country.value.name || country.value.country_code;
});

const regimeBadge = computed(() => ({
    visa_free:       'bg-green-50 text-green-700',
    visa_on_arrival: 'bg-blue-50 text-blue-600',
    evisa:           'bg-cyan-50 text-cyan-700',
    visa_required:   'bg-red-50 text-red-600',
}[country.value?.visa_regime] || 'bg-gray-100 text-gray-500'));

async function createDraftCase() {
    creatingCase.value = true;
    try {
        const res = await publicPortalApi.createCase({
            country_code: country.value.country_code,
            visa_type:    'tourist',
        });
        const caseId = res.data?.data?.id ?? res.data?.data?.case_id;
        if (caseId) {
            router.push({ name: 'me.cases.show', params: { id: caseId } });
        } else {
            router.push({ name: 'me.cases' });
        }
    } catch (e) {
        alert(e?.response?.data?.message ?? t('portal.createError'));
    } finally {
        creatingCase.value = false;
    }
}

onMounted(async () => {
    try {
        const code = route.params.code;
        // Используем публичный endpoint который уже инкрементит view_count
        const res = await publicPortalApi.countries();
        const list = res.data.data ?? [];
        country.value = list.find(c => c.country_code === code) || null;
    } catch { /* ignore */ } finally {
        loading.value = false;
    }
});
</script>

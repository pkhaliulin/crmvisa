<template>
    <div class="space-y-4">
        <div>
            <h1 class="text-lg font-bold text-[#0A1F44]">{{ $t('landing.countriesNav') }}</h1>
        </div>

        <!-- Фильтры -->
        <div class="flex flex-wrap gap-2">
            <!-- Поиск -->
            <input v-model="search" type="text" :placeholder="$t('common.search')"
                class="flex-1 min-w-[200px] rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors" />
            <!-- Визовый режим -->
            <select v-model="filterRegime"
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors bg-white">
                <option value="">{{ $t('common.all') }}</option>
                <option value="visa_free">{{ $t('visaRegime.visa_free') }}</option>
                <option value="visa_on_arrival">{{ $t('visaRegime.visa_on_arrival') }}</option>
                <option value="evisa">{{ $t('visaRegime.evisa') }}</option>
                <option value="visa_required">{{ $t('visaRegime.visa_required') }}</option>
            </select>
            <!-- Континент -->
            <select v-model="filterContinent"
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors bg-white">
                <option value="">{{ $t('continent.label') }}</option>
                <option v-for="c in continents" :key="c" :value="c">{{ $t(`continent.${c}`) }}</option>
            </select>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="grid gap-3 sm:grid-cols-2">
            <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-32 mb-2"></div>
                <div class="h-3 bg-gray-50 rounded w-24"></div>
            </div>
        </div>

        <!-- Список -->
        <div v-else-if="filtered.length" class="grid gap-3 sm:grid-cols-2">
            <router-link v-for="c in filtered" :key="c.country_code"
                :to="{ name: 'me.countries.show', params: { code: c.country_code } }"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:border-[#1BA97F]/30 transition-colors block">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">{{ codeToFlag(c.country_code) }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-[#0A1F44] text-sm truncate">{{ localName(c) }}</div>
                        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                :class="regimeBadge(c.visa_regime)">
                                {{ $t(`visaRegime.${c.visa_regime}`) }}
                            </span>
                            <span v-if="c.visa_free_days" class="text-xs text-gray-400">{{ c.visa_free_days }} {{ $t('common.days') }}</span>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </router-link>
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <p class="text-sm text-gray-400">{{ $t('agencies.emptyTitle') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const loading = ref(true);
const countries = ref([]);

const search = ref('');
const filterRegime = ref('');
const filterContinent = ref('');

const continents = ['Asia', 'Europe', 'Africa', 'North America', 'South America', 'Oceania'];

function localName(c) {
    const locale = i18n.global.locale.value;
    if (locale === 'uz' && c.name_uz) return c.name_uz;
    return t(`countries.${c.country_code}`) !== `countries.${c.country_code}`
        ? t(`countries.${c.country_code}`)
        : c.name || c.country_code;
}

function regimeBadge(regime) {
    return {
        visa_free:       'bg-green-50 text-green-700',
        visa_on_arrival: 'bg-blue-50 text-blue-600',
        evisa:           'bg-cyan-50 text-cyan-700',
        visa_required:   'bg-red-50 text-red-600',
    }[regime] || 'bg-gray-100 text-gray-500';
}

const filtered = computed(() => {
    let list = countries.value;
    if (filterRegime.value) list = list.filter(c => c.visa_regime === filterRegime.value);
    if (filterContinent.value) list = list.filter(c => c.continent === filterContinent.value);
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(c =>
            (c.name || '').toLowerCase().includes(q) ||
            (c.name_uz || '').toLowerCase().includes(q) ||
            (t(`countries.${c.country_code}`) || '').toLowerCase().includes(q) ||
            c.country_code.toLowerCase().includes(q)
        );
    }
    return list;
});

onMounted(async () => {
    try {
        const res = await publicPortalApi.countries();
        countries.value = res.data.data ?? [];
    } catch { /* ignore */ } finally {
        loading.value = false;
    }
});
</script>

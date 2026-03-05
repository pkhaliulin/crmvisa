<template>
    <div class="space-y-5">
        <!-- Header -->
        <div>
            <h1 class="text-xl font-bold text-[#0A1F44]">{{ t('countriesList.title') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ t('countriesList.subtitle') }}</p>
        </div>

        <!-- Continent chips -->
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
            <button v-for="opt in continentOptions" :key="opt.value"
                @click="filterContinent = opt.value"
                class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                :class="filterContinent === opt.value
                    ? 'bg-[#0A1F44] text-white shadow-sm'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                {{ opt.label }}
            </button>
        </div>

        <!-- Search + regime filter -->
        <div class="flex gap-2">
            <div class="relative flex-1 min-w-0">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input v-model="search" type="text" :placeholder="t('common.search')"
                    class="w-full rounded-xl border border-gray-200 pl-9 pr-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors" />
            </div>
            <select v-model="filterRegime"
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors bg-white">
                <option value="">{{ t('visaRegime.label') }}</option>
                <option value="visa_required">{{ t('visaRegime.visa_required') }}</option>
                <option value="evisa">{{ t('visaRegime.evisa') }}</option>
                <option value="visa_on_arrival">{{ t('visaRegime.visa_on_arrival') }}</option>
                <option value="visa_free">{{ t('visaRegime.visa_free') }}</option>
            </select>
        </div>

        <!-- Count -->
        <p v-if="!loading" class="text-xs text-gray-400">
            {{ t('countriesList.showingCount', { count: displayList.length, total: filtered.length }) }}
        </p>

        <!-- Loading skeleton -->
        <div v-if="loading" class="space-y-3">
            <div v-for="i in 5" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-gray-100 rounded-full shrink-0"></div>
                    <div class="flex-1">
                        <div class="h-5 bg-gray-100 rounded w-36 mb-2"></div>
                        <div class="h-3 bg-gray-50 rounded w-24"></div>
                    </div>
                </div>
                <div class="flex gap-4 mt-4">
                    <div class="h-3 bg-gray-50 rounded w-20"></div>
                    <div class="h-3 bg-gray-50 rounded w-16"></div>
                    <div class="h-3 bg-gray-50 rounded w-24"></div>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div v-else-if="displayList.length" class="space-y-3">
            <router-link v-for="c in displayList" :key="c.country_code"
                :to="{ name: 'me.countries.show', params: { code: c.country_code } }"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:border-[#1BA97F]/30 transition-all block group">

                <!-- Top: flag + name + badges -->
                <div class="flex items-start gap-3">
                    <span class="text-3xl leading-none mt-0.5">{{ codeToFlag(c.country_code) }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="font-bold text-[#0A1F44] text-[15px] truncate">{{ localName(c) }}</div>
                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                :class="regimeBadge(c.visa_regime)">
                                {{ regimeLabel(c) }}
                            </span>
                            <span v-if="c.risk_level && c.visa_regime === 'visa_required'"
                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                :class="riskBadge(c.risk_level)">
                                {{ t(`countryPage.risk_${c.risk_level}`) }}
                            </span>
                            <span v-if="c.is_popular"
                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">
                                {{ t('countriesList.popular') }}
                            </span>
                            <span v-if="c.evisa_available && c.visa_regime !== 'evisa'"
                                class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-cyan-50 text-cyan-700">
                                eVisa
                            </span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 shrink-0 mt-1 group-hover:text-[#1BA97F] transition-colors"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                <!-- Metrics row: visa_required or evisa -->
                <div v-if="c.visa_regime === 'visa_required' || c.visa_regime === 'evisa'"
                    class="flex items-center gap-x-4 gap-y-1 mt-3 text-xs text-gray-500 flex-wrap">
                    <div v-if="c.processing_days_standard" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span>{{ c.processing_days_standard }} {{ t('countryData.days') }} {{ t('countriesList.processing') }}</span>
                    </div>
                    <div v-if="c.visa_fee_usd" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        <span>${{ c.visa_fee_usd }} {{ t('countriesList.visaFee') }}</span>
                    </div>
                    <div v-if="c.appointment_wait_days" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <span>{{ c.appointment_wait_days }} {{ t('countryData.days') }} {{ t('countriesList.waitDays') }}</span>
                    </div>
                </div>

                <!-- Visa free info -->
                <div v-else-if="c.visa_regime === 'visa_free' && c.visa_free_days"
                    class="mt-2.5 text-xs text-green-600 font-medium">
                    {{ t('countriesList.visaFreePeriod', { days: c.visa_free_days }) }}
                </div>

                <!-- VOA info -->
                <div v-else-if="c.visa_regime === 'visa_on_arrival' && c.visa_on_arrival_days"
                    class="mt-2.5 text-xs text-blue-600 font-medium">
                    {{ t('countriesList.voaPeriod', { days: c.visa_on_arrival_days }) }}
                </div>

                <!-- Requirements tags -->
                <div v-if="requirementTags(c).length" class="flex gap-1.5 mt-2.5 flex-wrap">
                    <span v-for="tag in requirementTags(c)" :key="tag"
                        class="text-[10px] px-2 py-0.5 rounded-full bg-gray-50 text-gray-500 border border-gray-100">
                        {{ tag }}
                    </span>
                </div>

                <!-- Where to apply -->
                <div v-if="applyLocation(c)" class="mt-2.5 text-[11px] text-gray-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="truncate">{{ applyLocation(c) }}</span>
                </div>
            </router-link>

            <!-- Show more -->
            <button v-if="filtered.length > 30 && !showAll"
                @click="showAll = true"
                class="w-full py-3 text-sm font-medium text-[#1BA97F] bg-white rounded-2xl border border-gray-100 shadow-sm hover:bg-gray-50 transition-colors">
                {{ t('countriesList.showMore', { count: filtered.length - 30 }) }}
            </button>
        </div>

        <!-- Empty -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <p class="text-sm text-gray-400">{{ t('countriesList.noResults') }}</p>
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
const filterContinent = ref('Europe');
const showAll = ref(false);

const continentOptions = computed(() => [
    { value: '',          label: t('countriesList.all') },
    { value: 'popular',   label: t('countriesList.popular') },
    { value: 'Europe',    label: t('continent.Europe') },
    { value: 'Asia',      label: t('continent.Asia') },
    { value: 'North America', label: t('continent.North America') },
    { value: 'South America', label: t('continent.South America') },
    { value: 'Africa',    label: t('continent.Africa') },
    { value: 'Oceania',   label: t('continent.Oceania') },
]);

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

function riskBadge(level) {
    return {
        low:    'bg-emerald-50 text-emerald-600',
        medium: 'bg-amber-50 text-amber-600',
        high:   'bg-red-50 text-red-600',
    }[level] || 'bg-gray-100 text-gray-500';
}

function regimeLabel(c) {
    const label = t(`visaRegime.${c.visa_regime}`);
    if (c.visa_regime === 'visa_free' && c.visa_free_days) {
        return `${label} · ${c.visa_free_days} ${t('countryData.days')}`;
    }
    if (c.visa_regime === 'visa_on_arrival' && c.visa_on_arrival_days) {
        return `${label} · ${c.visa_on_arrival_days} ${t('countryData.days')}`;
    }
    return label;
}

function requirementTags(c) {
    if (c.visa_regime === 'visa_free') return [];
    const tags = [];
    if (c.insurance_required)      tags.push(t('countriesList.insurance'));
    if (c.hotel_booking_required)  tags.push(t('countriesList.hotelBooking'));
    if (c.invitation_required)     tags.push(t('countriesList.invitation'));
    if (c.bank_statement_required) tags.push(t('countriesList.bankStatement'));
    if (c.biometrics_required)     tags.push(t('countriesList.biometrics'));
    if (c.return_ticket_required)  tags.push(t('countriesList.returnTicket'));
    return tags;
}

function applyLocation(c) {
    if (c.visa_regime === 'visa_free') return '';
    if (c.has_visa_center && c.visa_center_name) {
        return c.visa_center_name;
    }
    if (c.has_embassy && c.embassy_name) {
        return c.embassy_name + (c.embassy_city ? ', ' + c.embassy_city : '');
    }
    if (!c.has_embassy && c.referral_embassy_country) {
        return t('countriesList.referralAbroad') + ' (' + c.referral_embassy_city + ', ' + c.referral_embassy_country + ')';
    }
    return '';
}

const filtered = computed(() => {
    let list = countries.value.filter(c => c.is_active);

    if (filterContinent.value === 'popular') {
        list = list.filter(c => c.is_popular);
    } else if (filterContinent.value) {
        list = list.filter(c => c.continent === filterContinent.value);
    }

    if (filterRegime.value) list = list.filter(c => c.visa_regime === filterRegime.value);

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

const displayList = computed(() => showAll.value ? filtered.value : filtered.value.slice(0, 30));

onMounted(async () => {
    try {
        const res = await publicPortalApi.countries();
        countries.value = res.data.data ?? [];
    } catch { /* ignore */ } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

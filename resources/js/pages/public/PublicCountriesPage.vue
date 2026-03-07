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
                @click="selectContinent(opt.value)"
                class="shrink-0 px-4 py-1.5 rounded-full text-sm font-medium transition-colors"
                :class="filterContinent === opt.value
                    ? 'bg-[#0A1F44] text-white shadow-sm'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                {{ opt.label }}
            </button>
        </div>

        <!-- Quick filters -->
        <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
            <button @click="applyQuick('high_chance')"
                class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-colors border"
                :class="quickFilter === 'high_chance'
                    ? 'bg-green-100 text-green-700 border-green-200'
                    : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                {{ t('countriesList.quickHighChance') }}
            </button>
            <button @click="applyQuick('cheaper')"
                class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-colors border"
                :class="quickFilter === 'cheaper'
                    ? 'bg-blue-100 text-blue-700 border-blue-200'
                    : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                {{ t('countriesList.quickCheaper') }}
            </button>
            <button @click="applyQuick('faster')"
                class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold transition-colors border"
                :class="quickFilter === 'faster'
                    ? 'bg-amber-100 text-amber-700 border-amber-200'
                    : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'">
                {{ t('countriesList.quickFaster') }}
            </button>
        </div>

        <!-- Search + regime + visa type + sort -->
        <div class="flex gap-2 flex-wrap">
            <div class="relative flex-1 min-w-[160px]">
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
            <select v-model="filterVisaType"
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors bg-white">
                <option value="">{{ t('countriesList.visaTypeAll') }}</option>
                <option value="tourist">{{ t('countriesList.visaTypeTourist') }}</option>
                <option value="work">{{ t('countriesList.visaTypeWork') }}</option>
                <option value="study">{{ t('countriesList.visaTypeStudy') }}</option>
                <option value="business">{{ t('countriesList.visaTypeBusiness') }}</option>
            </select>
            <select v-model="sortBy"
                class="rounded-xl border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#1BA97F] transition-colors bg-white">
                <option value="name">{{ t('countriesList.sortName') }}</option>
                <option value="score_desc">{{ t('countriesList.sortScoreDesc') }}</option>
                <option value="score_asc">{{ t('countriesList.sortScoreAsc') }}</option>
                <option value="fee_asc">{{ t('countriesList.sortFeeAsc') }}</option>
                <option value="fee_desc">{{ t('countriesList.sortFeeDesc') }}</option>
                <option value="processing">{{ t('countriesList.sortProcessing') }}</option>
                <option value="flight_asc">{{ t('countriesList.sortFlightAsc') }}</option>
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
                    <div class="w-14 h-14 bg-gray-100 rounded-xl shrink-0"></div>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div v-else-if="displayList.length" class="space-y-3">
            <router-link v-for="c in displayList" :key="c.country_code"
                :to="{ name: 'me.countries.show', params: { code: c.country_code } }"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md hover:border-[#1BA97F]/30 transition-all block group">

                <!-- Top: flag + name + badges + scoring -->
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
                        </div>
                    </div>

                    <!-- Scoring badge with label -->
                    <div v-if="scoreMap[c.country_code] != null" class="shrink-0 flex flex-col items-center">
                        <div class="relative w-14 h-14 sm:w-16 sm:h-16">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 48 48">
                                <circle cx="24" cy="24" r="19" fill="none" stroke="#f1f5f9" stroke-width="4"/>
                                <circle cx="24" cy="24" r="19" fill="none"
                                        :stroke="scoreColor(scoreMap[c.country_code])" stroke-width="4"
                                        stroke-linecap="round"
                                        :stroke-dasharray="`${scoreMap[c.country_code] * 1.194} 119.4`"
                                        class="transition-all duration-500"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-xs sm:text-sm font-bold" :style="{ color: scoreColor(scoreMap[c.country_code]) }">
                                    {{ scoreMap[c.country_code] }}%
                                </span>
                            </div>
                        </div>
                        <span class="text-[9px] font-semibold mt-0.5" :style="{ color: scoreColor(scoreMap[c.country_code]) }">
                            {{ chanceLabel(scoreMap[c.country_code]) }}
                        </span>
                    </div>
                    <div v-else-if="scoringLoading" class="shrink-0 w-14 h-14 sm:w-16 sm:h-16 bg-gray-50 rounded-full animate-pulse"></div>

                    <svg class="w-5 h-5 text-gray-300 shrink-0 mt-4 group-hover:text-[#1BA97F] transition-colors"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>

                <!-- Metrics row -->
                <div v-if="c.visa_regime === 'visa_required' || c.visa_regime === 'evisa'"
                    class="flex items-center gap-x-4 gap-y-1.5 mt-3 text-xs text-gray-500 flex-wrap">
                    <div v-if="c.processing_days_standard" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span>{{ c.processing_days_standard }} {{ t('countriesList.daysProcessing') }}</span>
                    </div>
                    <div v-if="c.visa_fee_usd" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                        <span>${{ c.visa_fee_usd }} {{ t('countriesList.consulFee') }}</span>
                    </div>
                    <div v-if="c.appointment_wait_days" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <span>{{ c.appointment_wait_days }} {{ t('countriesList.daysWait') }}</span>
                    </div>
                    <div v-if="c.avg_flight_cost_usd" class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5 5 3 2 1-3.4 3.4L4.2 13l-1 .5 2 2 2 2 .5-1-1.7-2.8L10 10.3l1 2 3 5 .5-.3c.4-.2.6-.6.5-1.1z"/></svg>
                        <span>~${{ c.avg_flight_cost_usd }} {{ t('countriesList.flight') }}</span>
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

                <!-- Financial hint -->
                <div v-if="showFinanceHint(c)" class="mt-2 text-[11px] text-gray-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5M12 2L2 7l10 5 10-5-10-5z"/></svg>
                    <span>{{ t('countriesList.financeHint', { income: c.min_monthly_income_usd, balance: c.min_balance_usd || '\u2014' }) }}</span>
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
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const loading = ref(true);
const countries = ref([]);
const search = ref('');
const filterRegime = ref('visa_required');
const filterContinent = ref('Europe');
const filterVisaType = ref('');
const sortBy = ref('name');
const showAll = ref(false);
const quickFilter = ref('');

// Scoring
const scoreMap = ref({});       // { CC: score }
const scoringLoading = ref(false);

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
        return `${label} \u00B7 ${c.visa_free_days} ${t('common.days')}`;
    }
    if (c.visa_regime === 'visa_on_arrival' && c.visa_on_arrival_days) {
        return `${label} \u00B7 ${c.visa_on_arrival_days} ${t('common.days')}`;
    }
    return label;
}

function scoreColor(score) {
    if (score >= 80) return '#1BA97F';
    if (score >= 60) return '#3B82F6';
    if (score >= 40) return '#F59E0B';
    return '#EF4444';
}

function chanceLabel(score) {
    if (score >= 80) return t('countriesList.chanceHigh');
    if (score >= 60) return t('countriesList.chanceMedium');
    if (score >= 40) return t('countriesList.chanceLow');
    return t('countriesList.chanceRisk');
}

function showFinanceHint(c) {
    return c.visa_regime === 'visa_required' && c.min_monthly_income_usd && c.min_monthly_income_usd > 0;
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
    if (c.has_visa_center && c.visa_center_name) return c.visa_center_name;
    if (c.has_embassy && c.embassy_name) return c.embassy_name + (c.embassy_city ? ', ' + c.embassy_city : '');
    if (!c.has_embassy && c.referral_embassy_country) return t('countriesList.referralAbroad') + ' (' + c.referral_embassy_city + ', ' + c.referral_embassy_country + ')';
    return '';
}

function applyQuick(type) {
    if (quickFilter.value === type) {
        quickFilter.value = '';
        sortBy.value = 'name';
        return;
    }
    quickFilter.value = type;
    if (type === 'high_chance') {
        sortBy.value = 'score_desc';
    } else if (type === 'cheaper') {
        sortBy.value = 'fee_asc';
    } else if (type === 'faster') {
        sortBy.value = 'processing';
    }
}

function sortFn(a, b) {
    switch (sortBy.value) {
        case 'score_desc':
            return (scoreMap.value[b.country_code] ?? -1) - (scoreMap.value[a.country_code] ?? -1);
        case 'score_asc':
            return (scoreMap.value[a.country_code] ?? 999) - (scoreMap.value[b.country_code] ?? 999);
        case 'fee_asc':
            return (a.visa_fee_usd || 999) - (b.visa_fee_usd || 999);
        case 'fee_desc':
            return (b.visa_fee_usd || 0) - (a.visa_fee_usd || 0);
        case 'flight_asc':
            return (a.avg_flight_cost_usd || 9999) - (b.avg_flight_cost_usd || 9999);
        case 'processing':
            return (a.processing_days_standard || 999) - (b.processing_days_standard || 999);
        default:
            return localName(a).localeCompare(localName(b));
    }
}

const filtered = computed(() => {
    let list = countries.value.filter(c => c.is_active);

    if (filterContinent.value === 'popular') {
        list = list.filter(c => c.is_popular);
    } else if (filterContinent.value) {
        list = list.filter(c => c.continent === filterContinent.value);
    }

    if (filterRegime.value) list = list.filter(c => c.visa_regime === filterRegime.value);

    if (filterVisaType.value) {
        list = list.filter(c => {
            const types = c.available_visa_types || c.visa_types || [];
            return types.includes(filterVisaType.value);
        });
    }

    // Quick filter extra conditions
    if (quickFilter.value === 'high_chance') {
        list = list.filter(c => (scoreMap.value[c.country_code] ?? 0) >= 60);
    } else if (quickFilter.value === 'cheaper') {
        list = list.filter(c => c.visa_fee_usd != null && c.visa_fee_usd > 0);
    } else if (quickFilter.value === 'faster') {
        list = list.filter(c => c.processing_days_standard != null && c.processing_days_standard > 0);
    }

    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(c =>
            (c.name || '').toLowerCase().includes(q) ||
            (c.name_uz || '').toLowerCase().includes(q) ||
            (t(`countries.${c.country_code}`) || '').toLowerCase().includes(q) ||
            c.country_code.toLowerCase().includes(q)
        );
    }

    return [...list].sort(sortFn);
});

const displayList = computed(() => showAll.value ? filtered.value : filtered.value.slice(0, 30));

// Load scoring for current continent/filter subset
async function loadScoresForVisible() {
    const codes = filtered.value.map(c => c.country_code);
    const toScore = codes.filter(cc => scoreMap.value[cc] == null);
    if (!toScore.length) return;

    scoringLoading.value = true;
    try {
        for (let i = 0; i < toScore.length; i += 30) {
            const chunk = toScore.slice(i, i + 30);
            const visaType = filterVisaType.value || 'tourist';
            const res = await publicPortalApi.scoreBatch(chunk, visaType);
            const scores = res.data.data ?? [];
            scores.forEach(s => {
                scoreMap.value[s.country_code] = s.score;
            });
        }
    } catch { /* silent */ } finally {
        scoringLoading.value = false;
    }
}

function selectContinent(val) {
    filterContinent.value = val;
    showAll.value = false;
}

// Watch continent/regime/visaType changes to lazy-load scores
watch([filterContinent, filterRegime], () => {
    setTimeout(loadScoresForVisible, 50);
});

// When visa type changes, clear cached scores and reload
watch(filterVisaType, () => {
    scoreMap.value = {};
    setTimeout(loadScoresForVisible, 50);
});

// Reset quick filter when sort changes manually
watch(sortBy, (val) => {
    if (quickFilter.value === 'high_chance' && val !== 'score_desc') quickFilter.value = '';
    if (quickFilter.value === 'cheaper' && val !== 'fee_asc') quickFilter.value = '';
    if (quickFilter.value === 'faster' && val !== 'processing') quickFilter.value = '';
});

onMounted(async () => {
    try {
        const res = await publicPortalApi.countries();
        countries.value = res.data.data ?? [];
    } catch { /* ignore */ } finally {
        loading.value = false;
    }
    loadScoresForVisible();
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

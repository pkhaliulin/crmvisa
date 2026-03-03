<template>
    <div>

            <!-- Хедер страницы -->
            <div class="mb-6 sm:mb-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-[#0A1F44]">{{ $t('scoring.title') }}</h1>
                        <p class="text-gray-500 text-sm mt-0.5">{{ $t('scoring.subtitle') }}</p>
                    </div>
                    <!-- Прогресс профиля -->
                    <div class="text-right shrink-0">
                        <div class="text-xs text-gray-400 mb-1">{{ $t('common.profile') }}</div>
                        <div class="flex items-center gap-2">
                            <div class="w-20 sm:w-32 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full bg-[#1BA97F] transition-all duration-500"
                                     :style="{ width: profilePercent + '%' }"></div>
                            </div>
                            <span class="text-sm font-bold text-[#0A1F44]">{{ profilePercent }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Аккордеон "Как рассчитывается вероятность" -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm mb-5 overflow-hidden">
                <button @click="howOpen = !howOpen"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#0A1F44]/5 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-[#0A1F44]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-[#0A1F44] text-sm">{{ $t('scoring.howCalculated') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                         :class="howOpen ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div v-if="howOpen" class="px-5 pb-5 border-t border-gray-50">
                    <p class="text-sm text-gray-500 mt-3 mb-4">{{ $t('scoring.howDesc') }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="block in HOW_BLOCKS" :key="block.key"
                            class="flex items-start gap-3 p-3 rounded-xl bg-gray-50">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-white text-xs font-bold"
                                 :style="{ background: block.color }">
                                {{ block.weight }}
                            </div>
                            <div>
                                <div class="font-semibold text-[#0A1F44] text-sm">{{ block.label }}</div>
                                <div class="text-xs text-gray-400 mt-0.5 leading-tight">{{ block.desc }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 p-3 bg-red-50 rounded-xl">
                        <div class="text-xs font-semibold text-red-700 mb-1">{{ $t('scoring.redFlagsTitle') }}</div>
                        <ul class="text-xs text-red-600 space-y-0.5">
                            <li>{{ $t('scoring.redFlag1') }}</li>
                            <li>{{ $t('scoring.redFlag2') }}</li>
                            <li>{{ $t('scoring.redFlag3') }}</li>
                        </ul>
                    </div>
                    <div class="mt-3 grid grid-cols-4 gap-2 text-center">
                        <div v-for="level in LEVELS" :key="level.label"
                            class="p-2 rounded-lg" :style="{ background: level.bg }">
                            <div class="text-xs font-bold" :style="{ color: level.color }">{{ level.range }}</div>
                            <div class="text-[10px] mt-0.5" :style="{ color: level.color }">{{ level.label }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Список стран + детальный скоринг -->
            <div class="space-y-4">

                    <!-- Детальный скоринг выбранной страны -->
                    <div v-if="activeScore" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
                        <div class="flex items-center justify-between mb-5 sm:mb-6">
                            <div>
                                <div class="text-xs text-gray-400 mb-1">{{ $t('scoring.selectedCountry') }}</div>
                                <div class="font-bold text-[#0A1F44] text-lg">
                                    {{ countryFlag(activeScore.country_code) }}
                                    {{ countryName(activeScore.country_code) }}
                                </div>
                            </div>
                            <!-- Круговой скор -->
                            <div class="relative flex items-center justify-center">
                                <svg class="w-20 h-20 sm:w-24 sm:h-24 -rotate-90" viewBox="0 0 80 80">
                                    <circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="7"/>
                                    <circle cx="40" cy="40" r="32" fill="none"
                                            :stroke="scoreColor(activeScore.score)" stroke-width="7"
                                            stroke-linecap="round"
                                            :stroke-dasharray="`${activeScore.score * 2.01} 201`"
                                            class="transition-all duration-700"/>
                                </svg>
                                <div class="absolute text-center">
                                    <div class="text-lg sm:text-xl font-bold text-[#0A1F44]">{{ activeScore.score }}%</div>
                                    <div class="text-[9px] sm:text-[10px]" :style="{ color: scoreColor(activeScore.score) }">
                                        {{ activeScore.label }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Красные флаги -->
                        <div v-if="activeScore.red_flags?.length" class="mb-4 p-3 bg-red-50 rounded-xl space-y-1.5">
                            <div class="text-xs font-semibold text-red-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                </svg>
                                {{ $t('scoring.redFlags') }} (&times;{{ activeScore.red_flag_multiplier?.toFixed(1) }})
                            </div>
                            <div v-for="flag in activeScore.red_flags" :key="flag" class="text-xs text-red-600">{{ flag }}</div>
                        </div>

                        <!-- Разбивка по 5 блокам -->
                        <div class="space-y-3 mb-5 sm:mb-6">
                            <div v-for="block in HOW_BLOCKS" :key="block.key"
                                class="flex items-center gap-2 sm:gap-3">
                                <span class="text-xs text-gray-400 w-28 sm:w-32 shrink-0">{{ block.label }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-2.5">
                                    <div class="h-2.5 rounded-full transition-all duration-700"
                                         :class="blockBarColor(activeScore.breakdown?.[block.key])"
                                         :style="{ width: (activeScore.breakdown?.[block.key] ?? 0) + '%' }"></div>
                                </div>
                                <span class="text-xs font-bold text-gray-600 w-7 text-right">
                                    {{ activeScore.breakdown?.[block.key] ?? 0 }}
                                </span>
                            </div>
                        </div>

                        <!-- Рекомендации -->
                        <div v-if="activeScore.recommendations?.length"
                            class="p-4 bg-amber-50 rounded-xl space-y-2">
                            <div class="text-xs font-semibold text-amber-700 mb-2">{{ $t('scoring.improveTips') }}</div>
                            <div v-for="rec in activeScore.recommendations" :key="rec"
                                class="flex items-start gap-2 text-sm text-amber-800">
                                <span class="shrink-0 mt-px">&rarr;</span>
                                <span>{{ rec }}</span>
                            </div>
                        </div>

                        <!-- Найти агентство -->
                        <button @click="goToAgencies(activeScore)"
                            class="mt-4 w-full py-3.5 bg-[#1BA97F] text-white font-semibold rounded-xl
                                  hover:bg-[#17956f] active:scale-[0.98] transition-all">
                            {{ $t('scoring.findAgencyFor', { country: countryName(activeScore.country_code) }) }}
                        </button>
                    </div>

                    <!-- Список всех стран -->
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="px-5 sm:px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                            <h3 class="font-bold text-[#0A1F44]">{{ $t('scoring.allCountries') }}</h3>
                            <span class="text-xs text-gray-400">{{ $t('scoring.countryCount', { count: scores.length }) }}</span>
                        </div>

                        <div v-if="scoringLoading" class="p-5 sm:p-6 space-y-3">
                            <div v-for="i in 8" :key="i"
                                class="h-14 bg-gray-50 rounded-xl animate-pulse"></div>
                        </div>

                        <div v-else class="divide-y divide-gray-50">
                            <button
                                v-for="s in scores"
                                :key="s.country_code"
                                @click="selectCountry(s)"
                                class="w-full px-4 sm:px-6 py-4 flex items-center gap-3 sm:gap-4
                                       hover:bg-slate-50 active:bg-slate-100 transition-colors text-left"
                                :class="{ 'bg-[#1BA97F]/5': activeScore?.country_code === s.country_code }">
                                <span class="text-xl sm:text-2xl">{{ countryFlag(s.country_code) }}</span>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-[#0A1F44] text-sm truncate">
                                        {{ countryName(s.country_code) }}
                                    </div>
                                </div>
                                <!-- Мини бар -->
                                <div class="w-16 sm:w-24 bg-gray-100 rounded-full h-2 shrink-0">
                                    <div class="h-2 rounded-full transition-all duration-700"
                                         :class="s.score >= 80 ? 'bg-[#1BA97F]' : s.score >= 60 ? 'bg-blue-400' : s.score >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                         :style="{ width: s.score + '%' }"></div>
                                </div>
                                <div class="w-10 sm:w-12 text-right shrink-0">
                                    <span class="text-sm font-bold"
                                          :class="s.score >= 80 ? 'text-[#1BA97F]' : s.score >= 60 ? 'text-blue-500' : s.score >= 40 ? 'text-amber-500' : 'text-red-500'">
                                        {{ s.score }}%
                                    </span>
                                </div>
                                <span class="text-gray-300 text-sm hidden sm:block">&rsaquo;</span>
                            </button>
                        </div>
                    </div>
            </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, defineComponent, h } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { usePublicReferences } from '@/composables/usePublicReferences';
import { countryName as getCountryName, codeToFlag } from '@/utils/countries';

const { t, locale } = useI18n();
const { activeItems: refItems } = usePublicReferences();

const employmentTypes = computed(() => refItems('employment_type'));
const maritalStatuses = computed(() => refItems('marital_status'));

const route          = useRoute();
const router         = useRouter();
const publicAuth     = usePublicAuthStore();
const scores         = ref([]);
const activeScore    = ref(null);
const scoringLoading = ref(false);
const profileOpen    = ref(false);
const howOpen        = ref(false);

const profilePercent = computed(() => publicAuth.profilePercent);

// 5 блоков скоринга (computed для реактивности i18n)
const HOW_BLOCKS = computed(() => [
    { key: 'finances',     label: t('scoring.finances'),          weight: '25%', color: '#1BA97F', desc: t('scoring.financesDesc') },
    { key: 'visa_history', label: t('scoring.visaHistoryBlock'),   weight: '30%', color: '#3B82F6', desc: t('scoring.visaHistoryDesc') },
    { key: 'social_ties',  label: t('scoring.socialTies'),        weight: '20%', color: '#8B5CF6', desc: t('scoring.socialTiesDesc') },
    { key: 'destination',  label: t('scoring.destination'),       weight: '15%', color: '#F59E0B', desc: t('scoring.destinationDesc') },
    { key: 'visa_type',    label: t('scoring.visaType'),          weight: '10%', color: '#EF4444', desc: t('scoring.visaTypeDesc') },
]);

const LEVELS = computed(() => [
    { range: '80\u2013100%', label: t('scoring.levelHigh'),   color: '#1BA97F', bg: '#f0fdf4' },
    { range: '60\u201379%',  label: t('scoring.levelMedium'), color: '#3B82F6', bg: '#eff6ff' },
    { range: '40\u201359%',  label: t('scoring.levelLow'),    color: '#F59E0B', bg: '#fffbeb' },
    { range: '<40%',         label: t('scoring.levelRisk'),   color: '#EF4444', bg: '#fef2f2' },
]);

const profile = ref({
    employment_type:       publicAuth.user?.employment_type       ?? '',
    monthly_income_usd:    publicAuth.user?.monthly_income_usd    ?? '',
    marital_status:        publicAuth.user?.marital_status        ?? '',
    has_children:          publicAuth.user?.has_children          ?? false,
    has_property:          publicAuth.user?.has_property          ?? false,
    has_car:               publicAuth.user?.has_car               ?? false,
    has_schengen_visa:     publicAuth.user?.has_schengen_visa     ?? false,
    has_us_visa:           publicAuth.user?.has_us_visa           ?? false,
    had_overstay:          publicAuth.user?.had_overstay          ?? false,
    had_deportation:       publicAuth.user?.had_deportation       ?? false,
    visas_obtained_count:  publicAuth.user?.visas_obtained_count  ?? 0,
    refusals_count:        publicAuth.user?.refusals_count        ?? 0,
    last_refusal_year:     publicAuth.user?.last_refusal_year     ?? null,
    employed_years:        publicAuth.user?.employed_years        ?? 0,
});

const countryMapDynamic = ref({});

function countryName(code) { return countryMapDynamic.value[code]?.name ?? getCountryName(code) ?? code; }
function countryFlag(code) { return countryMapDynamic.value[code]?.flag ?? codeToFlag(code); }
function scoreColor(score) {
    if (score >= 80) return '#1BA97F';
    if (score >= 60) return '#3B82F6';
    if (score >= 40) return '#F59E0B';
    return '#EF4444';
}
function blockBarColor(val) {
    val = val ?? 0;
    if (val >= 60) return 'bg-[#1BA97F]';
    if (val >= 40) return 'bg-amber-400';
    return 'bg-red-400';
}

function goToAgencies(score) {
    router.push({ name: 'me.agencies', query: { country_code: score.country_code } });
}

function selectCountry(s) {
    activeScore.value = s;
    if (window.innerWidth < 768) {
        setTimeout(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 50);
    }
}

async function updateProfile() {
    try {
        await publicPortalApi.updateProfile(profile.value);
        await publicAuth.fetchMe();
    } catch { /* тихо */ }
}

async function loadScores() {
    scoringLoading.value = true;
    try {
        const [scoresRes, countriesRes] = await Promise.all([
            publicPortalApi.scoreAll(),
            publicPortalApi.countries().catch(() => null),
        ]);
        if (countriesRes?.data?.data) {
            const map = {};
            countriesRes.data.data.forEach(c => {
                map[c.code] = { name: c.name, flag: c.flag ?? codeToFlag(c.code) };
            });
            countryMapDynamic.value = map;
        }
        scores.value = scoresRes.data.data.scores ?? [];
        const preselect = route.query.country?.toUpperCase();
        activeScore.value = scores.value.find(s => s.country_code === preselect) ?? scores.value[0] ?? null;
    } catch {
        scores.value = [];
    } finally {
        scoringLoading.value = false;
    }
}

// -------------------------------------------------------------------------
// Инлайн компонент формы профиля — расширенная версия
// -------------------------------------------------------------------------

const ProfileFormInline = {
    name: 'ProfileFormInline',
    props: ['profile', 'loading', 'employmentTypes', 'maritalStatuses', 'currentLocale'],
    emits: ['update:profile', 'update', 'recalculate'],
    setup(props, { emit }) {
        function set(key, value) {
            emit('update:profile', { ...props.profile, [key]: value });
            emit('update');
        }
        function rl(item) {
            if (props.currentLocale === 'uz' && item.label_uz) return item.label_uz;
            return item.label_ru;
        }
        return { set, rl };
    },
    template: `
        <div class="space-y-3">
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.employmentLabel') }}</label>
                <select :value="profile.employment_type"
                    @change="set('employment_type', $event.target.value)"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">{{ $t('common.notSpecified') }}</option>
                    <option v-for="et in employmentTypes" :key="et.code" :value="et.code">{{ rl(et) }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.tenureLabel') }}</label>
                <select :value="profile.employed_years"
                    @change="set('employed_years', Number($event.target.value) || 0)"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option :value="0">{{ $t('scoring.lessThan1Year') }}</option>
                    <option :value="1">{{ $t('scoring.tenure1to2') }}</option>
                    <option :value="2">{{ $t('scoring.tenure2to5') }}</option>
                    <option :value="5">{{ $t('scoring.tenure5to10') }}</option>
                    <option :value="10">{{ $t('scoring.tenureOver10') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.incomeLabel') }}</label>
                <select :value="profile.monthly_income_usd"
                    @change="set('monthly_income_usd', Number($event.target.value) || '')"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">{{ $t('common.notSpecified') }}</option>
                    <option :value="300">{{ $t('scoring.incomeTo500') }}</option>
                    <option :value="800">{{ $t('scoring.income500to1000') }}</option>
                    <option :value="1500">{{ $t('scoring.income1000to2000') }}</option>
                    <option :value="3000">{{ $t('scoring.income2000to4000') }}</option>
                    <option :value="5000">{{ $t('scoring.incomeOver4000') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.familyLabel') }}</label>
                <select :value="profile.marital_status"
                    @change="set('marital_status', $event.target.value)"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">{{ $t('common.notSpecified') }}</option>
                    <option v-for="ms in maritalStatuses" :key="ms.code" :value="ms.code">{{ rl(ms) }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.visasLabel') }}</label>
                <select :value="profile.visas_obtained_count"
                    @change="set('visas_obtained_count', Number($event.target.value))"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option :value="0">{{ $t('scoring.noVisas') }}</option>
                    <option :value="1">{{ $t('scoring.oneVisa') }}</option>
                    <option :value="2">{{ $t('scoring.twoFourVisas') }}</option>
                    <option :value="5">{{ $t('scoring.fivePlusVisas') }}</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.refusalsLabel') }} <span class="text-red-500 font-medium">({{ $t('scoring.reducesScoring') }})</span></label>
                <select :value="profile.refusals_count"
                    @change="set('refusals_count', Number($event.target.value))"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option :value="0">{{ $t('scoring.noRefusals') }}</option>
                    <option :value="1">{{ $t('scoring.oneRefusal') }}</option>
                    <option :value="2">{{ $t('scoring.twoRefusals') }}</option>
                    <option :value="3">{{ $t('scoring.threePlusRefusals') }}</option>
                </select>
            </div>
            <div v-if="profile.refusals_count > 0">
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.refusalYearLabel') }}</label>
                <select :value="profile.last_refusal_year"
                    @change="set('last_refusal_year', $event.target.value ? Number($event.target.value) : null)"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">{{ $t('scoring.dontRemember') }}</option>
                    <option v-for="y in refusalYears" :key="y" :value="y">{{ y }}</option>
                </select>
            </div>
            <div class="pt-2 space-y-2">
                <label v-for="cb in checkboxes" :key="cb.key"
                    class="flex items-center gap-3 cursor-pointer group py-0.5">
                    <div class="relative shrink-0">
                        <input type="checkbox" :checked="profile[cb.key]"
                            @change="set(cb.key, $event.target.checked)"
                            class="sr-only peer" />
                        <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-[#1BA97F]
                                    peer-checked:border-[#1BA97F] transition-colors"></div>
                        <div class="absolute inset-0 flex items-center justify-center
                                    text-white text-xs font-bold opacity-0 peer-checked:opacity-100">\u2713</div>
                    </div>
                    <span class="text-sm text-gray-600 group-hover:text-gray-900 leading-tight"
                          :class="cb.danger ? 'text-red-600' : ''">{{ cb.label }}</span>
                </label>
            </div>
            <button @click="$emit('recalculate')" :disabled="loading"
                class="mt-3 w-full py-3 bg-[#0A1F44] text-white text-sm font-semibold
                       rounded-xl hover:bg-[#0d2a5e] active:scale-[0.98] transition-all disabled:opacity-60">
                {{ loading ? $t('scoring.calculating') : $t('scoring.recalculate') }}
            </button>
        </div>
    `,
    computed: {
        checkboxes() {
            return [
                { key: 'has_children',      label: this.$t('scoring.hasChildrenLabel') },
                { key: 'has_property',      label: this.$t('scoring.hasPropertyLabel') },
                { key: 'has_car',           label: this.$t('scoring.hasCarLabel') },
                { key: 'has_schengen_visa', label: this.$t('scoring.hasSchengenLabel') },
                { key: 'has_us_visa',       label: this.$t('scoring.hasUsLabel') },
                { key: 'had_overstay',      label: this.$t('scoring.hadOverstayLabel'), danger: true },
                { key: 'had_deportation',   label: this.$t('scoring.hadDeportationLabel'), danger: true },
            ];
        },
        refusalYears() {
            const year = new Date().getFullYear();
            return Array.from({ length: 10 }, (_, i) => year - i);
        },
    },
};

onMounted(loadScores);
</script>

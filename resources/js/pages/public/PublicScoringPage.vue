<template>
    <div class="space-y-5">

        <!-- Header -->
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-[#0A1F44]">{{ $t('scoring.title') }}</h1>
                <p class="text-gray-500 text-sm mt-0.5">{{ $t('scoring.profileSubtitle') }}</p>
            </div>
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

        <!-- Loading -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-48 mb-3"></div>
                <div class="h-4 bg-gray-50 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else>
            <!-- Общий скоринговый профиль -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">{{ $t('scoring.yourProfile') }}</div>
                        <div class="font-bold text-[#0A1F44] text-lg">{{ $t('scoring.baseScoreTitle') }}</div>
                    </div>
                    <!-- Круговой скор -->
                    <div class="relative flex items-center justify-center">
                        <svg class="w-24 h-24 sm:w-28 sm:h-28 -rotate-90" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="7"/>
                            <circle cx="40" cy="40" r="32" fill="none"
                                    :stroke="scoreColor(profile.base_score)" stroke-width="7"
                                    stroke-linecap="round"
                                    :stroke-dasharray="`${profile.base_score * 2.01} 201`"
                                    class="transition-all duration-700"/>
                        </svg>
                        <div class="absolute text-center">
                            <div class="text-xl sm:text-2xl font-bold text-[#0A1F44]">{{ profile.base_score }}%</div>
                            <div class="text-[10px] font-medium" :style="{ color: scoreColor(profile.base_score) }">
                                {{ scoreLabel(profile.base_score) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Разбивка по 3 базовым блокам -->
                <div class="space-y-3 mb-4">
                    <div v-for="block in PROFILE_BLOCKS" :key="block.key"
                        class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 text-white text-xs font-bold"
                             :style="{ background: block.color }">
                            {{ block.weight }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-[#0A1F44]">{{ block.label }}</span>
                                <span class="text-sm font-bold" :style="{ color: blockColor(profile.blocks?.[block.key]) }">
                                    {{ profile.blocks?.[block.key] ?? 0 }}/100
                                </span>
                            </div>
                            <div class="bg-gray-100 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all duration-700"
                                     :style="{ width: (profile.blocks?.[block.key] ?? 0) + '%', background: blockColor(profile.blocks?.[block.key]) }"></div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">{{ block.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- Красные флаги -->
                <div v-if="profile.red_flags?.length" class="p-3.5 bg-red-50 rounded-xl space-y-1.5 mb-4">
                    <div class="text-xs font-semibold text-red-700 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        {{ $t('scoring.redFlags') }} (&times;{{ profile.red_flag_multiplier?.toFixed(1) }})
                    </div>
                    <div v-for="flag in profile.red_flags" :key="flag" class="text-xs text-red-600">{{ flag }}</div>
                </div>

                <!-- Кнопка пересчитать -->
                <button @click="loadProfile" :disabled="loading"
                    class="w-full py-3 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] active:scale-[0.98] transition-all disabled:opacity-60">
                    {{ $t('scoring.recalculate') }}
                </button>
            </div>

            <!-- Как рассчитывается -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
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
                        <div v-for="block in ALL_BLOCKS" :key="block.key"
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

            <!-- Рекомендации -->
            <div v-if="profile.recommendations?.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h3 class="font-bold text-[#0A1F44] text-sm mb-4">{{ $t('scoring.recommendationsTitle') }}</h3>
                <div class="space-y-3">
                    <div v-for="(rec, idx) in profile.recommendations" :key="idx"
                        class="p-4 rounded-xl border"
                        :class="recStyle(rec.priority)">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                 :class="recIconStyle(rec.priority)">
                                <svg v-if="rec.priority === 'high'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/>
                                </svg>
                                <svg v-else-if="rec.priority === 'medium'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                          :class="recBadgeStyle(rec.type)">
                                        {{ blockLabel(rec.type) }}
                                    </span>
                                    <span class="text-[10px] font-medium"
                                          :class="rec.priority === 'high' ? 'text-red-500' : rec.priority === 'medium' ? 'text-amber-500' : 'text-green-500'">
                                        {{ priorityLabel(rec.priority) }}
                                    </span>
                                </div>
                                <p class="text-sm text-[#0A1F44] font-medium">{{ rec.text }}</p>
                                <div v-if="rec.docs?.length" class="mt-2 space-y-1">
                                    <div v-for="doc in rec.docs" :key="doc"
                                        class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        {{ doc }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Подсказка: перейти в страны -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-100 p-5">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-blue-800 mb-1">{{ $t('scoring.countriesHintTitle') }}</p>
                        <p class="text-xs text-blue-600 mb-3">{{ $t('scoring.countriesHintDesc') }}</p>
                        <router-link :to="{ name: 'me.countries' }"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                            {{ $t('scoring.goToCountries') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Редактирование профиля -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <button @click="editOpen = !editOpen"
                    class="w-full px-5 py-4 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-[#1BA97F]/10 rounded-lg flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-[#0A1F44] text-sm">{{ $t('scoring.editProfileData') }}</span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                         :class="editOpen ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div v-if="editOpen" class="px-5 pb-5 border-t border-gray-50 pt-4">
                    <ProfileFormInline
                        v-model:profile="formData"
                        :loading="saving"
                        :employment-types="employmentTypes"
                        :marital-statuses="maritalStatuses"
                        :current-locale="locale"
                        @update="saveProfile"
                        @recalculate="saveAndRecalculate"
                    />
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { usePublicReferences } from '@/composables/usePublicReferences';
import SearchSelect from '@/components/SearchSelect.vue';

const { t, locale } = useI18n();
const { activeItems: refItems } = usePublicReferences();

const employmentTypes = computed(() => refItems('employment_type'));
const maritalStatuses = computed(() => refItems('marital_status'));

const publicAuth = usePublicAuthStore();
const loading    = ref(true);
const saving     = ref(false);
const howOpen    = ref(false);
const editOpen   = ref(false);

const profile = ref({
    base_score: 0,
    blocks: {},
    red_flags: [],
    red_flag_multiplier: 1.0,
    recommendations: [],
    profile_percent: 0,
});

const profilePercent = computed(() => profile.value.profile_percent ?? publicAuth.profilePercent);

const formData = ref({
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

// 3 базовых блока профиля (без destination и visa_type — они зависят от страны)
const PROFILE_BLOCKS = computed(() => [
    { key: 'finances',     label: t('scoring.finances'),        weight: '30%', color: '#1BA97F', desc: t('scoring.financesDesc') },
    { key: 'visa_history', label: t('scoring.visaHistoryBlock'), weight: '40%', color: '#3B82F6', desc: t('scoring.visaHistoryDesc') },
    { key: 'social_ties',  label: t('scoring.socialTies'),      weight: '30%', color: '#8B5CF6', desc: t('scoring.socialTiesDesc') },
]);

// Все 5 блоков (для объяснения)
const ALL_BLOCKS = computed(() => [
    { key: 'finances',     label: t('scoring.finances'),         weight: '30%', color: '#1BA97F', desc: t('scoring.financesDesc') },
    { key: 'visa_history', label: t('scoring.visaHistoryBlock'),  weight: '40%', color: '#3B82F6', desc: t('scoring.visaHistoryDesc') },
    { key: 'social_ties',  label: t('scoring.socialTies'),       weight: '30%', color: '#8B5CF6', desc: t('scoring.socialTiesDesc') },
]);

const LEVELS = computed(() => [
    { range: '80\u2013100%', label: t('scoring.levelHigh'),   color: '#1BA97F', bg: '#f0fdf4' },
    { range: '60\u201379%',  label: t('scoring.levelMedium'), color: '#3B82F6', bg: '#eff6ff' },
    { range: '40\u201359%',  label: t('scoring.levelLow'),    color: '#F59E0B', bg: '#fffbeb' },
    { range: '<40%',         label: t('scoring.levelRisk'),   color: '#EF4444', bg: '#fef2f2' },
]);

function scoreColor(score) {
    if (score >= 80) return '#1BA97F';
    if (score >= 60) return '#3B82F6';
    if (score >= 40) return '#F59E0B';
    return '#EF4444';
}

function blockColor(val) {
    val = val ?? 0;
    if (val >= 70) return '#1BA97F';
    if (val >= 50) return '#3B82F6';
    if (val >= 30) return '#F59E0B';
    return '#EF4444';
}

function scoreLabel(score) {
    if (score >= 80) return t('scoring.levelHigh');
    if (score >= 60) return t('scoring.levelMedium');
    if (score >= 40) return t('scoring.levelLow');
    return t('scoring.levelRisk');
}

function blockLabel(type) {
    const labels = {
        finances: t('scoring.finances'),
        visa_history: t('scoring.visaHistoryBlock'),
        social_ties: t('scoring.socialTies'),
        profile: t('common.profile'),
    };
    return labels[type] || type;
}

function priorityLabel(p) {
    return { high: t('scoring.priorityHigh'), medium: t('scoring.priorityMedium'), low: t('scoring.priorityLow') }[p] || p;
}

function recStyle(priority) {
    return {
        high:   'border-red-100 bg-red-50/50',
        medium: 'border-amber-100 bg-amber-50/30',
        low:    'border-green-100 bg-green-50/30',
    }[priority] || 'border-gray-100';
}

function recIconStyle(priority) {
    return {
        high:   'bg-red-100 text-red-600',
        medium: 'bg-amber-100 text-amber-600',
        low:    'bg-green-100 text-green-600',
    }[priority] || 'bg-gray-100 text-gray-500';
}

function recBadgeStyle(type) {
    return {
        finances:     'bg-emerald-100 text-emerald-700',
        visa_history: 'bg-blue-100 text-blue-700',
        social_ties:  'bg-purple-100 text-purple-700',
        profile:      'bg-gray-100 text-gray-700',
    }[type] || 'bg-gray-100 text-gray-600';
}

async function loadProfile() {
    loading.value = true;
    try {
        const res = await publicPortalApi.scoreProfile();
        profile.value = res.data.data;
    } catch { /* silent */ } finally {
        loading.value = false;
    }
}

async function saveProfile() {
    try {
        await publicPortalApi.updateProfile(formData.value);
        await publicAuth.fetchMe();
    } catch { /* silent */ }
}

async function saveAndRecalculate() {
    saving.value = true;
    try {
        await publicPortalApi.updateProfile(formData.value);
        await publicAuth.fetchMe();
        await loadProfile();
    } catch { /* silent */ } finally {
        saving.value = false;
    }
}

// Инлайн компонент формы профиля
const ProfileFormInline = {
    name: 'ProfileFormInline',
    components: { SearchSelect },
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
                <SearchSelect :modelValue="profile.employment_type"
                    @update:modelValue="set('employment_type', $event)"
                    :items="employmentItems"
                    allow-all :all-label="$t('common.notSpecified')" />
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.tenureLabel') }}</label>
                <SearchSelect :modelValue="profile.employed_years"
                    @update:modelValue="set('employed_years', Number($event) || 0)"
                    :items="tenureItems" />
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.incomeLabel') }}</label>
                <SearchSelect :modelValue="profile.monthly_income_usd"
                    @update:modelValue="set('monthly_income_usd', Number($event) || '')"
                    :items="incomeItems"
                    allow-all :all-label="$t('common.notSpecified')" />
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.familyLabel') }}</label>
                <SearchSelect :modelValue="profile.marital_status"
                    @update:modelValue="set('marital_status', $event)"
                    :items="maritalItems"
                    allow-all :all-label="$t('common.notSpecified')" />
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.visasLabel') }}</label>
                <SearchSelect :modelValue="profile.visas_obtained_count"
                    @update:modelValue="set('visas_obtained_count', Number($event))"
                    :items="visasItems" />
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.refusalsLabel') }} <span class="text-red-500 font-medium">({{ $t('scoring.reducesScoring') }})</span></label>
                <SearchSelect :modelValue="profile.refusals_count"
                    @update:modelValue="set('refusals_count', Number($event))"
                    :items="refusalsItems" />
            </div>
            <div v-if="profile.refusals_count > 0">
                <label class="text-xs text-gray-400 mb-1 block">{{ $t('scoring.refusalYearLabel') }}</label>
                <SearchSelect :modelValue="profile.last_refusal_year"
                    @update:modelValue="set('last_refusal_year', $event ? Number($event) : null)"
                    :items="refusalYearItems"
                    allow-all :all-label="$t('scoring.dontRemember')" />
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
                                    text-white text-xs font-bold opacity-0 peer-checked:opacity-100">&#10003;</div>
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
        employmentItems() {
            return this.employmentTypes.map(et => ({ value: et.code, label: this.rl(et) }));
        },
        tenureItems() {
            return [
                { value: 0,  label: this.$t('scoring.lessThan1Year') },
                { value: 1,  label: this.$t('scoring.tenure1to2') },
                { value: 2,  label: this.$t('scoring.tenure2to5') },
                { value: 5,  label: this.$t('scoring.tenure5to10') },
                { value: 10, label: this.$t('scoring.tenureOver10') },
            ];
        },
        incomeItems() {
            return [
                { value: 300,  label: this.$t('scoring.incomeTo500') },
                { value: 800,  label: this.$t('scoring.income500to1000') },
                { value: 1500, label: this.$t('scoring.income1000to2000') },
                { value: 3000, label: this.$t('scoring.income2000to4000') },
                { value: 5000, label: this.$t('scoring.incomeOver4000') },
            ];
        },
        maritalItems() {
            return this.maritalStatuses.map(ms => ({ value: ms.code, label: this.rl(ms) }));
        },
        visasItems() {
            return [
                { value: 0, label: this.$t('scoring.noVisas') },
                { value: 1, label: this.$t('scoring.oneVisa') },
                { value: 2, label: this.$t('scoring.twoFourVisas') },
                { value: 5, label: this.$t('scoring.fivePlusVisas') },
            ];
        },
        refusalsItems() {
            return [
                { value: 0, label: this.$t('scoring.noRefusals') },
                { value: 1, label: this.$t('scoring.oneRefusal') },
                { value: 2, label: this.$t('scoring.twoRefusals') },
                { value: 3, label: this.$t('scoring.threePlusRefusals') },
            ];
        },
        refusalYearItems() {
            const year = new Date().getFullYear();
            return Array.from({ length: 10 }, (_, i) => ({ value: year - i, label: String(year - i) }));
        },
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
    },
};

onMounted(loadProfile);
</script>

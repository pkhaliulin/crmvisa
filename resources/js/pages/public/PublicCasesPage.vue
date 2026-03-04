<template>
    <div class="space-y-5">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#0A1F44]">{{ $t('cases.title') }}</h2>
                <p class="text-sm text-gray-400 mt-0.5">{{ $t('cases.subtitle') }}</p>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-16">
            <div class="w-8 h-8 border-2 border-[#1BA97F] border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Group applications link -->
        <div v-if="!loading && groupsCount > 0"
            class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <button @click="router.push({ name: 'me.groups' })"
                class="w-full px-5 py-4 flex items-center gap-3 hover:bg-gray-50 transition-colors text-left">
                <div class="w-10 h-10 bg-[#0A1F44]/5 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#0A1F44]/60" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-[#0A1F44] text-sm">{{ $t('group.navTitle') }}</div>
                    <div class="text-xs text-gray-400">{{ $t('group.groupsCount', { count: groupsCount }) }}</div>
                </div>
                <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <!-- Cases list -->
        <template v-if="!loading && cases.length">
            <button v-for="c in cases" :key="c.id"
                @click="router.push({ name: 'me.cases.show', params: { id: c.id } })"
                class="w-full text-left bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden
                       hover:border-[#1BA97F]/30 hover:shadow-md active:scale-[0.99] transition-all cursor-pointer">

                <!-- Card header -->
                <div class="px-5 pt-4 pb-3 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl shrink-0">{{ codeToFlag(c.country_code) }}</span>
                        <div class="min-w-0">
                            <div class="font-bold text-[#0A1F44] text-sm leading-tight">
                                {{ countryName(c.country_code) }}
                                <span class="text-gray-400 font-normal"> — {{ c.visa_type }}</span>
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5" v-if="c.agency">
                                {{ c.agency.name }}<span v-if="c.agency.city">, {{ c.agency.city }}</span>
                            </div>
                            <div v-else class="text-xs text-gray-400 mt-0.5">{{ $t('cases.noAgency') }}</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Public status badge with tooltip -->
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                            :class="publicStatusBadge(c.public_status)"
                            :title="c.public_status_tooltip">
                            {{ c.public_status_label }}
                        </span>
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>

                <!-- Public status progress bar (8 steps) -->
                <div class="px-5 pb-3">
                    <div class="flex items-center gap-0.5 mb-1.5">
                        <div v-for="(s, i) in PUBLIC_STATUSES" :key="s.key"
                            class="flex-1 h-1.5 rounded-full transition-colors"
                            :class="getProgressColor(i, c.public_status, c.public_status_order)">
                        </div>
                    </div>
                    <p class="text-xs font-medium" :class="statusTextColor(c.public_status)">
                        {{ c.public_status_tooltip || c.public_status_label }}
                    </p>
                </div>

                <!-- Footer stats -->
                <div class="px-5 py-3 border-t border-gray-50 flex items-center gap-4 text-xs text-gray-400 flex-wrap">
                    <!-- Менеджер -->
                    <span v-if="c.assignee" class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-gray-600 font-medium">{{ c.assignee.name }}</span>
                    </span>

                    <!-- Документы -->
                    <span v-if="c.docs_total > 0" class="flex items-center gap-1.5"
                        :class="c.docs_uploaded >= c.docs_total ? 'text-[#1BA97F]' : c.docs_uploaded > 0 ? 'text-amber-500' : 'text-gray-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $t('cases.docs', { uploaded: c.docs_uploaded, total: c.docs_total }) }}
                    </span>

                    <!-- Дедлайн -->
                    <span v-if="c.critical_date" class="flex items-center gap-1.5 ml-auto"
                        :class="deadlineClass(c.critical_date, c.public_status)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ formatDate(c.critical_date) }}
                    </span>
                    <span v-else class="ml-auto text-gray-300">{{ formatDate(c.created_at) }}</span>
                </div>

                <!-- Кнопка выбора агентства для draft -->
                <div v-if="c.public_status === 'draft' && !c.agency"
                    class="px-5 py-3 border-t border-gray-50">
                    <button @click.stop="goChooseAgency(c)"
                        class="w-full py-2.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-xs font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                        </svg>
                        {{ $t('cases.chooseAgencyBtn') }}
                    </button>
                </div>
            </button>
        </template>

        <!-- Empty state -->
        <template v-if="!loading && !cases.length">
            <!-- Profile completion prompt -->
            <div v-if="publicAuth.profilePercent < 60"
                class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-4">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="font-semibold text-amber-800 text-sm">{{ $t('cases.fillProfileWarning') }}</div>
                    <p class="text-xs text-amber-700 mt-0.5 mb-3">{{ $t('cases.fillProfileWarningDesc') }}</p>
                    <router-link :to="{ name: 'me.profile' }"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition-colors">
                        {{ $t('cases.fillProfileBtn') }}
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </router-link>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 sm:p-10 text-center">
                <div class="w-16 h-16 bg-[#0A1F44]/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#0A1F44]/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-[#0A1F44] text-base mb-2">{{ $t('cases.emptyTitle') }}</h3>
                <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                    {{ $t('cases.emptyDesc') }}
                </p>

                <!-- Steps -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6 text-left">
                    <div v-for="step in steps" :key="step.num"
                        class="flex gap-3 p-4 rounded-xl"
                        :class="step.done ? 'bg-[#1BA97F]/5 border border-[#1BA97F]/20' : 'bg-gray-50'">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"
                             :class="step.done ? 'bg-[#1BA97F] text-white' : 'bg-gray-200 text-gray-500'">
                            <svg v-if="step.done" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span v-else>{{ step.num }}</span>
                        </div>
                        <div>
                            <div class="text-xs font-semibold" :class="step.done ? 'text-[#1BA97F]' : 'text-gray-700'">{{ step.title }}</div>
                            <div class="text-xs text-gray-400 mt-0.5 leading-tight">{{ step.desc }}</div>
                        </div>
                    </div>
                </div>

                <router-link :to="nextStepRoute"
                    class="inline-flex items-center gap-2 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white px-6 py-3 rounded-xl text-sm font-semibold transition-colors">
                    {{ nextStepLabel }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </router-link>
            </div>
        </template>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const router = useRouter();
const publicAuth = usePublicAuthStore();
const loading = ref(true);
const cases   = ref([]);
const groupsCount = ref(0);

// 8 клиентских статусов для прогресс-бара
const PUBLIC_STATUSES = [
    { key: 'draft',                 order: 0 },
    { key: 'submitted',             order: 1 },
    { key: 'manager_assigned',      order: 2 },
    { key: 'document_collection',   order: 3 },
    { key: 'submitted_to_embassy',  order: 4 },
    { key: 'decision_pending',      order: 5 },
    { key: 'completed',             order: 6 },
    { key: 'rejected',              order: 7 },
];

function countryName(code) { return t(`countries.${code}`) !== `countries.${code}` ? t(`countries.${code}`) : code; }

function publicStatusBadge(status) {
    const map = {
        draft:                 'bg-gray-100 text-gray-600',
        submitted:             'bg-blue-50 text-blue-600',
        manager_assigned:      'bg-indigo-50 text-indigo-700',
        document_collection:   'bg-amber-50 text-amber-700',
        submitted_to_embassy:  'bg-orange-50 text-orange-700',
        decision_pending:      'bg-purple-50 text-purple-700',
        completed:             'bg-green-50 text-green-700',
        rejected:              'bg-red-50 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

function statusTextColor(status) {
    if (status === 'completed') return 'text-[#1BA97F]';
    if (status === 'rejected')  return 'text-red-500';
    return 'text-gray-500';
}

function getProgressColor(index, status, order) {
    if (status === 'rejected') {
        return index < order ? 'bg-red-300' : index === order ? 'bg-red-500' : 'bg-gray-100';
    }
    if (status === 'completed') return 'bg-[#1BA97F]';
    return index < order ? 'bg-[#1BA97F]' : index === order ? 'bg-[#1BA97F]/50' : 'bg-gray-100';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    return d.toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
}

function deadlineClass(dateStr, status) {
    if (!dateStr || status === 'completed' || status === 'rejected') return 'text-gray-400';
    const days = Math.floor((new Date(dateStr) - new Date()) / 86400000);
    if (days < 0)  return 'text-red-600 font-semibold';
    if (days <= 5) return 'text-amber-600 font-medium';
    return 'text-gray-500';
}

// Empty state
const profileDone = computed(() => publicAuth.profilePercent >= 60);

const steps = computed(() => [
    {
        num: 1,
        title: t('cases.fillProfile'),
        desc: t('cases.fillProfileDesc'),
        done: profileDone.value,
    },
    {
        num: 2,
        title: t('cases.createCase'),
        desc: t('cases.createCaseDesc'),
        done: false,
    },
    {
        num: 3,
        title: t('cases.chooseAgency'),
        desc: t('cases.chooseAgencyDesc'),
        done: false,
    },
]);

const nextStepRoute = computed(() =>
    profileDone.value ? { name: 'me.agencies' } : { name: 'me.profile' }
);

const nextStepLabel = computed(() =>
    profileDone.value ? t('cases.selectAgency') : t('cases.fillProfileBtn')
);

function goChooseAgency(c) {
    router.push({
        name: 'me.agencies',
        query: {
            case_id: c.id,
            country_code: c.country_code,
            visa_type: c.visa_type,
        },
    });
}

onMounted(async () => {
    try {
        const [casesRes, groupsRes] = await Promise.all([
            publicPortalApi.cases(),
            publicPortalApi.groups().catch(() => ({ data: { data: [] } })),
        ]);
        cases.value = casesRes.data.data ?? [];
        groupsCount.value = (groupsRes.data.data ?? []).length;
    } catch {
        cases.value = [];
    } finally {
        loading.value = false;
    }
});
</script>

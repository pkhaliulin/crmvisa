<template>
    <div class="min-h-screen bg-[#F0F4F8] flex flex-col">

        <!-- Header -->
        <header class="fixed top-0 inset-x-0 z-50 h-14 bg-white border-b border-gray-100 flex items-center gap-3 px-4 sm:px-6">
            <a href="/" class="flex items-center shrink-0">
                <img :src="logoUrl" :srcset="`${logoUrl} 1x, ${logoUrl2x} 2x`"
                     alt="VisaBor" class="h-7 w-auto">
            </a>

            <!-- Mobile page title -->
            <span class="sm:hidden flex-1 text-center text-sm font-semibold text-[#0A1F44]">{{ currentTitle }}</span>

            <!-- Desktop: status bar + controls -->
            <div class="hidden sm:flex items-center ml-auto gap-3">
                <!-- Status badges -->
                <div v-if="publicAuth.isLoggedIn" class="flex items-center gap-2 mr-2">
                    <router-link v-if="statusSummary.activeCases > 0"
                        :to="{ name: 'me.cases' }"
                        class="flex items-center gap-1 px-2 py-1 rounded-lg bg-blue-50 text-blue-600 text-xs font-medium cursor-pointer hover:bg-blue-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ statusSummary.activeCases }}
                    </router-link>
                    <div v-if="statusSummary.docsNeeded > 0"
                        class="flex items-center gap-1 px-2 py-1 rounded-lg bg-amber-50 text-amber-600 text-xs font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        {{ $t('portal.docsNeeded', { count: statusSummary.docsNeeded }) }}
                    </div>
                    <div v-if="statusSummary.awaitingResult > 0"
                        class="flex items-center gap-1 px-2 py-1 rounded-lg bg-green-50 text-green-600 text-xs font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $t('portal.awaitingResult') }}
                    </div>
                    <router-link v-if="statusSummary.unpaidCases > 0"
                        :to="{ name: 'me.billing' }"
                        class="flex items-center gap-1 px-2 py-1 rounded-lg bg-red-50 text-red-600 text-xs font-medium animate-pulse cursor-pointer hover:bg-red-100 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        {{ statusSummary.unpaidCases }}
                    </router-link>
                </div>

                <!-- User name + profile link -->
                <router-link :to="{ name: 'me.profile' }"
                    class="text-sm font-semibold text-[#0A1F44] hover:opacity-80 transition-opacity">
                    {{ displayName }}
                </router-link>

                <!-- Language switcher -->
                <button @click="toggleLocale"
                    class="flex items-center rounded-lg border border-gray-200 text-xs font-semibold overflow-hidden shrink-0">
                    <span class="px-2 py-1.5 transition-colors"
                        :class="currentLocale() === 'ru' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">RU</span>
                    <span class="px-2 py-1.5 transition-colors"
                        :class="currentLocale() === 'uz' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">UZ</span>
                </button>

                <!-- Logout icon -->
                <button @click="logout"
                    class="text-gray-400 hover:text-red-500 transition-colors p-1.5 rounded-lg hover:bg-red-50"
                    :title="$t('common.logout')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile: lang switch + logout -->
            <button @click="toggleLocale"
                class="sm:hidden ml-auto flex items-center rounded-md border border-gray-200 text-[10px] font-semibold overflow-hidden shrink-0">
                <span class="px-1.5 py-1 transition-colors"
                    :class="currentLocale() === 'ru' ? 'bg-[#0A1F44] text-white' : 'text-gray-400'">RU</span>
                <span class="px-1.5 py-1 transition-colors"
                    :class="currentLocale() === 'uz' ? 'bg-[#0A1F44] text-white' : 'text-gray-400'">UZ</span>
            </button>
            <button @click="logout" class="sm:hidden text-gray-400 hover:text-red-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </header>

        <div class="flex flex-1 pt-14 pb-16 md:pb-0">

            <!-- Desktop Sidebar -->
            <aside class="hidden md:flex flex-col fixed top-14 left-0 bottom-0 w-64 bg-white border-r border-gray-100">

                <!-- User card — clickable, links to profile -->
                <router-link :to="{ name: 'me.profile' }"
                    class="block p-5 border-b border-gray-50 rounded-b-none transition-colors hover:bg-gray-50/70 group cursor-pointer"
                    :class="$route.name === 'me.profile' ? 'bg-[#1BA97F]/5' : ''">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center text-white font-bold text-lg shrink-0
                                    ring-2 ring-transparent group-hover:ring-[#1BA97F]/30 transition-all">
                            {{ initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-[#0A1F44] text-sm leading-tight truncate group-hover:text-[#1BA97F] transition-colors">{{ displayName }}</div>
                            <span v-if="publicAuth.user?.phone"
                                @click.prevent.stop="callPhone"
                                class="text-xs text-gray-400 hover:text-blue-600 leading-tight mt-0.5 truncate block cursor-pointer">{{ formatPhone(publicAuth.user.phone) }}</span>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-[#1BA97F] transition-colors shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                    <!-- Profile progress -->
                    <div class="flex items-center gap-2 mb-0.5">
                        <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="publicAuth.profilePercent >= 80 ? 'bg-[#1BA97F]' : publicAuth.profilePercent >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                 :style="{ width: publicAuth.profilePercent + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-[#0A1F44] shrink-0">{{ publicAuth.profilePercent }}%</span>
                    </div>
                    <div class="text-xs text-gray-400">{{ $t('common.profileFilled') }}</div>
                </router-link>

                <!-- New application CTA -->
                <div class="p-3 border-b border-gray-50">
                    <button @click="showNewCase = true; loadServedCountries()"
                        class="w-full flex items-center justify-center gap-2 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white rounded-xl px-4 py-3 text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('portal.newApplication') }}
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 p-3 space-y-0.5">
                    <router-link v-for="item in navItems" :key="item.name" :to="{ name: item.name }"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
                        :class="isActiveNav(item.name)
                            ? 'bg-[#1BA97F]/10 text-[#1BA97F]'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-[#0A1F44]'">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath"/>
                        </svg>
                        <span>{{ item.label }}</span>
                        <span v-if="item.badge"
                            class="ml-auto text-xs px-1.5 py-0.5 rounded-full font-medium shrink-0"
                            :class="item.badgeClass">
                            {{ item.badge }}
                        </span>
                    </router-link>
                </nav>

                <!-- Telegram help -->
                <div class="p-3 border-t border-gray-50">
                    <a href="https://t.me/visaborbot" target="_blank"
                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-gray-500 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4 text-[#229ED9] shrink-0" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                        </svg>
                        {{ $t('nav.telegramHelp') }}
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 md:ml-64 min-w-0 p-4 sm:p-6">
                <div class="max-w-4xl mx-auto">
                    <router-view />
                </div>
            </main>
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-100">
            <div class="flex items-stretch h-16">
                <!-- Left nav items -->
                <router-link v-for="item in mobileNavItems.slice(0, 2)" :key="item.name" :to="{ name: item.name }"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 text-xs transition-colors"
                    :class="isActiveNav(item.name) ? 'text-[#1BA97F]' : 'text-gray-400'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath"/>
                    </svg>
                    <span class="text-[10px]">{{ item.label }}</span>
                </router-link>

                <!-- Central new case button -->
                <button @click="showNewCase = true; loadServedCountries()" class="flex-1 flex flex-col items-center justify-center gap-0.5">
                    <div class="w-11 h-11 bg-[#0A1F44] rounded-xl flex items-center justify-center -mt-2 shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="text-[10px] text-gray-400 mt-0.5">{{ $t('nav.application') }}</span>
                </button>

                <!-- Right nav items -->
                <router-link v-for="item in mobileNavItems.slice(2)" :key="item.name" :to="{ name: item.name }"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 text-xs transition-colors"
                    :class="isActiveNav(item.name) ? 'text-[#1BA97F]' : 'text-gray-400'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath"/>
                    </svg>
                    <span class="text-[10px]">{{ item.label }}</span>
                </router-link>
            </div>
        </nav>

        <!-- New Application Modal (bottom sheet on mobile, centered on desktop) -->
        <div v-if="showNewCase"
            class="fixed inset-0 bg-[#0A1F44]/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="closeNewCase">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-base font-bold text-[#0A1F44]">{{ $t('portal.newApplication') }}</h3>
                        <button @click="closeNewCase" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mb-5">{{ $t('portal.newAppDesc') }}</p>

                    <!-- Inline блок "заполните профиль" если < 60% -->
                    <div v-if="publicAuth.profilePercent < 60" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-amber-700 text-sm">{{ $t('portal.fillProfileFirst') }}</div>
                                <div class="text-xs text-amber-600 mt-0.5">{{ $t('portal.fillProfileMinimum', { percent: publicAuth.profilePercent }) }}</div>
                                <router-link :to="{ name: 'me.profile' }" @click="closeNewCase"
                                    class="inline-block mt-2 text-xs font-semibold text-amber-700 underline underline-offset-2">
                                    {{ $t('portal.goToProfile') }}
                                </router-link>
                            </div>
                        </div>
                    </div>

                    <!-- Форма создания заявки -->
                    <div v-else class="space-y-3">
                        <div v-if="servedCountries.length === 0 && !loadingServed" class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-center">
                            <p class="text-sm text-gray-500">{{ $t('portal.noServedCountries') }}</p>
                        </div>
                        <template v-else>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('portal.destinationCountry') }}</label>
                                <select v-model="newCaseForm.country_code"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white">
                                    <option value="">{{ $t('profile.selectCountry') }}</option>
                                    <option v-for="c in servedCountries" :key="c.country_code" :value="c.country_code">
                                        {{ $t('countries.' + c.country_code) }} ({{ c.agencies_count }})
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('portal.visaTypeLabel') }}</label>
                                <select v-model="newCaseForm.visa_type"
                                    :disabled="!newCaseForm.country_code"
                                    class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="">{{ newCaseForm.country_code ? $t('portal.selectType') : $t('portal.selectCountryFirst') }}</option>
                                    <option v-for="vt in availableVisaTypes" :key="vt" :value="vt">
                                        {{ VISA_TYPE_LABELS[vt] || vt }}
                                    </option>
                                </select>
                            </div>
                            <div v-if="newCaseError" class="text-xs text-red-500">{{ newCaseError }}</div>
                            <button @click="createDraftCase"
                                :disabled="newCaseSubmitting || !newCaseForm.country_code || !newCaseForm.visa_type"
                                class="w-full py-3 bg-[#1BA97F] hover:bg-[#169B72] text-white text-sm font-semibold rounded-xl
                                       transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg v-if="newCaseSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ newCaseSubmitting ? $t('portal.creating') : $t('portal.createApplication') }}
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { setLocale, currentLocale } from '@/i18n';
import logoUrl from '@/assets/logo.png';
import logoUrl2x from '@/assets/logo@2x.png';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { publicPortalApi } from '@/api/public';
import { formatPhone } from '@/utils/format';

const { t } = useI18n();

function toggleLocale() {
    setLocale(currentLocale() === 'ru' ? 'uz' : 'ru');
}
const router     = useRouter();
const route      = useRoute();
const publicAuth = usePublicAuthStore();
const showNewCase = ref(false);

const newCaseForm = ref({ country_code: '', visa_type: '' });
const newCaseSubmitting = ref(false);
const newCaseError = ref('');

const VISA_TYPE_LABELS = {
    tourist: t('portal.tourist'),
    business: t('portal.business'),
    student: t('portal.studentVisa'),
    work: t('portal.work'),
    transit: t('portal.transit'),
    medical: t('portal.medical') || 'Медицинская',
    family: t('portal.family') || 'Семейная',
};

// Типы виз доступные для выбранной страны
const availableVisaTypes = computed(() => {
    if (!newCaseForm.value.country_code) return [];
    const country = servedCountries.value.find(c => c.country_code === newCaseForm.value.country_code);
    return country?.visa_types ?? [];
});

// Сбрасываем тип визы при смене страны
watch(() => newCaseForm.value.country_code, () => {
    newCaseForm.value.visa_type = '';
});

// Страны, по которым работает хотя бы одно агентство
const servedCountries = ref([]);
const loadingServed = ref(false);

async function loadServedCountries() {
    if (servedCountries.value.length > 0) return;
    loadingServed.value = true;
    try {
        const res = await publicPortalApi.servedCountries();
        servedCountries.value = (res.data?.data ?? []).sort((a, b) =>
            (t('countries.' + a.country_code) || '').localeCompare(t('countries.' + b.country_code) || '')
        );
    } catch { /* ignore */ }
    loadingServed.value = false;
}

// Status summary для верхнего бара
const statusSummary = ref({ activeCases: 0, docsNeeded: 0, awaitingResult: 0, unpaidCases: 0, unpaidCaseId: null });

async function loadStatusSummary() {
    // Загружаем заявки и биллинг отдельно, чтобы ошибка одного не ломала другой
    let cases = [];
    let unpaidCount = 0;

    try {
        const casesRes = await publicPortalApi.cases();
        cases = casesRes?.data?.data ?? [];
    } catch { /* ignore */ }

    try {
        const billingRes = await publicPortalApi.billingHistory();
        const payments = billingRes?.data?.data?.payments ?? [];
        unpaidCount = payments.filter(p => p.status === 'pending').length;
    } catch { /* ignore */ }

    const TERMINAL = ['rejected', 'cancelled', 'completed'];
    const activeCases = cases.filter(c => !TERMINAL.includes(c.public_status));

    statusSummary.value = {
        activeCases: activeCases.length,
        docsNeeded: activeCases.filter(c => c.public_status === 'document_collection').length,
        awaitingResult: activeCases.filter(c => c.public_status === 'under_review').length,
        unpaidCases: unpaidCount,
        unpaidCaseId: null,
    };
}

function closeNewCase() {
    showNewCase.value = false;
    newCaseError.value = '';
}

async function createDraftCase() {
    if (!newCaseForm.value.country_code || !newCaseForm.value.visa_type) return;
    newCaseSubmitting.value = true;
    newCaseError.value = '';
    try {
        const res = await publicPortalApi.createCase({
            country_code: newCaseForm.value.country_code,
            visa_type:    newCaseForm.value.visa_type,
        });
        const caseId = res.data?.data?.id ?? res.data?.data?.case_id;
        closeNewCase();
        newCaseForm.value = { country_code: '', visa_type: '' };
        if (caseId) {
            router.push({ name: 'me.cases.show', params: { id: caseId } });
        } else {
            router.push({ name: 'me.cases' });
        }
    } catch (e) {
        newCaseError.value = e?.response?.data?.message ?? t('portal.createError');
    } finally {
        newCaseSubmitting.value = false;
    }
}

const initials = computed(() => {
    const name = publicAuth.user?.name;
    if (!name) return String(publicAuth.user?.phone ?? '?').slice(-2);
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('');
});

const displayName = computed(() =>
    publicAuth.user?.name || formatPhone(publicAuth.user?.phone) || t('common.guest')
);

// Active nav helper (highlights groups pages under cases too)
function isActiveNav(name) {
    if (name === 'me.cases') {
        return ['me.cases', 'me.cases.show', 'me.groups', 'me.groups.show'].includes(route.name);
    }
    if (name === 'me.countries') {
        return ['me.countries', 'me.countries.show'].includes(route.name);
    }
    if (name === 'me.agencies') {
        return ['me.agencies', 'me.agencies.show'].includes(route.name);
    }
    return route.name === name;
}

// Sidebar navigation — no separate "Profile" and "Groups"
const navItems = computed(() => [
    {
        name: 'me.cases',
        label: t('nav.cases'),
        iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        badge: statusSummary.value.activeCases > 0 ? statusSummary.value.activeCases : null,
        badgeClass: 'bg-blue-50 text-blue-600',
    },
    {
        name: 'me.countries',
        label: t('landing.countriesNav'),
        iconPath: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
    },
    {
        name: 'me.scoring',
        label: t('nav.scoring'),
        iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        name: 'me.agencies',
        label: t('nav.agencies'),
        iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        name: 'me.billing',
        label: t('billing.title'),
        iconPath: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        badge: statusSummary.value.unpaidCases > 0 ? statusSummary.value.unpaidCases : null,
        badgeClass: 'bg-red-50 text-red-600',
    },
]);

// Mobile bottom nav (4 items + central button)
const mobileNavItems = computed(() => [
    navItems.value[0], // Заявки
    navItems.value[1], // Страны
    navItems.value[2], // Скоринг
    {
        name: 'me.profile',
        label: t('nav.profile'),
        iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
]);

const TITLES = computed(() => ({
    'me.cases': t('nav.cases'),
    'me.groups': t('group.navTitle'),
    'me.agencies': t('nav.agencies'),
    'me.scoring': t('nav.scoring'),
    'me.profile': t('nav.profile'),
    'me.countries': t('landing.countriesNav'),
    'me.billing': t('billing.title'),
}));

const currentTitle = computed(() => TITLES.value[route.name] ?? t('portal.cabinet'));

function callPhone() {
    if (publicAuth.user?.phone) {
        location.href = `tel:${publicAuth.user.phone}`;
    }
}

function logout() {
    publicAuth.logout();
    router.push({ name: 'landing' });
}

onMounted(() => {
    if (publicAuth.isLoggedIn) {
        loadStatusSummary();
    }
});
</script>

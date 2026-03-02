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

            <!-- Desktop: user info -->
            <div class="hidden sm:flex items-center ml-auto gap-4">
                <!-- Переключатель языка -->
                <button @click="toggleLocale"
                    class="flex items-center rounded-lg border border-gray-200 text-xs font-semibold overflow-hidden shrink-0">
                    <span class="px-2 py-1.5 transition-colors"
                        :class="currentLocale() === 'ru' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">RU</span>
                    <span class="px-2 py-1.5 transition-colors"
                        :class="currentLocale() === 'uz' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">UZ</span>
                </button>
                <div class="text-right">
                    <div class="text-sm font-semibold text-[#0A1F44] leading-tight">{{ displayName }}</div>
                    <div class="text-xs font-medium leading-tight"
                         :class="publicAuth.profilePercent >= 80 ? 'text-[#1BA97F]' : publicAuth.profilePercent >= 40 ? 'text-amber-500' : 'text-gray-400'">
                        {{ $t('common.profilePercent', { percent: publicAuth.profilePercent }) }}
                    </div>
                </div>
                <button @click="logout"
                    class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-red-500 transition-colors border border-gray-200 hover:border-red-200 rounded-lg px-2.5 py-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ $t('common.logout') }}
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

                <!-- User card -->
                <div class="p-5 border-b border-gray-50">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center text-white font-bold text-lg shrink-0">
                            {{ initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-[#0A1F44] text-sm leading-tight truncate">{{ displayName }}</div>
                            <div class="text-xs text-gray-400 leading-tight mt-0.5 truncate">{{ publicAuth.user?.phone }}</div>
                        </div>
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
                </div>

                <!-- New application CTA -->
                <div class="p-3 border-b border-gray-50">
                    <button @click="showNewCase = true"
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
                        :class="$route.name === item.name
                            ? 'bg-[#1BA97F]/10 text-[#1BA97F]'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-[#0A1F44]'">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath"/>
                        </svg>
                        <span>{{ item.label }}</span>
                        <span v-if="item.name === 'me.profile' && publicAuth.profilePercent < 80"
                            class="ml-auto text-xs bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full font-medium shrink-0">
                            {{ publicAuth.profilePercent }}%
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
                <router-link v-for="item in navItems.slice(0, 2)" :key="item.name" :to="{ name: item.name }"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 text-xs transition-colors"
                    :class="$route.name === item.name ? 'text-[#1BA97F]' : 'text-gray-400'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath"/>
                    </svg>
                    <span class="text-[10px]">{{ item.label }}</span>
                </router-link>

                <!-- Central new case button -->
                <button @click="showNewCase = true" class="flex-1 flex flex-col items-center justify-center gap-0.5">
                    <div class="w-11 h-11 bg-[#0A1F44] rounded-xl flex items-center justify-center -mt-2 shadow-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <span class="text-[10px] text-gray-400 mt-0.5">{{ $t('nav.application') }}</span>
                </button>

                <!-- Right nav items -->
                <router-link v-for="item in navItems.slice(2)" :key="item.name" :to="{ name: item.name }"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 text-xs transition-colors"
                    :class="$route.name === item.name ? 'text-[#1BA97F]' : 'text-gray-400'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.iconPath"/>
                    </svg>
                    <span class="text-[10px]">{{ item.label }}</span>
                </router-link>
            </div>
        </nav>

        <!-- New Application Modal (bottom sheet on mobile, centered on desktop) -->
        <div v-if="showNewCase"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
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
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('portal.destinationCountry') }}</label>
                            <select v-model="newCaseForm.country_code"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white">
                                <option value="">{{ $t('profile.selectCountry') }}</option>
                                <option value="DE">{{ $t('countries.DE') }}</option>
                                <option value="ES">{{ $t('countries.ES') }}</option>
                                <option value="FR">{{ $t('countries.FR') }}</option>
                                <option value="IT">{{ $t('countries.IT') }}</option>
                                <option value="PL">{{ $t('countries.PL') }}</option>
                                <option value="CZ">{{ $t('countries.CZ') }}</option>
                                <option value="GB">{{ $t('countries.GB') }}</option>
                                <option value="US">{{ $t('countries.US') }}</option>
                                <option value="CA">{{ $t('countries.CA') }}</option>
                                <option value="KR">{{ $t('countries.KR') }}</option>
                                <option value="AE">{{ $t('countries.AE') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('portal.visaTypeLabel') }}</label>
                            <select v-model="newCaseForm.visa_type"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white">
                                <option value="">{{ $t('portal.selectType') }}</option>
                                <option value="tourist">{{ $t('portal.tourist') }}</option>
                                <option value="business">{{ $t('portal.business') }}</option>
                                <option value="student">{{ $t('portal.studentVisa') }}</option>
                                <option value="work">{{ $t('portal.work') }}</option>
                                <option value="transit">{{ $t('portal.transit') }}</option>
                            </select>
                        </div>
                        <div v-if="newCaseError" class="text-xs text-red-500">{{ newCaseError }}</div>
                        <button @click="createDraftCase"
                            :disabled="newCaseSubmitting || !newCaseForm.country_code || !newCaseForm.visa_type"
                            class="w-full py-3 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-sm font-semibold rounded-xl
                                   transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="newCaseSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ newCaseSubmitting ? $t('portal.creating') : $t('portal.createApplication') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { setLocale, currentLocale } from '@/i18n';
import logoUrl from '@/assets/logo.png';
import logoUrl2x from '@/assets/logo@2x.png';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { publicPortalApi } from '@/api/public';

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
    if (!name) return (publicAuth.user?.phone ?? '?').slice(-2);
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('');
});

const displayName = computed(() =>
    publicAuth.user?.name || publicAuth.user?.phone || t('common.guest')
);

const navItems = computed(() => [
    {
        name: 'me.cases',
        label: t('nav.cases'),
        iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        name: 'me.agencies',
        label: t('nav.agencies'),
        iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
    },
    {
        name: 'me.scoring',
        label: t('nav.scoring'),
        iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        name: 'me.profile',
        label: t('nav.profile'),
        iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
]);

const TITLES = computed(() => ({
    'me.cases': t('nav.cases'),
    'me.agencies': t('nav.agencies'),
    'me.scoring': t('nav.scoring'),
    'me.profile': t('nav.profile'),
}));

const currentTitle = computed(() => TITLES.value[route.name] ?? t('portal.cabinet'));

function logout() {
    publicAuth.logout();
    router.push({ name: 'landing' });
}
</script>
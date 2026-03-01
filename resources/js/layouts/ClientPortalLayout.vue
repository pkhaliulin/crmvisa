<template>
    <div class="min-h-screen bg-[#F0F4F8] flex flex-col">

        <!-- Header -->
        <header class="fixed top-0 inset-x-0 z-50 h-14 bg-white border-b border-gray-100 flex items-center gap-3 px-4 sm:px-6">
            <a href="/" class="flex items-center shrink-0">
                <img src="/images/logo.png"
                     srcset="/images/logo@1x.png 1x, /images/logo@2x.png 2x"
                     alt="VisaBor" class="h-7 w-auto">
            </a>

            <!-- Mobile page title -->
            <span class="sm:hidden flex-1 text-center text-sm font-semibold text-[#0A1F44]">{{ currentTitle }}</span>

            <!-- Desktop: user info -->
            <div class="hidden sm:flex items-center ml-auto gap-4">
                <div class="text-right">
                    <div class="text-sm font-semibold text-[#0A1F44] leading-tight">{{ displayName }}</div>
                    <div class="text-xs font-medium leading-tight"
                         :class="publicAuth.profilePercent >= 80 ? 'text-[#1BA97F]' : publicAuth.profilePercent >= 40 ? 'text-amber-500' : 'text-gray-400'">
                        Профиль {{ publicAuth.profilePercent }}%
                    </div>
                </div>
                <button @click="logout"
                    class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-red-500 transition-colors border border-gray-200 hover:border-red-200 rounded-lg px-2.5 py-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Выйти
                </button>
            </div>

            <!-- Mobile logout -->
            <button @click="logout" class="sm:hidden ml-auto text-gray-400 hover:text-red-500 transition-colors">
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
                    <div class="text-xs text-gray-400">профиль заполнен</div>
                </div>

                <!-- New application CTA -->
                <div class="p-3 border-b border-gray-50">
                    <button @click="showNewCase = true"
                        class="w-full flex items-center justify-center gap-2 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white rounded-xl px-4 py-3 text-sm font-semibold transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Новая заявка на визу
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
                        Помощь в Telegram
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 md:ml-64 min-w-0 p-4 sm:p-6">
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Navigation -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-100">
            <div class="flex items-stretch h-16">
                <!-- Left nav items -->
                <router-link v-for="item in navItems.slice(0, 1)" :key="item.name" :to="{ name: item.name }"
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
                    <span class="text-[10px] text-gray-400 mt-0.5">Заявка</span>
                </button>

                <!-- Right nav items -->
                <router-link v-for="item in navItems.slice(1)" :key="item.name" :to="{ name: item.name }"
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
            @click.self="showNewCase = false">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-base font-bold text-[#0A1F44]">Новая заявка на визу</h3>
                        <button @click="showNewCase = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mb-5">Заполните профиль и пройдите скоринг — подберём агентство по вашей стране.</p>

                    <div class="space-y-2.5">
                        <router-link :to="{ name: 'me.profile' }" @click="showNewCase = false"
                            class="flex items-center gap-3 p-4 rounded-xl border transition-colors"
                            :class="publicAuth.profilePercent >= 60
                                ? 'border-[#1BA97F]/30 bg-[#1BA97F]/5 hover:bg-[#1BA97F]/10'
                                : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                 :class="publicAuth.profilePercent >= 60 ? 'bg-[#1BA97F]' : 'bg-gray-200'">
                                <svg class="w-4 h-4" :class="publicAuth.profilePercent >= 60 ? 'text-white' : 'text-gray-500'"
                                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-[#0A1F44] text-sm">Шаг 1: Профиль</div>
                                <div class="text-xs text-gray-400 mt-0.5">ФИО, паспорт, гражданство</div>
                            </div>
                            <span v-if="publicAuth.profilePercent >= 60" class="text-xs text-[#1BA97F] font-bold shrink-0">Готово</span>
                            <svg v-else class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </router-link>

                        <router-link :to="{ name: 'me.scoring' }" @click="showNewCase = false"
                            class="flex items-center gap-3 p-4 rounded-xl border border-gray-100 bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-[#0A1F44] text-sm">Шаг 2: Скоринг</div>
                                <div class="text-xs text-gray-400 mt-0.5">Узнайте шансы — займёт 2 минуты</div>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { usePublicAuthStore } from '@/stores/publicAuth';

const router     = useRouter();
const route      = useRoute();
const publicAuth = usePublicAuthStore();
const showNewCase = ref(false);

const initials = computed(() => {
    const name = publicAuth.user?.name;
    if (!name) return (publicAuth.user?.phone ?? '?').slice(-2);
    return name.split(' ').slice(0, 2).map(w => w[0]?.toUpperCase() ?? '').join('');
});

const displayName = computed(() =>
    publicAuth.user?.name || publicAuth.user?.phone || 'Гость'
);

const navItems = [
    {
        name: 'me.profile',
        label: 'Профиль',
        iconPath: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
    {
        name: 'me.scoring',
        label: 'Скоринг',
        iconPath: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        name: 'me.cases',
        label: 'Заявки',
        iconPath: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
];

const TITLES = { 'me.profile': 'Профиль', 'me.scoring': 'Скоринг', 'me.cases': 'Заявки' };
const currentTitle = computed(() => TITLES[route.name] ?? 'Кабинет');

function logout() {
    publicAuth.logout();
    router.push({ name: 'landing' });
}
</script>

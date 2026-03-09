<template>
    <div class="min-h-screen bg-white">
        <!-- Хедер -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Логотип -->
                <a href="/" class="flex items-center select-none shrink-0">
                    <LogoBrand size="1.3rem" />
                </a>

                <!-- Десктоп навигация (скрываем если залогинен) -->
                <nav v-if="!publicAuth.isLoggedIn" class="hidden md:flex items-center gap-6 text-sm text-gray-500">
                    <a href="#how"       class="hover:text-gray-900 transition-colors">{{ $t('landing.howWorks') }}</a>
                    <a href="#countries" class="hover:text-gray-900 transition-colors">{{ $t('landing.countriesNav') }}</a>
                    <a href="#agencies"  class="hover:text-gray-900 transition-colors">{{ $t('landing.agencies') }}</a>
                </nav>

                <!-- Если залогинен: имя + кнопка кабинета -->
                <div v-if="publicAuth.isLoggedIn" class="hidden md:flex items-center ml-auto gap-3">
                    <!-- Переключатель языка -->
                    <button @click="toggleLocale"
                        class="flex items-center rounded-lg border border-gray-200 text-xs font-semibold overflow-hidden shrink-0">
                        <span class="px-2 py-1.5 transition-colors"
                            :class="currentLocale() === 'ru' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">RU</span>
                        <span class="px-2 py-1.5 transition-colors"
                            :class="currentLocale() === 'uz' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">UZ</span>
                    </button>
                    <div class="text-right mr-1">
                        <div class="text-sm font-semibold text-[#0A1F44] leading-tight">
                            {{ publicAuth.user?.name || formatPhone(publicAuth.user?.phone) }}
                        </div>
                        <div class="text-xs text-[#1BA97F] font-medium leading-tight">
                            {{ $t('common.profilePercent', { percent: publicAuth.profilePercent }) }}
                        </div>
                    </div>
                    <router-link to="/me/cases"
                        class="px-4 py-2 bg-[#1BA97F] text-white text-sm font-semibold rounded-lg hover:bg-[#169B72] transition-colors">
                        {{ $t('common.myAccount') }}
                    </router-link>
                    <button @click="publicAuth.logout()"
                        class="text-sm text-gray-400 hover:text-red-500 transition-colors">
                        {{ $t('common.logout') }}
                    </button>
                </div>

                <!-- Если не залогинен: кнопки входа -->
                <div v-else class="hidden md:flex items-center gap-3">
                    <!-- Переключатель языка -->
                    <button @click="toggleLocale"
                        class="flex items-center rounded-lg border border-gray-200 text-xs font-semibold overflow-hidden shrink-0">
                        <span class="px-2 py-1.5 transition-colors"
                            :class="currentLocale() === 'ru' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">RU</span>
                        <span class="px-2 py-1.5 transition-colors"
                            :class="currentLocale() === 'uz' ? 'bg-[#0A1F44] text-white' : 'text-gray-400 hover:text-gray-600'">UZ</span>
                    </button>
                    <a href="/login" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                        {{ $t('common.crm') }}
                    </a>
                    <button @click="$emit('open-auth')"
                        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-lg
                               hover:bg-[#0d2a5e] transition-colors">
                        {{ $t('common.login') }}
                    </button>
                </div>

                <!-- Мобильная кнопка -->
                <button @click="mobileOpen = !mobileOpen"
                    class="md:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 transition-colors">
                    <svg v-if="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Мобильное меню -->
            <div v-if="mobileOpen"
                class="md:hidden border-t border-gray-100 bg-white px-4 pb-4 space-y-1">
                <a href="#how"       @click="mobileOpen=false"
                    class="block py-3 text-gray-600 hover:text-gray-900 border-b border-gray-50">
                    {{ $t('landing.howWorks') }}
                </a>
                <a href="#countries" @click="mobileOpen=false"
                    class="block py-3 text-gray-600 hover:text-gray-900 border-b border-gray-50">
                    {{ $t('landing.countriesNav') }}
                </a>
                <a href="#agencies"  @click="mobileOpen=false"
                    class="block py-3 text-gray-600 hover:text-gray-900 border-b border-gray-50">
                    {{ $t('landing.agencies') }}
                </a>
                <!-- Переключатель языка (мобильный) -->
                <div class="py-2 border-b border-gray-50 flex items-center gap-2">
                    <span class="text-xs text-gray-400">{{ $t('common.language') || 'Язык' }}:</span>
                    <button @click="toggleLocale"
                        class="flex items-center rounded-lg border border-gray-200 text-xs font-semibold overflow-hidden">
                        <span class="px-2.5 py-1.5 transition-colors"
                            :class="currentLocale() === 'ru' ? 'bg-[#0A1F44] text-white' : 'text-gray-400'">RU</span>
                        <span class="px-2.5 py-1.5 transition-colors"
                            :class="currentLocale() === 'uz' ? 'bg-[#0A1F44] text-white' : 'text-gray-400'">UZ</span>
                    </button>
                </div>
                <div class="pt-2 flex flex-col gap-2">
                    <template v-if="publicAuth.isLoggedIn">
                        <router-link to="/me/cases" @click="mobileOpen=false"
                            class="block py-3 px-4 bg-[#1BA97F] text-white text-sm font-semibold
                                   rounded-xl text-center">
                            {{ $t('common.myAccount') }}
                        </router-link>
                        <button @click="publicAuth.logout(); mobileOpen=false"
                            class="py-3 px-4 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl text-center w-full">
                            {{ $t('common.logout') }}
                        </button>
                    </template>
                    <template v-else>
                        <button @click="mobileOpen=false; $emit('open-auth')"
                            class="py-3 px-4 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl">
                            {{ $t('landing.checkChances') }}
                        </button>
                        <a href="/login"
                            class="py-3 px-4 border border-gray-200 text-[#0A1F44] text-sm font-semibold
                                   rounded-xl text-center">
                            {{ $t('common.crmForAgencies') }}
                        </a>
                    </template>
                </div>
            </div>
        </header>

        <!-- Контент -->
        <main class="pt-16">
            <slot />
        </main>

        <!-- Футер -->
        <footer class="bg-[#0A1F44] text-white/60 text-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8
                        flex flex-col sm:flex-row items-center justify-between gap-3">
                <span class="text-white font-bold text-base">VisaBor.uz</span>
                <span class="text-center">{{ $t('landing.footerPlatform') }}</span>
                <span>© 2026 VisaBor</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { setLocale, currentLocale } from '@/i18n';
import LogoBrand from '@/components/LogoBrand.vue';
import { formatPhone } from '@/utils/format';

const { t } = useI18n();
defineEmits(['open-auth']);
const publicAuth = usePublicAuthStore();
const mobileOpen = ref(false);

function toggleLocale() {
    setLocale(currentLocale() === 'ru' ? 'uz' : 'ru');
}
</script>

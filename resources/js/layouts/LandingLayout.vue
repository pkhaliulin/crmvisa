<template>
    <div class="min-h-screen bg-white">
        <!-- Хедер -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Логотип -->
                <a href="/" class="flex items-center select-none shrink-0">
                    <img src="/images/logo.png"
                         srcset="/images/logo@1x.png 1x, /images/logo@2x.png 2x"
                         alt="VisaBor" class="h-8 w-auto">
                </a>

                <!-- Десктоп навигация (скрываем если залогинен) -->
                <nav v-if="!publicAuth.isLoggedIn" class="hidden md:flex items-center gap-6 text-sm text-gray-500">
                    <a href="#how"       class="hover:text-gray-900 transition-colors">Как работает</a>
                    <a href="#countries" class="hover:text-gray-900 transition-colors">Страны</a>
                    <a href="#agencies"  class="hover:text-gray-900 transition-colors">Агентства</a>
                </nav>

                <!-- Если залогинен: имя + кнопка кабинета -->
                <div v-if="publicAuth.isLoggedIn" class="hidden md:flex items-center ml-auto gap-3">
                    <div class="text-right mr-1">
                        <div class="text-sm font-semibold text-[#0A1F44] leading-tight">
                            {{ publicAuth.user?.name || publicAuth.user?.phone }}
                        </div>
                        <div class="text-xs text-[#1BA97F] font-medium leading-tight">
                            Профиль {{ publicAuth.profilePercent }}%
                        </div>
                    </div>
                    <router-link to="/me/profile"
                        class="px-4 py-2 bg-[#1BA97F] text-white text-sm font-semibold rounded-lg hover:bg-[#169B72] transition-colors">
                        Мой кабинет
                    </router-link>
                    <button @click="publicAuth.logout()"
                        class="text-sm text-gray-400 hover:text-red-500 transition-colors">
                        Выйти
                    </button>
                </div>

                <!-- Если не залогинен: кнопки входа -->
                <div v-else class="hidden md:flex items-center gap-3">
                    <a href="/login" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                        CRM
                    </a>
                    <button @click="$emit('open-auth')"
                        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-lg
                               hover:bg-[#0d2a5e] transition-colors">
                        Войти
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
                    Как работает
                </a>
                <a href="#countries" @click="mobileOpen=false"
                    class="block py-3 text-gray-600 hover:text-gray-900 border-b border-gray-50">
                    Страны
                </a>
                <a href="#agencies"  @click="mobileOpen=false"
                    class="block py-3 text-gray-600 hover:text-gray-900 border-b border-gray-50">
                    Агентства
                </a>
                <div class="pt-2 flex flex-col gap-2">
                    <template v-if="publicAuth.isLoggedIn">
                        <router-link to="/me/profile" @click="mobileOpen=false"
                            class="block py-3 px-4 bg-[#1BA97F] text-white text-sm font-semibold
                                   rounded-xl text-center">
                            Мой кабинет
                        </router-link>
                        <button @click="publicAuth.logout(); mobileOpen=false"
                            class="py-3 px-4 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl text-center w-full">
                            Выйти
                        </button>
                    </template>
                    <template v-else>
                        <button @click="mobileOpen=false; $emit('open-auth')"
                            class="py-3 px-4 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl">
                            Проверить шансы на визу
                        </button>
                        <a href="/login"
                            class="py-3 px-4 border border-gray-200 text-[#0A1F44] text-sm font-semibold
                                   rounded-xl text-center">
                            CRM для агентств
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
                <span class="text-center">Visa-платформа для Центральной Азии</span>
                <span>© 2026 VisaBor</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { usePublicAuthStore } from '@/stores/publicAuth';
defineEmits(['open-auth']);
const publicAuth = usePublicAuthStore();
const mobileOpen = ref(false);
</script>

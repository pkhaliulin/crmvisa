<template>
    <div class="min-h-screen bg-white">
        <!-- Хедер -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <!-- Логотип -->
                <a href="/" class="flex items-center gap-1.5 select-none shrink-0">
                    <svg width="26" height="26" viewBox="0 0 28 28" fill="none">
                        <path d="M2 8L10 20L14 14L18 20L26 8" stroke="#1BA97F" stroke-width="3.5"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-xl font-bold tracking-tight text-[#0A1F44]">
                        Visa<span class="font-light text-[#1BA97F]">Bor</span>
                    </span>
                </a>

                <!-- Десктоп навигация -->
                <nav class="hidden md:flex items-center gap-6 text-sm text-gray-500">
                    <a href="#how"       class="hover:text-gray-900 transition-colors">Как работает</a>
                    <a href="#countries" class="hover:text-gray-900 transition-colors">Страны</a>
                    <a href="#agencies"  class="hover:text-gray-900 transition-colors">Агентства</a>
                </nav>

                <!-- Десктоп кнопки -->
                <div class="hidden md:flex items-center gap-3">
                    <template v-if="publicAuth.isLoggedIn">
                        <router-link to="/scoring"
                            class="text-sm font-medium text-[#0A1F44] hover:text-[#1BA97F] transition-colors">
                            Мой скоринг
                        </router-link>
                        <button @click="publicAuth.logout()"
                            class="text-sm text-gray-400 hover:text-gray-600 transition-colors">
                            Выйти
                        </button>
                    </template>
                    <template v-else>
                        <a href="/login" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                            CRM
                        </a>
                        <button @click="$emit('open-auth')"
                            class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-lg
                                   hover:bg-[#0d2a5e] transition-colors">
                            Войти
                        </button>
                    </template>
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
                        <router-link to="/scoring" @click="mobileOpen=false"
                            class="block py-3 px-4 bg-[#0A1F44] text-white text-sm font-semibold
                                   rounded-xl text-center">
                            Мой скоринг
                        </router-link>
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

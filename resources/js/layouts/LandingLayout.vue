<template>
    <div class="min-h-screen bg-white">
        <!-- Хедер -->
        <header class="fixed top-0 inset-x-0 z-50 bg-white/95 backdrop-blur border-b border-gray-100">
            <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
                <!-- Логотип -->
                <a href="/" class="flex items-center gap-1.5 select-none">
                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                        <path d="M2 8L10 20L14 14L18 20L26 8" stroke="#1BA97F" stroke-width="3.5"
                              stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-xl font-bold tracking-tight text-[#0A1F44]">
                        Visa<span class="font-light text-[#1BA97F]">Bor</span>
                    </span>
                </a>

                <!-- Навигация -->
                <nav class="hidden md:flex items-center gap-6 text-sm text-gray-500">
                    <a href="#how" class="hover:text-gray-900 transition-colors">Как это работает</a>
                    <a href="#countries" class="hover:text-gray-900 transition-colors">Страны</a>
                    <a href="#agencies" class="hover:text-gray-900 transition-colors">Агентства</a>
                </nav>

                <!-- Действия -->
                <div class="flex items-center gap-3">
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
                            CRM для агентств
                        </a>
                        <button @click="$emit('open-auth')"
                            class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-medium rounded-lg hover:bg-[#0d2a5e] transition-colors">
                            Войти
                        </button>
                    </template>
                </div>
            </div>
        </header>

        <!-- Контент -->
        <main class="pt-16">
            <slot />
        </main>

        <!-- Футер -->
        <footer class="border-t border-gray-100 bg-[#0A1F44] text-white/60 text-sm">
            <div class="max-w-6xl mx-auto px-6 py-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <span class="text-white font-semibold text-base">VisaBor.uz</span>
                <span>Visa-платформа для Центральной Азии</span>
                <span>© 2026 VisaBor</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { usePublicAuthStore } from '@/stores/publicAuth';
defineEmits(['open-auth']);
const publicAuth = usePublicAuthStore();
</script>

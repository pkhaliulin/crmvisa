<template>
    <div class="max-w-2xl mx-auto space-y-5">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#0A1F44]">Мои заявки</h2>
                <p class="text-sm text-gray-400 mt-0.5">История обращений в агентства</p>
            </div>
        </div>

        <!-- Profile completion prompt (if profile low) -->
        <div v-if="publicAuth.profilePercent < 60"
            class="bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1">
                <div class="font-semibold text-amber-800 text-sm">Заполните профиль для подачи заявки</div>
                <p class="text-xs text-amber-700 mt-0.5 mb-3">Агентство должно знать ваши данные для правильного оформления документов.</p>
                <router-link :to="{ name: 'me.profile' }"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-100 hover:bg-amber-200 px-3 py-1.5 rounded-lg transition-colors">
                    Заполнить профиль
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </router-link>
            </div>
        </div>

        <!-- Empty state with steps -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 sm:p-10 text-center">
            <div class="w-16 h-16 bg-[#0A1F44]/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#0A1F44]/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="font-bold text-[#0A1F44] text-base mb-2">Заявок пока нет</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">
                Пройдите скоринг — и мы подберём агентство под вашу страну назначения. Всё общение и документы будут здесь.
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

    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePublicAuthStore } from '@/stores/publicAuth';

const publicAuth = usePublicAuthStore();

const profileDone = computed(() => publicAuth.profilePercent >= 60);

const steps = computed(() => [
    {
        num: 1,
        title: 'Заполните профиль',
        desc: 'ФИО, дата рождения, гражданство',
        done: profileDone.value,
    },
    {
        num: 2,
        title: 'Пройдите скоринг',
        desc: 'Оценка шансов по 10+ странам за 2 минуты',
        done: false,
    },
    {
        num: 3,
        title: 'Агентство возьмёт вас',
        desc: 'Специалист свяжется и откроет заявку',
        done: false,
    },
]);

const nextStepRoute = computed(() =>
    profileDone.value ? { name: 'me.scoring' } : { name: 'me.profile' }
);

const nextStepLabel = computed(() =>
    profileDone.value ? 'Пройти скоринг' : 'Заполнить профиль'
);
</script>

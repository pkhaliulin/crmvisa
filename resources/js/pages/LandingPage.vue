<template>
    <LandingLayout @open-auth="showAuth = true">

        <!-- ================================================================
             HERO
        ================================================================ -->
        <section class="min-h-[92vh] flex items-center justify-center px-4 sm:px-6
                        bg-gradient-to-b from-white to-slate-50 py-16">
            <div class="max-w-3xl mx-auto text-center w-full">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#1BA97F]/10
                            text-[#1BA97F] text-xs sm:text-sm font-medium mb-6 sm:mb-8">
                    <span class="w-2 h-2 bg-[#1BA97F] rounded-full animate-pulse shrink-0"></span>
                    Проверьте шансы бесплатно — займёт 3 минуты
                </div>

                <h1 class="text-3xl sm:text-5xl md:text-6xl font-bold text-[#0A1F44]
                           leading-tight mb-4 sm:mb-6">
                    Получите визу<br class="hidden sm:block">
                    <span class="text-[#1BA97F]"> быстрее и увереннее</span>
                </h1>

                <p class="text-base sm:text-xl text-gray-500 max-w-xl mx-auto
                          mb-8 sm:mb-10 leading-relaxed px-2">
                    Автоматическая проверка шансов на одобрение,
                    OCR-распознавание паспорта и сопровождение агентства.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center px-4 sm:px-0">
                    <button @click="showAuth = true"
                        class="w-full sm:w-auto px-6 sm:px-8 py-4 bg-[#0A1F44] text-white
                               font-semibold rounded-xl text-base sm:text-lg
                               hover:bg-[#0d2a5e] transition-all shadow-lg shadow-[#0A1F44]/20
                               active:scale-95">
                        Проверить шансы на визу
                    </button>
                    <a href="#countries"
                        class="w-full sm:w-auto px-6 sm:px-8 py-4 bg-white text-[#0A1F44]
                               font-semibold rounded-xl text-base sm:text-lg border border-gray-200
                               hover:border-gray-300 transition-all text-center active:scale-95">
                        Выбрать страну
                    </a>
                </div>

                <!-- Телеграм-бот CTA -->
                <div class="mt-6 flex justify-center">
                    <a href="https://t.me/visaborbot" target="_blank"
                        class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-[#0A1F44]
                               transition-colors">
                        <svg class="w-5 h-5 text-[#229ED9]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                        </svg>
                        Или напишите нам в Telegram-бот
                    </a>
                </div>

                <!-- Статы -->
                <div class="mt-12 sm:mt-16 grid grid-cols-3 gap-4 sm:gap-8 max-w-sm sm:max-w-lg mx-auto">
                    <div>
                        <div class="text-2xl sm:text-3xl font-bold text-[#0A1F44]">10+</div>
                        <div class="text-xs sm:text-sm text-gray-400 mt-1">стран</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-bold text-[#0A1F44]">95%</div>
                        <div class="text-xs sm:text-sm text-gray-400 mt-1">точность</div>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-bold text-[#0A1F44]">3 мин</div>
                        <div class="text-xs sm:text-sm text-gray-400 mt-1">до результата</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             КАК ЭТО РАБОТАЕТ
        ================================================================ -->
        <section id="how" class="py-16 sm:py-24 px-4 sm:px-6">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-10 sm:mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#0A1F44] mb-3">
                        Как это работает
                    </h2>
                    <p class="text-gray-500 text-base sm:text-lg">
                        Три шага от регистрации до результата
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div v-for="step in steps" :key="step.num"
                        class="relative p-6 sm:p-8 rounded-2xl bg-white border border-gray-100
                               shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4 sm:mb-6"
                             :class="step.bg">
                            <span class="text-2xl">{{ step.icon }}</span>
                        </div>
                        <div class="absolute top-5 right-5 text-4xl font-bold text-gray-100 select-none">
                            {{ step.num }}
                        </div>
                        <h3 class="font-bold text-[#0A1F44] text-base sm:text-lg mb-2">
                            {{ step.title }}
                        </h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             ПОПУЛЯРНЫЕ НАПРАВЛЕНИЯ
        ================================================================ -->
        <section id="countries" class="py-16 sm:py-24 px-4 sm:px-6 bg-slate-50">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-10 sm:mb-16">
                    <h2 class="text-2xl sm:text-3xl font-bold text-[#0A1F44] mb-3">
                        Популярные направления
                    </h2>
                    <p class="text-gray-500 text-base sm:text-lg">
                        Страны, где для граждан Узбекистана требуется виза
                    </p>
                </div>

                <!-- Скелетон -->
                <div v-if="loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    <div v-for="i in 10" :key="i"
                        class="h-24 rounded-2xl bg-white animate-pulse border border-gray-100"></div>
                </div>

                <!-- Карточки стран -->
                <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                    <button v-for="c in countries" :key="c.code"
                        @click="selectCountry(c)"
                        class="group p-4 sm:p-5 bg-white rounded-2xl border border-gray-100
                               text-center hover:border-[#1BA97F] hover:shadow-md
                               transition-all duration-200 active:scale-95"
                        :class="{ 'border-[#1BA97F] shadow-md': selectedCountry?.code === c.code }">
                        <div class="text-3xl sm:text-3xl mb-2">{{ c.flag }}</div>
                        <div class="font-semibold text-[#0A1F44] text-xs sm:text-sm leading-tight">
                            {{ c.name }}
                        </div>
                        <div class="text-xs text-[#1BA97F] mt-1 opacity-0 group-hover:opacity-100
                                    transition-opacity hidden sm:block">
                            Проверить →
                        </div>
                    </button>
                </div>

                <!-- CTA под странами -->
                <transition name="fade">
                    <div v-if="selectedCountry"
                        class="mt-6 p-4 sm:p-6 bg-white rounded-2xl border border-[#1BA97F]/30
                               flex flex-col sm:flex-row items-start sm:items-center
                               justify-between gap-4 shadow-sm">
                        <div>
                            <div class="font-bold text-[#0A1F44] text-base sm:text-lg">
                                {{ selectedCountry.flag }} {{ selectedCountry.name }}
                            </div>
                            <div class="text-gray-500 text-sm mt-0.5">
                                Узнайте вероятность одобрения за 3 минуты
                            </div>
                        </div>
                        <button @click="startScoring"
                            class="w-full sm:w-auto px-6 py-3 bg-[#1BA97F] text-white font-semibold
                                   rounded-xl hover:bg-[#17956f] transition-colors
                                   whitespace-nowrap active:scale-95">
                            Проверить шанс
                        </button>
                    </div>
                </transition>
            </div>
        </section>

        <!-- ================================================================
             ПРЕИМУЩЕСТВА СКОРИНГА
        ================================================================ -->
        <section class="py-16 sm:py-24 px-4 sm:px-6">
            <div class="max-w-5xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10 sm:gap-16 items-center">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-[#0A1F44] mb-4 sm:mb-6">
                            Умный скоринг — знайте шансы заранее
                        </h2>
                        <p class="text-gray-500 leading-relaxed mb-6 sm:mb-8 text-sm sm:text-base">
                            Алгоритм анализирует 7 факторов: финансы, занятость, семейный статус,
                            имущество, историю поездок и цель визита. Результат — оценка 0–100
                            с конкретными советами.
                        </p>
                        <ul class="space-y-3 sm:space-y-4">
                            <li v-for="f in features" :key="f"
                                class="flex items-start gap-3 text-gray-700 text-sm sm:text-base">
                                <span class="text-[#1BA97F] font-bold text-lg leading-none mt-0.5 shrink-0">✓</span>
                                {{ f }}
                            </li>
                        </ul>
                        <button @click="showAuth = true"
                            class="mt-6 sm:mt-8 w-full sm:w-auto px-6 py-3 bg-[#0A1F44] text-white
                                   font-semibold rounded-xl hover:bg-[#0d2a5e] transition-colors
                                   active:scale-95">
                            Начать бесплатно
                        </button>
                    </div>

                    <!-- Мок-скоринг карточка -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-xl p-6 sm:p-8">
                        <div class="text-center mb-5">
                            <div class="text-sm text-gray-400 mb-2">Вероятность одобрения</div>
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-32 h-32 sm:w-40 sm:h-40 -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none"
                                            stroke="#f1f5f9" stroke-width="10"/>
                                    <circle cx="60" cy="60" r="50" fill="none"
                                            stroke="#1BA97F" stroke-width="10"
                                            stroke-linecap="round"
                                            :stroke-dasharray="`${mockScore * 3.14} 314`"
                                            class="transition-all duration-1000"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div>
                                        <div class="text-3xl sm:text-4xl font-bold text-[#0A1F44]">
                                            {{ mockScore }}%
                                        </div>
                                        <div class="text-xs text-[#1BA97F] font-medium text-center">
                                            Высокий
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2.5 mb-5">
                            <div v-for="b in mockBreakdown" :key="b.label"
                                class="flex items-center gap-2 sm:gap-3">
                                <span class="text-xs text-gray-400 w-20 sm:w-24 shrink-0">
                                    {{ b.label }}
                                </span>
                                <div class="flex-1 bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-[#1BA97F] transition-all duration-700"
                                         :style="{ width: b.val + '%' }"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600 w-7 text-right">
                                    {{ b.val }}
                                </span>
                            </div>
                        </div>

                        <div class="p-3 bg-amber-50 rounded-xl text-xs text-amber-700 leading-relaxed">
                            Добавьте справку о доходах, чтобы повысить скоринг до 85%
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             TELEGRAM БОТ
        ================================================================ -->
        <section class="py-16 sm:py-24 px-4 sm:px-6 bg-slate-50">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm
                            p-6 sm:p-10 flex flex-col sm:flex-row items-center gap-6 sm:gap-10">
                    <div class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-[#229ED9]/10
                                flex items-center justify-center">
                        <svg class="w-10 h-10 sm:w-12 sm:h-12 text-[#229ED9]"
                             viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                        </svg>
                    </div>
                    <div class="flex-1 text-center sm:text-left">
                        <h3 class="text-xl sm:text-2xl font-bold text-[#0A1F44] mb-2">
                            Telegram-бот VisaBor
                        </h3>
                        <p class="text-gray-500 text-sm sm:text-base mb-4">
                            Проверьте шансы на визу прямо в Telegram. Ответьте на 4 вопроса
                            и получите результат с рекомендациями — без регистрации.
                        </p>
                        <a href="https://t.me/visaborbot" target="_blank"
                            class="inline-flex items-center gap-2 px-5 py-3 bg-[#229ED9] text-white
                                   font-semibold rounded-xl hover:bg-[#1a8fc4] transition-colors
                                   active:scale-95">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                            </svg>
                            Открыть бот
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             АГЕНТСТВА
        ================================================================ -->
        <section id="agencies" class="py-16 sm:py-24 px-4 sm:px-6 bg-[#0A1F44]">
            <div class="max-w-5xl mx-auto text-center">
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-3 sm:mb-4">
                    Проверенные агентства
                </h2>
                <p class="text-white/60 text-base sm:text-lg mb-8 sm:mb-12">
                    После скоринга вас соединят с агентством,
                    специализирующимся на вашей стране
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-8 sm:mb-10">
                    <div v-for="b in agencyBenefits" :key="b.title"
                        class="p-5 sm:p-6 bg-white/5 rounded-2xl border border-white/10 text-left">
                        <div class="text-2xl mb-3">{{ b.icon }}</div>
                        <div class="font-bold text-white mb-1 text-sm sm:text-base">{{ b.title }}</div>
                        <div class="text-white/50 text-xs sm:text-sm">{{ b.desc }}</div>
                    </div>
                </div>
                <button @click="showAuth = true"
                    class="w-full sm:w-auto px-8 py-4 bg-[#1BA97F] text-white font-semibold
                           rounded-xl text-base sm:text-lg hover:bg-[#17956f] transition-colors
                           active:scale-95">
                    Найти агентство
                </button>
            </div>
        </section>

        <!-- Модал авторизации -->
        <PhoneAuthModal
            v-if="showAuth"
            :preselected-country="selectedCountry?.code"
            @close="showAuth = false"
            @success="onAuthSuccess"
        />
    </LandingLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import LandingLayout from '@/layouts/LandingLayout.vue';
import PhoneAuthModal from '@/pages/public/PhoneAuthModal.vue';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';

const router     = useRouter();
const publicAuth = usePublicAuthStore();
const showAuth   = ref(false);
const loading    = ref(true);
const countries  = ref([]);
const selectedCountry = ref(null);
const mockScore  = ref(0);

onMounted(async () => {
    try {
        const { data } = await publicPortalApi.countries();
        countries.value = data.data;
    } finally {
        loading.value = false;
    }
    setTimeout(() => { mockScore.value = 72; }, 600);
});

const steps = [
    { num: '01', icon: '📱', bg: 'bg-blue-50',   title: 'Войдите через телефон',   desc: 'Одно SMS — и вы в системе. Без паролей и лишней регистрации.' },
    { num: '02', icon: '🛂', bg: 'bg-green-50',  title: 'Заполните профиль',        desc: 'OCR считает данные паспорта автоматически. Или введите за 2 минуты.' },
    { num: '03', icon: '🎯', bg: 'bg-purple-50', title: 'Получите результат',        desc: 'Скоринг 0–100 с рекомендациями. Выберите агентство и подавайте.' },
];

const features = [
    'Бесплатная проверка для 10+ стран',
    'Персональные рекомендации по улучшению профиля',
    'OCR-распознавание паспорта — автозаполнение',
    'Подключение к агентству по результатам',
];

const mockBreakdown = [
    { label: 'Финансы',     val: 78 },
    { label: 'Привязанность', val: 85 },
    { label: 'История',     val: 55 },
    { label: 'Профиль',     val: 90 },
];

const agencyBenefits = [
    { icon: '⭐', title: 'Рейтинг и отзывы',  desc: 'Только верифицированные агентства с реальными отзывами' },
    { icon: '📍', title: 'Трекинг заявки',    desc: 'Статус документов в реальном времени' },
    { icon: '💬', title: 'Прямая связь',      desc: 'Чат с менеджером без посредников' },
];

function selectCountry(c) {
    selectedCountry.value = selectedCountry.value?.code === c.code ? null : c;
}

function startScoring() {
    if (publicAuth.isLoggedIn) {
        router.push({ name: 'public.scoring', query: { country: selectedCountry.value?.code } });
    } else {
        showAuth.value = true;
    }
}

function onAuthSuccess() {
    showAuth.value = false;
    router.push({ name: 'public.scoring', query: { country: selectedCountry.value?.code } });
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s, transform 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-4px); }
</style>

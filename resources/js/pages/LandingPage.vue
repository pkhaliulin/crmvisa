<template>
    <LandingLayout @open-auth="showAuth = true">
        <!-- ================================================================
             HERO
        ================================================================ -->
        <section class="min-h-[90vh] flex items-center justify-center px-6 bg-gradient-to-b from-white to-slate-50">
            <div class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#1BA97F]/10
                            text-[#1BA97F] text-sm font-medium mb-8">
                    <span class="w-2 h-2 bg-[#1BA97F] rounded-full animate-pulse"></span>
                    Проверьте шансы бесплатно
                </div>

                <h1 class="text-5xl md:text-6xl font-bold text-[#0A1F44] leading-tight mb-6">
                    Получите визу<br>
                    <span class="text-[#1BA97F]">быстрее и увереннее</span>
                </h1>

                <p class="text-xl text-gray-500 max-w-xl mx-auto mb-10 leading-relaxed">
                    Автоматическая проверка шансов на одобрение, OCR-распознавание паспорта
                    и сопровождение агентства в одной платформе.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button @click="showAuth = true"
                        class="px-8 py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               text-lg hover:bg-[#0d2a5e] transition-all shadow-lg shadow-[#0A1F44]/20">
                        Проверить шансы на визу
                    </button>
                    <a href="#countries"
                        class="px-8 py-4 bg-white text-[#0A1F44] font-semibold rounded-xl
                               text-lg border border-gray-200 hover:border-gray-300 transition-all">
                        Выбрать страну
                    </a>
                </div>

                <!-- Статы доверия -->
                <div class="mt-16 grid grid-cols-3 gap-8 max-w-lg mx-auto">
                    <div>
                        <div class="text-3xl font-bold text-[#0A1F44]">10+</div>
                        <div class="text-sm text-gray-400 mt-1">стран</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-[#0A1F44]">95%</div>
                        <div class="text-sm text-gray-400 mt-1">точность скоринга</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold text-[#0A1F44]">3 мин</div>
                        <div class="text-sm text-gray-400 mt-1">до результата</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             КАК ЭТО РАБОТАЕТ
        ================================================================ -->
        <section id="how" class="py-24 px-6">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-[#0A1F44] mb-4">Как это работает</h2>
                    <p class="text-gray-500 text-lg">Три шага от регистрации до результата</p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="step in steps" :key="step.num"
                        class="relative p-8 rounded-2xl bg-white border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-6"
                             :class="step.bg">
                            <span class="text-2xl">{{ step.icon }}</span>
                        </div>
                        <div class="absolute top-6 right-6 text-4xl font-bold text-gray-100">
                            {{ step.num }}
                        </div>
                        <h3 class="font-bold text-[#0A1F44] text-lg mb-2">{{ step.title }}</h3>
                        <p class="text-gray-500 text-sm leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             ПОПУЛЯРНЫЕ НАПРАВЛЕНИЯ
        ================================================================ -->
        <section id="countries" class="py-24 px-6 bg-slate-50">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-[#0A1F44] mb-4">Популярные направления</h2>
                    <p class="text-gray-500 text-lg">Страны, где для граждан Узбекистана требуется виза</p>
                </div>

                <div v-if="loading" class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div v-for="i in 10" :key="i"
                        class="h-28 rounded-2xl bg-white animate-pulse border border-gray-100"></div>
                </div>

                <div v-else class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <button v-for="c in countries" :key="c.code"
                        @click="selectCountry(c)"
                        class="group p-5 bg-white rounded-2xl border border-gray-100 text-center
                               hover:border-[#1BA97F] hover:shadow-md transition-all duration-200
                               cursor-pointer"
                        :class="{ 'border-[#1BA97F] shadow-md': selectedCountry?.code === c.code }">
                        <div class="text-3xl mb-2">{{ c.flag }}</div>
                        <div class="font-semibold text-[#0A1F44] text-sm">{{ c.name }}</div>
                        <div class="text-xs text-[#1BA97F] mt-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            Проверить →
                        </div>
                    </button>
                </div>

                <!-- CTA под странами -->
                <div v-if="selectedCountry" class="mt-8 p-6 bg-white rounded-2xl border border-[#1BA97F]/30
                                                    flex items-center justify-between gap-4 shadow-sm">
                    <div>
                        <div class="font-bold text-[#0A1F44] text-lg">
                            {{ selectedCountry.flag }} {{ selectedCountry.name }}
                        </div>
                        <div class="text-gray-500 text-sm mt-0.5">
                            Узнайте вашу вероятность одобрения за 3 минуты
                        </div>
                    </div>
                    <button @click="startScoring"
                        class="px-6 py-3 bg-[#1BA97F] text-white font-semibold rounded-xl
                               hover:bg-[#17956f] transition-colors whitespace-nowrap">
                        Проверить шанс
                    </button>
                </div>
            </div>
        </section>

        <!-- ================================================================
             СКОРИНГ (преимущества)
        ================================================================ -->
        <section class="py-24 px-6">
            <div class="max-w-5xl mx-auto">
                <div class="grid md:grid-cols-2 gap-16 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-[#0A1F44] mb-6">
                            Умный скоринг — знайте шансы заранее
                        </h2>
                        <p class="text-gray-500 leading-relaxed mb-8">
                            Наш алгоритм анализирует 7 факторов: финансы, занятость,
                            семейный статус, имущество, историю поездок и цель визита.
                            Результат — персональная оценка 0–100 с конкретными советами.
                        </p>
                        <ul class="space-y-4">
                            <li v-for="f in features" :key="f"
                                class="flex items-start gap-3 text-gray-700">
                                <span class="text-[#1BA97F] font-bold text-lg leading-none mt-0.5">✓</span>
                                {{ f }}
                            </li>
                        </ul>
                        <button @click="showAuth = true"
                            class="mt-8 px-6 py-3 bg-[#0A1F44] text-white font-semibold rounded-xl
                                   hover:bg-[#0d2a5e] transition-colors">
                            Начать бесплатно
                        </button>
                    </div>

                    <!-- Мок-карточка скоринга -->
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-xl p-8">
                        <div class="text-center mb-6">
                            <div class="text-sm text-gray-400 mb-2">Вероятность одобрения</div>
                            <div class="relative inline-flex items-center justify-center">
                                <svg class="w-40 h-40 -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="10"/>
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#1BA97F" stroke-width="10"
                                            stroke-linecap="round"
                                            :stroke-dasharray="`${mockScore * 3.14} 314`"
                                            class="transition-all duration-1000"/>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div>
                                        <div class="text-4xl font-bold text-[#0A1F44]">{{ mockScore }}%</div>
                                        <div class="text-xs text-[#1BA97F] font-medium text-center">Высокий</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div v-for="b in mockBreakdown" :key="b.label" class="flex items-center gap-3">
                                <span class="text-xs text-gray-400 w-24 shrink-0">{{ b.label }}</span>
                                <div class="flex-1 bg-gray-100 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-[#1BA97F] transition-all duration-700"
                                         :style="{ width: b.val + '%' }"></div>
                                </div>
                                <span class="text-xs font-medium text-gray-600 w-8 text-right">{{ b.val }}</span>
                            </div>
                        </div>

                        <div class="p-3 bg-amber-50 rounded-xl text-xs text-amber-700">
                            Добавьте справку о доходах, чтобы повысить скоринг до 85%
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================
             АГЕНТСТВА
        ================================================================ -->
        <section id="agencies" class="py-24 px-6 bg-[#0A1F44]">
            <div class="max-w-5xl mx-auto text-center">
                <h2 class="text-3xl font-bold text-white mb-4">Работаем с проверенными агентствами</h2>
                <p class="text-white/60 text-lg mb-12">
                    После скоринга вас соединят с агентством, которое специализируется на вашей стране
                </p>
                <div class="grid md:grid-cols-3 gap-6 mb-10">
                    <div v-for="b in agencyBenefits" :key="b.title"
                        class="p-6 bg-white/5 rounded-2xl border border-white/10 text-left">
                        <div class="text-2xl mb-3">{{ b.icon }}</div>
                        <div class="font-bold text-white mb-1">{{ b.title }}</div>
                        <div class="text-white/50 text-sm">{{ b.desc }}</div>
                    </div>
                </div>
                <button @click="showAuth = true"
                    class="px-8 py-4 bg-[#1BA97F] text-white font-semibold rounded-xl text-lg
                           hover:bg-[#17956f] transition-colors">
                    Найти агентство
                </button>
            </div>
        </section>

        <!-- ================================================================
             МОДАЛ АВТОРИЗАЦИИ
        ================================================================ -->
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

const router      = useRouter();
const publicAuth  = usePublicAuthStore();
const showAuth    = ref(false);
const loading     = ref(true);
const countries   = ref([]);
const selectedCountry = ref(null);
const mockScore   = ref(0);

onMounted(async () => {
    try {
        const { data } = await publicPortalApi.countries();
        countries.value = data.data;
    } finally {
        loading.value = false;
    }
    // Анимация скора в демо-карточке
    setTimeout(() => { mockScore.value = 72; }, 500);
});

const steps = [
    { num: '01', icon: '📱', bg: 'bg-blue-50',  title: 'Войдите через телефон',      desc: 'Одно SMS — и вы в системе. Без паролей. Никакой лишней регистрации.' },
    { num: '02', icon: '🛂', bg: 'bg-green-50', title: 'Заполните профиль или загрузите паспорт', desc: 'OCR автоматически считает данные паспорта. Или введите вручную за 2 минуты.' },
    { num: '03', icon: '🎯', bg: 'bg-purple-50', title: 'Получите результат',         desc: 'Скоринг 0–100 с рекомендациями. Выберите агентство и начните подачу.' },
];

const features = [
    'Бесплатная проверка для 10+ стран',
    'Персональные рекомендации по улучшению профиля',
    'OCR-распознавание паспорта — данные заполнятся автоматически',
    'Подключение к агентству по результатам скоринга',
];

const mockBreakdown = [
    { label: 'Финансы',     val: 78 },
    { label: 'Привязанность', val: 85 },
    { label: 'История',     val: 55 },
    { label: 'Профиль',     val: 90 },
];

const agencyBenefits = [
    { icon: '⭐', title: 'Рейтинг и отзывы',   desc: 'Только верифицированные агентства с реальными отзывами' },
    { icon: '📍', title: 'Трекинг заявки',     desc: 'Статус в реальном времени — где ваши документы прямо сейчас' },
    { icon: '💬', title: 'Прямая связь',       desc: 'Чат с менеджером без посредников и скрытых комиссий' },
];

function selectCountry(c) {
    selectedCountry.value = c;
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

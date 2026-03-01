<template>
    <LandingLayout>
        <div class="min-h-screen bg-slate-50 py-12 px-6">
            <div class="max-w-4xl mx-auto">

                <!-- Хедер страницы -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-[#0A1F44]">Ваш скоринг</h1>
                        <p class="text-gray-500 text-sm mt-0.5">Вероятность одобрения визы по странам</p>
                    </div>
                    <!-- Прогресс профиля -->
                    <div class="text-right">
                        <div class="text-sm text-gray-400 mb-1">Профиль заполнен на</div>
                        <div class="flex items-center gap-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full bg-[#1BA97F] transition-all duration-500"
                                     :style="{ width: profilePercent + '%' }"></div>
                            </div>
                            <span class="text-sm font-bold text-[#0A1F44]">{{ profilePercent }}%</span>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Левая колонка: Профиль -->
                    <div class="md:col-span-1">
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                            <h2 class="font-bold text-[#0A1F44] mb-4">Ваш профиль</h2>

                            <div class="space-y-3">
                                <!-- Занятость -->
                                <div>
                                    <label class="text-xs text-gray-400 mb-1 block">Занятость</label>
                                    <select v-model="profile.employment_type"
                                        @change="updateProfile"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                                               text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                                        <option value="">Не указано</option>
                                        <option value="employed">Наёмный сотрудник</option>
                                        <option value="business_owner">Владелец бизнеса</option>
                                        <option value="self_employed">Самозанятый / ИП</option>
                                        <option value="retired">Пенсионер</option>
                                        <option value="student">Студент</option>
                                        <option value="unemployed">Безработный</option>
                                    </select>
                                </div>

                                <!-- Доход -->
                                <div>
                                    <label class="text-xs text-gray-400 mb-1 block">Доход в месяц ($)</label>
                                    <select v-model="profile.monthly_income_usd"
                                        @change="updateProfile"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                                               text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                                        <option value="">Не указано</option>
                                        <option :value="300">До $500</option>
                                        <option :value="800">$500–1 000</option>
                                        <option :value="1500">$1 000–2 000</option>
                                        <option :value="3000">$2 000–4 000</option>
                                        <option :value="5000">Более $4 000</option>
                                    </select>
                                </div>

                                <!-- Семейное положение -->
                                <div>
                                    <label class="text-xs text-gray-400 mb-1 block">Семейное положение</label>
                                    <select v-model="profile.marital_status"
                                        @change="updateProfile"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm
                                               text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                                        <option value="">Не указано</option>
                                        <option value="married">Женат / замужем</option>
                                        <option value="single">Холост / незамужем</option>
                                        <option value="divorced">Разведён/а</option>
                                        <option value="widowed">Вдовец/вдова</option>
                                    </select>
                                </div>

                                <!-- Чекбоксы -->
                                <div class="pt-2 space-y-2">
                                    <label v-for="cb in checkboxes" :key="cb.key"
                                        class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative">
                                            <input type="checkbox" v-model="profile[cb.key]"
                                                @change="updateProfile" class="sr-only peer" />
                                            <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-[#1BA97F]
                                                        peer-checked:border-[#1BA97F] transition-colors"></div>
                                            <div class="absolute inset-0 flex items-center justify-center
                                                        text-white text-xs font-bold opacity-0 peer-checked:opacity-100">✓</div>
                                        </div>
                                        <span class="text-sm text-gray-600 group-hover:text-gray-900">{{ cb.label }}</span>
                                    </label>
                                </div>

                                <!-- Кнопка пересчёта -->
                                <button @click="loadScores" :disabled="scoringLoading"
                                    class="mt-4 w-full py-2.5 bg-[#0A1F44] text-white text-sm font-semibold
                                           rounded-xl hover:bg-[#0d2a5e] transition-colors disabled:opacity-60">
                                    {{ scoringLoading ? 'Считаем...' : 'Пересчитать' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Правая колонка: Результаты -->
                    <div class="md:col-span-2 space-y-4">
                        <!-- Детальный скоринг выбранной страны -->
                        <div v-if="activeScore" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <div class="text-xs text-gray-400 mb-1">Выбрана страна</div>
                                    <div class="font-bold text-[#0A1F44] text-lg">
                                        {{ countryName(activeScore.country_code) }}
                                    </div>
                                </div>
                                <!-- Круговой скор -->
                                <div class="relative flex items-center justify-center">
                                    <svg class="w-24 h-24 -rotate-90" viewBox="0 0 80 80">
                                        <circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="7"/>
                                        <circle cx="40" cy="40" r="32" fill="none"
                                                :stroke="scoreColor(activeScore.score)" stroke-width="7"
                                                stroke-linecap="round"
                                                :stroke-dasharray="`${activeScore.score * 2.01} 201`"
                                                class="transition-all duration-700"/>
                                    </svg>
                                    <div class="absolute text-center">
                                        <div class="text-xl font-bold text-[#0A1F44]">{{ activeScore.score }}%</div>
                                        <div class="text-[10px]" :style="{ color: scoreColor(activeScore.score) }">
                                            {{ activeScore.label }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Разбивка -->
                            <div class="space-y-3 mb-6">
                                <div v-for="(val, key) in activeScore.breakdown" :key="key"
                                    class="flex items-center gap-3">
                                    <span class="text-xs text-gray-400 w-28 shrink-0">{{ breakdownLabel(key) }}</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full transition-all duration-700"
                                             :class="val >= 60 ? 'bg-[#1BA97F]' : val >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                             :style="{ width: val + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 w-8 text-right">{{ val }}</span>
                                </div>
                            </div>

                            <!-- Рекомендации -->
                            <div v-if="activeScore.recommendations?.length"
                                class="p-4 bg-amber-50 rounded-xl space-y-2">
                                <div class="text-xs font-semibold text-amber-700 mb-2">
                                    Чтобы повысить шансы:
                                </div>
                                <div v-for="rec in activeScore.recommendations" :key="rec"
                                    class="flex items-start gap-2 text-sm text-amber-800">
                                    <span class="shrink-0">→</span>
                                    <span>{{ rec }}</span>
                                </div>
                            </div>

                            <!-- Найти агентство -->
                            <button class="mt-4 w-full py-3 bg-[#1BA97F] text-white font-semibold rounded-xl
                                          hover:bg-[#17956f] transition-colors">
                                Найти агентство для {{ countryName(activeScore.country_code) }}
                            </button>
                        </div>

                        <!-- Список всех стран -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                                <h3 class="font-bold text-[#0A1F44]">Все страны</h3>
                                <span class="text-xs text-gray-400">{{ scores.length }} стран</span>
                            </div>

                            <div v-if="scoringLoading" class="p-6 space-y-3">
                                <div v-for="i in 8" :key="i"
                                    class="h-14 bg-gray-50 rounded-xl animate-pulse"></div>
                            </div>

                            <div v-else class="divide-y divide-gray-50">
                                <button
                                    v-for="s in scores"
                                    :key="s.country_code"
                                    @click="activeScore = s"
                                    class="w-full px-6 py-4 flex items-center gap-4 hover:bg-slate-50
                                           transition-colors text-left"
                                    :class="{ 'bg-[#1BA97F]/5': activeScore?.country_code === s.country_code }">
                                    <span class="text-2xl">{{ countryFlag(s.country_code) }}</span>
                                    <div class="flex-1">
                                        <div class="font-medium text-[#0A1F44] text-sm">{{ countryName(s.country_code) }}</div>
                                    </div>
                                    <!-- Мини бар -->
                                    <div class="w-24 bg-gray-100 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all duration-700"
                                             :class="s.score >= 60 ? 'bg-[#1BA97F]' : s.score >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                             :style="{ width: s.score + '%' }"></div>
                                    </div>
                                    <div class="w-12 text-right">
                                        <span class="text-sm font-bold"
                                              :class="s.score >= 60 ? 'text-[#1BA97F]' : s.score >= 40 ? 'text-amber-500' : 'text-red-500'">
                                            {{ s.score }}%
                                        </span>
                                    </div>
                                    <span class="text-gray-300 text-sm">›</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandingLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import LandingLayout from '@/layouts/LandingLayout.vue';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';

const route       = useRoute();
const publicAuth  = usePublicAuthStore();
const scores      = ref([]);
const activeScore = ref(null);
const scoringLoading = ref(false);

const profilePercent = computed(() => publicAuth.profilePercent);

const profile = ref({
    employment_type:    publicAuth.user?.employment_type    ?? '',
    monthly_income_usd: publicAuth.user?.monthly_income_usd ?? '',
    marital_status:     publicAuth.user?.marital_status     ?? '',
    has_children:       publicAuth.user?.has_children       ?? false,
    has_property:       publicAuth.user?.has_property       ?? false,
    has_car:            publicAuth.user?.has_car            ?? false,
    has_schengen_visa:  publicAuth.user?.has_schengen_visa  ?? false,
    has_us_visa:        publicAuth.user?.has_us_visa        ?? false,
    had_visa_refusal:   publicAuth.user?.had_visa_refusal   ?? false,
    had_overstay:       publicAuth.user?.had_overstay       ?? false,
});

const checkboxes = [
    { key: 'has_children',      label: 'Есть дети (живут в Узбекистане)' },
    { key: 'has_property',      label: 'Есть недвижимость' },
    { key: 'has_car',           label: 'Есть автомобиль' },
    { key: 'has_schengen_visa', label: 'Есть шенгенская виза' },
    { key: 'has_us_visa',       label: 'Есть виза США' },
    { key: 'had_visa_refusal',  label: 'Был отказ в визе' },
];

const countryMap = {
    DE: { name: 'Германия',       flag: '🇩🇪' },
    ES: { name: 'Испания',         flag: '🇪🇸' },
    FR: { name: 'Франция',         flag: '🇫🇷' },
    IT: { name: 'Италия',          flag: '🇮🇹' },
    PL: { name: 'Польша',          flag: '🇵🇱' },
    CZ: { name: 'Чехия',           flag: '🇨🇿' },
    GB: { name: 'Великобритания',  flag: '🇬🇧' },
    US: { name: 'США',             flag: '🇺🇸' },
    CA: { name: 'Канада',          flag: '🇨🇦' },
    KR: { name: 'Южная Корея',     flag: '🇰🇷' },
    AE: { name: 'ОАЭ',             flag: '🇦🇪' },
};

const breakdownLabels = {
    finance: 'Финансы',
    ties:    'Привязанность',
    travel:  'История виз',
    profile: 'Профиль',
};

function countryName(code)   { return countryMap[code]?.name ?? code; }
function countryFlag(code)   { return countryMap[code]?.flag ?? '🌍'; }
function breakdownLabel(key) { return breakdownLabels[key]   ?? key; }
function scoreColor(score)   { return score >= 60 ? '#1BA97F' : score >= 40 ? '#f59e0b' : '#ef4444'; }

async function updateProfile() {
    try {
        await publicPortalApi.updateProfile(profile.value);
        await publicAuth.fetchMe();
    } catch { /* тихо */ }
}

async function loadScores() {
    scoringLoading.value = true;
    try {
        const { data } = await publicPortalApi.scoreAll();
        scores.value = data.data.scores;
        // Выбираем страну из query-параметра или первую
        const preselect = route.query.country?.toUpperCase();
        activeScore.value = scores.value.find(s => s.country_code === preselect) ?? scores.value[0];
    } finally {
        scoringLoading.value = false;
    }
}

onMounted(loadScores);
</script>

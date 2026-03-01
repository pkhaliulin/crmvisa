<template>
    <div class="min-h-0">
            <div class="max-w-4xl mx-auto">

                <!-- Хедер страницы -->
                <div class="mb-6 sm:mb-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl sm:text-2xl font-bold text-[#0A1F44]">Ваш скоринг</h1>
                            <p class="text-gray-500 text-sm mt-0.5">Вероятность одобрения визы по странам</p>
                        </div>
                        <!-- Прогресс профиля -->
                        <div class="text-right shrink-0">
                            <div class="text-xs text-gray-400 mb-1">Профиль</div>
                            <div class="flex items-center gap-2">
                                <div class="w-20 sm:w-32 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full bg-[#1BA97F] transition-all duration-500"
                                         :style="{ width: profilePercent + '%' }"></div>
                                </div>
                                <span class="text-sm font-bold text-[#0A1F44]">{{ profilePercent }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- На мобильном — вертикально; на md+ — сетка 1/3 + 2/3 -->
                <div class="flex flex-col md:grid md:grid-cols-3 gap-5 sm:gap-6">

                    <!-- Список стран (на мобильном — первым сверху, на десктопе — правая колонка) -->
                    <div class="md:col-span-2 md:order-2 space-y-4">

                        <!-- Детальный скоринг выбранной страны -->
                        <div v-if="activeScore" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6">
                            <div class="flex items-center justify-between mb-5 sm:mb-6">
                                <div>
                                    <div class="text-xs text-gray-400 mb-1">Выбрана страна</div>
                                    <div class="font-bold text-[#0A1F44] text-lg">
                                        {{ countryFlag(activeScore.country_code) }}
                                        {{ countryName(activeScore.country_code) }}
                                    </div>
                                </div>
                                <!-- Круговой скор -->
                                <div class="relative flex items-center justify-center">
                                    <svg class="w-20 h-20 sm:w-24 sm:h-24 -rotate-90" viewBox="0 0 80 80">
                                        <circle cx="40" cy="40" r="32" fill="none" stroke="#f1f5f9" stroke-width="7"/>
                                        <circle cx="40" cy="40" r="32" fill="none"
                                                :stroke="scoreColor(activeScore.score)" stroke-width="7"
                                                stroke-linecap="round"
                                                :stroke-dasharray="`${activeScore.score * 2.01} 201`"
                                                class="transition-all duration-700"/>
                                    </svg>
                                    <div class="absolute text-center">
                                        <div class="text-lg sm:text-xl font-bold text-[#0A1F44]">{{ activeScore.score }}%</div>
                                        <div class="text-[9px] sm:text-[10px]" :style="{ color: scoreColor(activeScore.score) }">
                                            {{ activeScore.label }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Разбивка -->
                            <div class="space-y-3 mb-5 sm:mb-6">
                                <div v-for="(val, key) in activeScore.breakdown" :key="key"
                                    class="flex items-center gap-2 sm:gap-3">
                                    <span class="text-xs text-gray-400 w-24 sm:w-28 shrink-0">{{ breakdownLabel(key) }}</span>
                                    <div class="flex-1 bg-gray-100 rounded-full h-2.5">
                                        <div class="h-2.5 rounded-full transition-all duration-700"
                                             :class="val >= 60 ? 'bg-[#1BA97F]' : val >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                             :style="{ width: val + '%' }"></div>
                                    </div>
                                    <span class="text-xs font-bold text-gray-600 w-7 text-right">{{ val }}</span>
                                </div>
                            </div>

                            <!-- Рекомендации -->
                            <div v-if="activeScore.recommendations?.length"
                                class="p-4 bg-amber-50 rounded-xl space-y-2">
                                <div class="text-xs font-semibold text-amber-700 mb-2">Чтобы повысить шансы:</div>
                                <div v-for="rec in activeScore.recommendations" :key="rec"
                                    class="flex items-start gap-2 text-sm text-amber-800">
                                    <span class="shrink-0 mt-px">→</span>
                                    <span>{{ rec }}</span>
                                </div>
                            </div>

                            <!-- Найти агентство -->
                            <button @click="goToAgencies(activeScore)"
                                class="mt-4 w-full py-3.5 bg-[#1BA97F] text-white font-semibold rounded-xl
                                      hover:bg-[#17956f] active:scale-[0.98] transition-all">
                                Найти агентство для {{ countryName(activeScore.country_code) }}
                            </button>
                        </div>

                        <!-- Список всех стран -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="px-5 sm:px-6 py-4 border-b border-gray-50 flex items-center justify-between">
                                <h3 class="font-bold text-[#0A1F44]">Все страны</h3>
                                <span class="text-xs text-gray-400">{{ scores.length }} стран</span>
                            </div>

                            <div v-if="scoringLoading" class="p-5 sm:p-6 space-y-3">
                                <div v-for="i in 8" :key="i"
                                    class="h-14 bg-gray-50 rounded-xl animate-pulse"></div>
                            </div>

                            <div v-else class="divide-y divide-gray-50">
                                <button
                                    v-for="s in scores"
                                    :key="s.country_code"
                                    @click="selectCountry(s)"
                                    class="w-full px-4 sm:px-6 py-4 flex items-center gap-3 sm:gap-4
                                           hover:bg-slate-50 active:bg-slate-100 transition-colors text-left"
                                    :class="{ 'bg-[#1BA97F]/5': activeScore?.country_code === s.country_code }">
                                    <span class="text-xl sm:text-2xl">{{ countryFlag(s.country_code) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-[#0A1F44] text-sm truncate">
                                            {{ countryName(s.country_code) }}
                                        </div>
                                    </div>
                                    <!-- Мини бар -->
                                    <div class="w-16 sm:w-24 bg-gray-100 rounded-full h-2 shrink-0">
                                        <div class="h-2 rounded-full transition-all duration-700"
                                             :class="s.score >= 60 ? 'bg-[#1BA97F]' : s.score >= 40 ? 'bg-amber-400' : 'bg-red-400'"
                                             :style="{ width: s.score + '%' }"></div>
                                    </div>
                                    <div class="w-10 sm:w-12 text-right shrink-0">
                                        <span class="text-sm font-bold"
                                              :class="s.score >= 60 ? 'text-[#1BA97F]' : s.score >= 40 ? 'text-amber-500' : 'text-red-500'">
                                            {{ s.score }}%
                                        </span>
                                    </div>
                                    <span class="text-gray-300 text-sm hidden sm:block">›</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Профиль (на мобильном — сворачивается; на десктопе — левая колонка, sticky) -->
                    <div class="md:col-span-1 md:order-1">
                        <!-- Мобильный аккордеон -->
                        <div class="md:hidden">
                            <button @click="profileOpen = !profileOpen"
                                class="w-full bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4
                                       flex items-center justify-between text-left">
                                <span class="font-bold text-[#0A1F44] text-sm">Настроить профиль</span>
                                <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                     :class="profileOpen ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div v-if="profileOpen"
                                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 mt-2">
                                <ProfileForm
                                    v-model:profile="profile"
                                    :loading="scoringLoading"
                                    @update="updateProfile"
                                    @recalculate="loadScores"
                                />
                            </div>
                        </div>

                        <!-- Десктоп — sticky -->
                        <div class="hidden md:block bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sticky top-24">
                            <h2 class="font-bold text-[#0A1F44] mb-4">Ваш профиль</h2>
                            <ProfileForm
                                v-model:profile="profile"
                                :loading="scoringLoading"
                                @update="updateProfile"
                                @recalculate="loadScores"
                            />
                        </div>
                    </div>
                </div>
            </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import LandingLayout from '@/layouts/LandingLayout.vue';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { countryName as getCountryName, codeToFlag } from '@/utils/countries';

// Инлайн компонент формы профиля
const ProfileForm = {
    name: 'ProfileForm',
    props: ['profile', 'loading'],
    emits: ['update:profile', 'update', 'recalculate'],
    template: `
        <div class="space-y-3">
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Занятость</label>
                <select :value="profile.employment_type"
                    @change="$emit('update:profile', {...profile, employment_type: $event.target.value}); $emit('update')"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">Не указано</option>
                    <option value="employed">Наёмный сотрудник</option>
                    <option value="business_owner">Владелец бизнеса</option>
                    <option value="self_employed">Самозанятый / ИП</option>
                    <option value="retired">Пенсионер</option>
                    <option value="student">Студент</option>
                    <option value="unemployed">Безработный</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Доход в месяц ($)</label>
                <select :value="profile.monthly_income_usd"
                    @change="$emit('update:profile', {...profile, monthly_income_usd: Number($event.target.value)||''}); $emit('update')"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">Не указано</option>
                    <option :value="300">До $500</option>
                    <option :value="800">$500–1 000</option>
                    <option :value="1500">$1 000–2 000</option>
                    <option :value="3000">$2 000–4 000</option>
                    <option :value="5000">Более $4 000</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Семейное положение</label>
                <select :value="profile.marital_status"
                    @change="$emit('update:profile', {...profile, marital_status: $event.target.value}); $emit('update')"
                    class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-[#0A1F44] outline-none focus:border-[#1BA97F]">
                    <option value="">Не указано</option>
                    <option value="married">Женат / замужем</option>
                    <option value="single">Холост / незамужем</option>
                    <option value="divorced">Разведён/а</option>
                    <option value="widowed">Вдовец/вдова</option>
                </select>
            </div>
            <div class="pt-2 space-y-2">
                <label v-for="cb in checkboxes" :key="cb.key"
                    class="flex items-center gap-3 cursor-pointer group py-0.5">
                    <div class="relative shrink-0">
                        <input type="checkbox" :checked="profile[cb.key]"
                            @change="$emit('update:profile', {...profile, [cb.key]: $event.target.checked}); $emit('update')"
                            class="sr-only peer" />
                        <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-[#1BA97F]
                                    peer-checked:border-[#1BA97F] transition-colors"></div>
                        <div class="absolute inset-0 flex items-center justify-center
                                    text-white text-xs font-bold opacity-0 peer-checked:opacity-100">✓</div>
                    </div>
                    <span class="text-sm text-gray-600 group-hover:text-gray-900 leading-tight">{{ cb.label }}</span>
                </label>
            </div>
            <button @click="$emit('recalculate')" :disabled="loading"
                class="mt-3 w-full py-3 bg-[#0A1F44] text-white text-sm font-semibold
                       rounded-xl hover:bg-[#0d2a5e] active:scale-[0.98] transition-all disabled:opacity-60">
                {{ loading ? 'Считаем...' : 'Пересчитать' }}
            </button>
        </div>
    `,
    setup() {
        const checkboxes = [
            { key: 'has_children',      label: 'Есть дети (живут дома)' },
            { key: 'has_property',      label: 'Есть недвижимость' },
            { key: 'has_car',           label: 'Есть автомобиль' },
            { key: 'has_schengen_visa', label: 'Шенгенская виза' },
            { key: 'has_us_visa',       label: 'Виза США' },
            { key: 'had_visa_refusal',  label: 'Был отказ в визе' },
        ];
        return { checkboxes };
    },
};

const route          = useRoute();
const router         = useRouter();
const publicAuth     = usePublicAuthStore();
const scores         = ref([]);
const activeScore    = ref(null);
const scoringLoading = ref(false);
const profileOpen    = ref(false);

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

// Динамический маппинг: после загрузки стран из API заполняется automatически
const countryMapDynamic = ref({});

const breakdownLabels = {
    finance: 'Финансы',
    ties:    'Привязанность',
    travel:  'История виз',
    profile: 'Профиль',
};

function countryName(code)   { return countryMapDynamic.value[code]?.name ?? getCountryName(code) ?? code; }
function countryFlag(code)   { return countryMapDynamic.value[code]?.flag ?? codeToFlag(code); }
function breakdownLabel(key) { return breakdownLabels[key]   ?? key; }
function scoreColor(score)   { return score >= 60 ? '#1BA97F' : score >= 40 ? '#f59e0b' : '#ef4444'; }

function goToAgencies(score) {
    router.push({ name: 'me.agencies', query: { country_code: score.country_code } });
}

function selectCountry(s) {
    activeScore.value = s;
    // На мобильном прокрутить к результату
    if (window.innerWidth < 768) {
        setTimeout(() => {
            document.querySelector('[data-scoring-result]')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 50);
    }
}

async function updateProfile() {
    try {
        await publicPortalApi.updateProfile(profile.value);
        await publicAuth.fetchMe();
    } catch { /* тихо */ }
}

async function loadScores() {
    scoringLoading.value = true;
    try {
        const [scoresRes, countriesRes] = await Promise.all([
            publicPortalApi.scoreAll(),
            publicPortalApi.countries().catch(() => null),
        ]);
        // Заполняем маппинг стран из API
        if (countriesRes?.data?.data) {
            const map = {};
            countriesRes.data.data.forEach(c => {
                map[c.code] = { name: c.name, flag: c.flag ?? codeToFlag(c.code) };
            });
            countryMapDynamic.value = map;
        }
        scores.value = scoresRes.data.data.scores;
        const preselect = route.query.country?.toUpperCase();
        activeScore.value = scores.value.find(s => s.country_code === preselect) ?? scores.value[0];
    } finally {
        scoringLoading.value = false;
    }
}

onMounted(loadScores);
</script>

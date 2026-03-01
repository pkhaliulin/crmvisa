<template>
    <div class="max-w-2xl mx-auto space-y-4">

        <!-- Назад -->
        <button @click="router.push({ name: 'me.cases' })"
            class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Мои заявки
        </button>

        <!-- Скелетон загрузки -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-48 mb-3"></div>
                <div class="h-4 bg-gray-50 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else-if="caseData">

            <!-- === Заголовок заявки === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ codeToFlag(caseData.country_code) }}</span>
                        <div>
                            <h1 class="text-lg font-bold text-[#0A1F44] leading-tight">
                                {{ countryName(caseData.country_code) }}
                            </h1>
                            <div class="text-sm text-gray-400 mt-0.5">{{ visaTypeLabel(caseData.visa_type) }}</div>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full"
                        :class="stageBadge(caseData.stage)">
                        {{ caseData.stage_label }}
                    </span>
                </div>

                <!-- Прогресс -->
                <div class="mb-3">
                    <div class="flex items-center gap-0.5 mb-2">
                        <div v-for="(s, i) in STAGES" :key="s.key"
                            class="flex-1 h-2 rounded-full transition-colors"
                            :class="i < caseData.stage_order ? 'bg-[#1BA97F]' : i === caseData.stage_order ? 'bg-[#1BA97F]/50' : 'bg-gray-100'">
                        </div>
                    </div>
                    <p class="text-sm text-[#1BA97F] font-medium">{{ caseData.stage_msg }}</p>
                </div>

                <!-- Даты -->
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div v-if="caseData.critical_date" class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">Дедлайн</div>
                        <div class="text-sm font-semibold" :class="deadlineClass(caseData.critical_date)">
                            {{ formatDate(caseData.critical_date) }}
                        </div>
                    </div>
                    <div v-if="caseData.travel_date" class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">Дата поездки</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ formatDate(caseData.travel_date) }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">Создана</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ formatDate(caseData.created_at) }}</div>
                    </div>
                </div>

                <!-- Заметки от агентства -->
                <div v-if="caseData.notes" class="mt-4 p-3 bg-blue-50 rounded-xl">
                    <div class="text-xs font-semibold text-blue-700 mb-1">Комментарий агентства</div>
                    <p class="text-sm text-blue-800 leading-relaxed whitespace-pre-line">{{ caseData.notes }}</p>
                </div>
            </div>

            <!-- === Чек-лист документов === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-[#0A1F44] text-sm">Документы</h2>
                        <p class="text-xs text-gray-400 mt-0.5">Что нужно подготовить для визы</p>
                    </div>
                    <div v-if="checklist.length" class="text-right">
                        <div class="text-lg font-bold"
                            :class="docsUploaded >= checklist.length ? 'text-[#1BA97F]' : 'text-[#0A1F44]'">
                            {{ docsUploaded }}/{{ checklist.length }}
                        </div>
                        <div class="text-xs text-gray-400">загружено</div>
                    </div>
                </div>

                <!-- Прогресс документов -->
                <div v-if="checklist.length" class="px-5 pt-3 pb-1">
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                            :class="docsUploaded >= checklist.length ? 'bg-[#1BA97F]' : 'bg-amber-400'"
                            :style="{ width: (docsUploaded / checklist.length * 100) + '%' }">
                        </div>
                    </div>
                </div>

                <!-- Список документов -->
                <div v-if="checklist.length" class="divide-y divide-gray-50 mt-1">
                    <div v-for="item in checklist" :key="item.id"
                        class="px-5 py-3.5 flex items-start gap-3">
                        <!-- Статус иконка -->
                        <div class="mt-0.5 shrink-0 w-5 h-5 rounded-full flex items-center justify-center"
                            :class="{
                                'bg-[#1BA97F]':   item.status === 'approved',
                                'bg-blue-100':    item.status === 'uploaded',
                                'bg-amber-100':   item.status === 'pending' && item.is_required,
                                'bg-gray-100':    item.status === 'pending' && !item.is_required,
                            }">
                            <!-- Одобрено -->
                            <svg v-if="item.status === 'approved'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <!-- Загружено, на проверке -->
                            <svg v-else-if="item.status === 'uploaded'" class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <!-- Ожидает -->
                            <svg v-else class="w-3 h-3" :class="item.is_required ? 'text-amber-600' : 'text-gray-400'"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-[#0A1F44]">{{ item.name }}</span>
                                <span v-if="item.is_required"
                                    class="text-[10px] font-semibold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full shrink-0">
                                    Обязательно
                                </span>
                            </div>
                            <p v-if="item.description" class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ item.description }}</p>
                            <p v-if="item.notes" class="text-xs text-blue-600 mt-0.5">{{ item.notes }}</p>

                            <!-- Статус текст -->
                            <div class="mt-1 text-xs font-medium"
                                :class="{
                                    'text-[#1BA97F]': item.status === 'approved',
                                    'text-blue-600':  item.status === 'uploaded',
                                    'text-amber-600': item.status === 'pending' && item.is_required,
                                    'text-gray-400':  item.status === 'pending' && !item.is_required,
                                }">
                                {{ statusLabel(item.status, item.is_required) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Нет документов -->
                <div v-else class="px-5 py-6 text-center">
                    <div class="text-sm text-gray-400">Список документов формируется агентством</div>
                </div>
            </div>

            <!-- === Агентство === -->
            <div v-if="caseData.agency" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-[#0A1F44] text-sm">Агентство</h2>
                </div>
                <div class="p-5">
                    <div class="flex items-start gap-4 mb-4">
                        <!-- Логотип -->
                        <div class="w-14 h-14 rounded-xl border border-gray-100 flex items-center justify-center bg-gray-50 shrink-0 overflow-hidden">
                            <img v-if="caseData.agency.logo_url" :src="caseData.agency.logo_url" :alt="caseData.agency.name"
                                class="w-full h-full object-cover">
                            <span v-else class="text-xl font-bold text-gray-300">
                                {{ caseData.agency.name?.[0]?.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-[#0A1F44]">{{ caseData.agency.name }}</span>
                                <span v-if="caseData.agency.is_verified"
                                    class="flex items-center gap-1 text-xs text-[#1BA97F] bg-[#1BA97F]/10 px-2 py-0.5 rounded-full shrink-0">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Верифицировано
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                                <span v-if="caseData.agency.city">{{ caseData.agency.city }}</span>
                                <span v-if="caseData.agency.experience_years">{{ caseData.agency.experience_years }} лет</span>
                                <span v-if="caseData.agency.rating" class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ Number(caseData.agency.rating).toFixed(1) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <p v-if="caseData.agency.description" class="text-sm text-gray-500 leading-relaxed mb-4">
                        {{ caseData.agency.description }}
                    </p>

                    <!-- Контакты агентства -->
                    <div class="space-y-2">
                        <a v-if="caseData.agency.phone"
                            :href="`tel:${caseData.agency.phone}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Телефон</div>
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.agency.phone }}</div>
                            </div>
                        </a>

                        <a v-if="caseData.agency.email"
                            :href="`mailto:${caseData.agency.email}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Email</div>
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.agency.email }}</div>
                            </div>
                        </a>

                        <div v-if="caseData.agency.address"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">Адрес</div>
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.agency.address }}</div>
                            </div>
                        </div>

                        <a v-if="caseData.agency.website_url"
                            :href="caseData.agency.website_url" target="_blank" rel="noopener"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-400">Сайт</div>
                                <div class="text-sm font-semibold text-[#0A1F44] truncate">{{ caseData.agency.website_url }}</div>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- === Менеджер === -->
            <div v-if="caseData.assignee" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-[#0A1F44] text-sm">Ваш менеджер</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Специалист, который ведёт вашу заявку</p>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white font-bold text-lg shrink-0">
                            {{ caseData.assignee.name?.[0]?.toUpperCase() ?? '?' }}
                        </div>
                        <div>
                            <div class="font-bold text-[#0A1F44]">{{ caseData.assignee.name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">Визовый специалист</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <a v-if="caseData.assignee.phone"
                            :href="`tel:${caseData.assignee.phone}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-[#1BA97F]/5 hover:bg-[#1BA97F]/10 border border-[#1BA97F]/20 transition-colors">
                            <svg class="w-4 h-4 text-[#1BA97F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="text-sm font-semibold text-[#1BA97F]">{{ caseData.assignee.phone }}</span>
                        </a>
                        <a v-if="caseData.assignee.email"
                            :href="`mailto:${caseData.assignee.email}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm text-gray-700">{{ caseData.assignee.email }}</span>
                        </a>
                        <div v-if="!caseData.assignee.phone && !caseData.assignee.email"
                            class="text-sm text-gray-400 p-3">
                            Менеджер скоро свяжется с вами
                        </div>
                    </div>
                </div>
            </div>

            <!-- === Без менеджера === -->
            <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#0A1F44]">Менеджер ещё не назначен</div>
                        <p class="text-xs text-gray-400 mt-0.5">Агентство назначит специалиста в ближайшее время</p>
                    </div>
                </div>
            </div>

        </template>

        <!-- Ошибка / не найдено -->
        <div v-else-if="!loading" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="text-3xl mb-3">404</div>
            <div class="font-semibold text-[#0A1F44]">Заявка не найдена</div>
            <button @click="router.push({ name: 'me.cases' })"
                class="mt-4 px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] transition-colors">
                Вернуться к списку
            </button>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';

const route  = useRoute();
const router = useRouter();

const loading  = ref(true);
const caseData = ref(null);

const STAGES = [
    { key: 'lead' }, { key: 'qualification' }, { key: 'documents' },
    { key: 'translation' }, { key: 'appointment' }, { key: 'review' }, { key: 'result' },
];

const COUNTRY_NAMES = {
    DE: 'Германия', FR: 'Франция', IT: 'Италия', ES: 'Испания',
    GB: 'Великобритания', US: 'США', CA: 'Канада', AU: 'Австралия',
    JP: 'Япония', KR: 'Южная Корея', CN: 'Китай', AE: 'ОАЭ',
    TR: 'Турция', PL: 'Польша', CZ: 'Чехия', HU: 'Венгрия',
    AT: 'Австрия', CH: 'Швейцария', NL: 'Нидерланды', PT: 'Португалия',
    GR: 'Греция', SA: 'Саудовская Аравия', IN: 'Индия', TH: 'Таиланд',
    MY: 'Малайзия', SG: 'Сингапур', ID: 'Индонезия',
};

const VISA_TYPE_LABELS = {
    tourist: 'Туристическая виза', business: 'Бизнес-виза',
    student: 'Студенческая виза', work: 'Рабочая виза',
    transit: 'Транзитная виза', immigrant: 'Иммиграционная виза',
};

const checklist = computed(() => caseData.value?.checklist ?? []);
const docsUploaded = computed(() =>
    checklist.value.filter(i => i.status === 'uploaded' || i.status === 'approved').length
);

function countryName(code)    { return COUNTRY_NAMES[code] || code; }
function codeToFlagLocal(code){ return codeToFlag(code); }
function visaTypeLabel(type)  { return VISA_TYPE_LABELS[type] || type; }

function stageBadge(stage) {
    const map = {
        lead:          'bg-gray-100 text-gray-600',
        qualification: 'bg-blue-50 text-blue-600',
        documents:     'bg-amber-50 text-amber-700',
        translation:   'bg-purple-50 text-purple-700',
        appointment:   'bg-indigo-50 text-indigo-700',
        review:        'bg-orange-50 text-orange-700',
        result:        'bg-green-50 text-green-700',
    };
    return map[stage] || 'bg-gray-100 text-gray-600';
}

function statusLabel(status, required) {
    if (status === 'approved') return 'Одобрено';
    if (status === 'uploaded') return 'Загружено — на проверке';
    return required ? 'Необходимо загрузить' : 'По желанию';
}

function deadlineClass(dateStr) {
    if (!dateStr) return 'text-gray-600';
    const days = Math.floor((new Date(dateStr) - new Date()) / 86400000);
    if (days < 0)  return 'text-red-600';
    if (days <= 5) return 'text-amber-600';
    return 'text-[#0A1F44]';
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });
}

onMounted(async () => {
    try {
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
    } catch {
        caseData.value = null;
    } finally {
        loading.value = false;
    }
});
</script>

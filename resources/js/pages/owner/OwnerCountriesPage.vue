<template>
    <div class="space-y-5">

        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Страны и типы виз для CRM. Веса используются в публичном скоринге.</p>
            <button @click="openAdd = true"
                class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl
                       hover:bg-[#0d2a5e] transition-colors">
                + Добавить страну
            </button>
        </div>

        <!-- Типы виз -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700">Типы виз</h3>
                <button @click="openAddVisa = true"
                    class="text-xs px-3 py-1.5 bg-[#0A1F44] text-white rounded-lg hover:bg-[#0d2a5e] transition-colors">
                    + Добавить тип
                </button>
            </div>
            <div v-if="loadingVisa" class="text-sm text-gray-400">Загрузка...</div>
            <div v-else class="flex flex-wrap gap-2">
                <div v-for="vt in visaTypes" :key="vt.slug"
                    class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-sm"
                    :class="vt.is_active ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-gray-200 bg-gray-50 text-gray-400'">
                    <span>{{ vt.name_ru }}</span>
                    <span class="text-xs font-mono opacity-60">{{ vt.slug }}</span>
                    <button @click="toggleVisa(vt)"
                        class="ml-1 text-xs opacity-50 hover:opacity-100 transition-opacity">
                        {{ vt.is_active ? 'Откл.' : 'Вкл.' }}
                    </button>
                    <button @click="editingVisa = vt; visaForm = { name_ru: vt.name_ru }"
                        class="text-xs opacity-50 hover:opacity-100 transition-opacity">
                        ✎
                    </button>
                </div>
                <div v-if="!visaTypes.length" class="text-sm text-gray-400">Типов виз нет</div>
            </div>
        </div>

        <!-- Таблица стран -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">Страна</th>
                        <th class="px-3 py-3 text-left">Типы виз</th>
                        <th class="px-3 py-3 text-center">Финансы</th>
                        <th class="px-3 py-3 text-center">Привяз.</th>
                        <th class="px-3 py-3 text-center">История</th>
                        <th class="px-3 py-3 text-center">Профиль</th>
                        <th class="px-3 py-3 text-center">Мин. доход</th>
                        <th class="px-3 py-3 text-center">Порог</th>
                        <th class="px-3 py-3 text-center">Статус</th>
                        <th class="px-3 py-3 text-center">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 5" :key="i">
                        <td colspan="10" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-full"></div>
                        </td>
                    </tr>
                    <template v-else v-for="c in countries" :key="c.country_code">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ c.flag_emoji }}</span>
                                    <div>
                                        <div class="font-medium text-gray-800">{{ c.name }}</div>
                                        <div class="text-xs text-gray-400">{{ c.country_code }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="vt in (c.visa_types || [])" :key="vt"
                                        class="text-xs px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded">
                                        {{ visaTypeName(vt) }}
                                    </span>
                                    <span v-if="!(c.visa_types?.length)" class="text-xs text-gray-300">—</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center"><WeightBadge :v="c.weight_finance" /></td>
                            <td class="px-3 py-3 text-center"><WeightBadge :v="c.weight_ties" /></td>
                            <td class="px-3 py-3 text-center"><WeightBadge :v="c.weight_travel" /></td>
                            <td class="px-3 py-3 text-center"><WeightBadge :v="c.weight_profile" /></td>
                            <td class="px-3 py-3 text-center text-gray-600">${{ c.min_monthly_income_usd }}</td>
                            <td class="px-3 py-3 text-center text-gray-600">{{ c.min_score }}%</td>
                            <td class="px-3 py-3 text-center">
                                <button @click="toggleActive(c)"
                                    class="text-xs px-2 py-1 rounded-full font-medium"
                                    :class="c.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    {{ c.is_active ? 'Активна' : 'Откл.' }}
                                </button>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="flex gap-1.5 justify-center">
                                    <RouterLink :to="{ name: 'owner.country.detail', params: { code: c.country_code } }"
                                        class="text-xs px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg
                                               hover:bg-blue-100 transition-colors font-medium">
                                        Подробнее
                                    </RouterLink>
                                    <button @click="openEdit(c)"
                                        class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg
                                               hover:bg-gray-50 text-gray-600 transition-colors">
                                        Изменить
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-100">
                    <tr>
                        <td class="px-5 py-2 text-xs text-gray-400">Итого: {{ countries.length }} стран</td>
                        <td colspan="9"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Модалка редактирования страны -->
        <div v-if="editingCountry" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">
                    {{ editingCountry.flag_emoji }} {{ editingCountry.name }}
                </h3>

                <!-- Типы виз -->
                <div class="mb-4">
                    <label class="text-xs text-gray-500 mb-2 block">Типы виз для этой страны</label>
                    <div class="flex flex-wrap gap-2">
                        <label v-for="vt in visaTypes.filter(v => v.is_active)" :key="vt.slug"
                            class="flex items-center gap-1.5 text-sm cursor-pointer">
                            <input type="checkbox" :value="vt.slug" v-model="editForm.visa_types"
                                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
                            {{ vt.name_ru }}
                        </label>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div v-for="f in weightFields" :key="f.key">
                            <label class="text-xs text-gray-500 mb-1 block">{{ f.label }}</label>
                            <input v-model.number="editForm[f.key]" type="number" min="0" max="1" step="0.05"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">
                        Сумма весов: <strong :class="weightsSum !== 1 ? 'text-red-500' : 'text-green-600'">
                            {{ weightsSum.toFixed(2) }}
                        </strong> (должна быть = 1.00)
                    </p>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Мин. доход ($)</label>
                        <input v-model.number="editForm.min_monthly_income_usd" type="number"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Минимальный порог скоринга (%)</label>
                        <input v-model.number="editForm.min_score" type="number" min="0" max="100"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>
                </div>

                <!-- Раздел: Информация о посольстве -->
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <h4 class="text-sm font-semibold text-[#0A1F44] mb-3">Информация о посольстве</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Сайт посольства / визового центра</label>
                            <input v-model="editForm.embassy_website" type="url" placeholder="https://"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Ссылка для записи на приём</label>
                            <input v-model="editForm.appointment_url" type="url" placeholder="https://"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Описание: требования, особенности</label>
                            <textarea v-model="editForm.embassy_description" rows="3" placeholder="Опишите требования посольства..."
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Правила подачи документов</label>
                            <textarea v-model="editForm.embassy_rules" rows="3" placeholder="Укажите правила подачи..."
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Раздел: Сроки обработки -->
                <div class="mt-5 pt-5 border-t border-gray-100">
                    <h4 class="text-sm font-semibold text-[#0A1F44] mb-3">Сроки обработки (дни)</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Стандартный срок рассмотрения</label>
                            <input v-model.number="editForm.processing_days_standard" type="number" min="0" placeholder="15"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Ускоренный срок</label>
                            <input v-model.number="editForm.processing_days_expedited" type="number" min="0" placeholder="7"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Среднее время ожидания записи</label>
                            <input v-model.number="editForm.appointment_wait_days" type="number" min="0" placeholder="10"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Рекомендуемый буфер (запас дней)</label>
                            <input v-model.number="editForm.buffer_days_recommended" type="number" min="0" placeholder="5"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                    </div>

                    <!-- Расчётная формула -->
                    <div v-if="totalRecommendedDays !== null"
                        class="mt-3 flex items-center gap-2 bg-blue-50 text-blue-800 rounded-xl px-4 py-3 text-sm">
                        <svg class="w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
                        </svg>
                        <span>Итого: рекомендуем подавать за <strong>{{ totalRecommendedDays }} дней</strong> до поездки</span>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="saveEdit" :disabled="saving || weightsSum !== 1"
                        class="flex-1 py-3 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] transition-colors disabled:opacity-60">
                        {{ saving ? 'Сохраняем...' : 'Сохранить' }}
                    </button>
                    <button @click="editingCountry = null"
                        class="px-5 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
                        Отмена
                    </button>
                </div>
            </div>
        </div>

        <!-- Модалка добавления страны -->
        <div v-if="openAdd" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">Добавить страну</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Код (ISO-2) *</label>
                            <input v-model="addForm.country_code" maxlength="2" placeholder="DE"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] uppercase"
                                @input="addForm.country_code = addForm.country_code.toUpperCase()" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">Флаг (эмодзи)</label>
                            <input v-model="addForm.flag_emoji" placeholder="🇩🇪"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Название *</label>
                        <input v-model="addForm.name" placeholder="Германия"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-2 block">Типы виз</label>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="vt in visaTypes.filter(v => v.is_active)" :key="vt.slug"
                                class="flex items-center gap-1.5 text-sm cursor-pointer">
                                <input type="checkbox" :value="vt.slug" v-model="addForm.visa_types"
                                    class="w-4 h-4 text-blue-600 rounded border-gray-300" />
                                {{ vt.name_ru }}
                            </label>
                        </div>
                    </div>
                    <p v-if="addError" class="text-xs text-red-600">{{ addError }}</p>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="addCountry" :disabled="saving"
                        class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
                        Добавить
                    </button>
                    <button @click="openAdd = false; addError = ''"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
                        Отмена
                    </button>
                </div>
            </div>
        </div>

        <!-- Модалка добавления типа визы -->
        <div v-if="openAddVisa" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">Новый тип визы</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Slug (латиница, без пробелов) *</label>
                        <input v-model="visaForm.slug" placeholder="work"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Название (рус.) *</label>
                        <input v-model="visaForm.name_ru" placeholder="Рабочая виза"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                    </div>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="addVisaType" :disabled="saving"
                        class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
                        Добавить
                    </button>
                    <button @click="openAddVisa = false"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
                        Отмена
                    </button>
                </div>
            </div>
        </div>

        <!-- Модалка редактирования типа визы -->
        <div v-if="editingVisa" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">Тип визы: {{ editingVisa.slug }}</h3>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Название (рус.)</label>
                    <input v-model="visaForm.name_ru"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="saveVisaEdit" :disabled="saving"
                        class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
                        Сохранить
                    </button>
                    <button @click="editingVisa = null"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { RouterLink } from 'vue-router';
import api from '@/api/index';

const countries    = ref([]);
const loading      = ref(true);
const saving       = ref(false);
const editingCountry = ref(null);
const editForm       = ref({});
const openAdd        = ref(false);
const addError       = ref('');
const addForm        = ref({ country_code: '', name: '', flag_emoji: '', visa_types: ['tourist','student','business'] });

// Типы виз
const visaTypes    = ref([]);
const loadingVisa  = ref(true);
const openAddVisa  = ref(false);
const editingVisa  = ref(null);
const visaForm     = ref({ slug: '', name_ru: '' });

const weightFields = [
    { key: 'weight_finance', label: 'Финансы' },
    { key: 'weight_ties',    label: 'Привязанность' },
    { key: 'weight_travel',  label: 'История виз' },
    { key: 'weight_profile', label: 'Профиль' },
];

const WeightBadge = {
    name: 'WeightBadge',
    props: ['v'],
    template: `
        <span class="inline-block text-xs font-mono font-bold px-2 py-0.5 rounded"
              :class="parseFloat(v) >= 0.40 ? 'bg-blue-50 text-blue-700' : parseFloat(v) >= 0.20 ? 'bg-gray-50 text-gray-600' : 'bg-gray-50 text-gray-400'">
            {{ (parseFloat(v) * 100).toFixed(0) }}%
        </span>
    `,
};

const weightsSum = computed(() => {
    const f = editForm.value;
    return +(( (f.weight_finance ?? 0) + (f.weight_ties ?? 0) + (f.weight_travel ?? 0) + (f.weight_profile ?? 0) ).toFixed(2));
});

const totalRecommendedDays = computed(() => {
    const f = editForm.value;
    const standard = f.processing_days_standard;
    const wait     = f.appointment_wait_days;
    const buffer   = f.buffer_days_recommended;
    if (standard != null && wait != null && buffer != null &&
        standard !== '' && wait !== '' && buffer !== '') {
        return (Number(standard) + Number(wait) + Number(buffer)) || null;
    }
    return null;
});

function visaTypeName(slug) {
    return visaTypes.value.find(v => v.slug === slug)?.name_ru ?? slug;
}

// ── Страны ───────────────────────────────────────────────────────────────────
async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/countries');
        countries.value = data.data;
    } finally {
        loading.value = false;
    }
}

function openEdit(c) {
    editingCountry.value = c;
    editForm.value = {
        visa_types:                 [...(c.visa_types || [])],
        weight_finance:             parseFloat(c.weight_finance) || 0,
        weight_ties:                parseFloat(c.weight_ties) || 0,
        weight_travel:              parseFloat(c.weight_travel) || 0,
        weight_profile:             parseFloat(c.weight_profile) || 0,
        min_monthly_income_usd:     c.min_monthly_income_usd,
        min_score:                  c.min_score,
        // Посольство
        embassy_website:            c.embassy_website ?? '',
        appointment_url:            c.appointment_url ?? '',
        embassy_description:        c.embassy_description ?? '',
        embassy_rules:              c.embassy_rules ?? '',
        // Сроки
        processing_days_standard:   c.processing_days_standard ?? null,
        processing_days_expedited:  c.processing_days_expedited ?? null,
        appointment_wait_days:      c.appointment_wait_days ?? null,
        buffer_days_recommended:    c.buffer_days_recommended ?? null,
    };
}

async function saveEdit() {
    saving.value = true;
    try {
        await api.patch(`/owner/countries/${editingCountry.value.country_code}`, editForm.value);
        editingCountry.value = null;
        load();
    } finally {
        saving.value = false;
    }
}

async function toggleActive(c) {
    await api.patch(`/owner/countries/${c.country_code}`, { is_active: !c.is_active });
    c.is_active = !c.is_active;
}

async function addCountry() {
    addError.value = '';
    if (!addForm.value.country_code || addForm.value.country_code.length !== 2) {
        addError.value = 'Код должен быть 2 буквы'; return;
    }
    if (!addForm.value.name) {
        addError.value = 'Введите название'; return;
    }
    saving.value = true;
    try {
        await api.post('/owner/countries', addForm.value);
        openAdd.value = false;
        addForm.value = { country_code: '', name: '', flag_emoji: '', visa_types: ['tourist','student','business'] };
        load();
    } catch (err) {
        addError.value = err.response?.data?.message ?? 'Ошибка';
    } finally {
        saving.value = false;
    }
}

// ── Типы виз ─────────────────────────────────────────────────────────────────
async function loadVisaTypes() {
    loadingVisa.value = true;
    try {
        const { data } = await api.get('/owner/visa-types');
        visaTypes.value = data.data;
    } finally {
        loadingVisa.value = false;
    }
}

async function addVisaType() {
    saving.value = true;
    try {
        await api.post('/owner/visa-types', visaForm.value);
        openAddVisa.value = false;
        visaForm.value = { slug: '', name_ru: '' };
        loadVisaTypes();
    } finally {
        saving.value = false;
    }
}

async function saveVisaEdit() {
    saving.value = true;
    try {
        await api.patch(`/owner/visa-types/${editingVisa.value.slug}`, { name_ru: visaForm.value.name_ru });
        editingVisa.value = null;
        loadVisaTypes();
    } finally {
        saving.value = false;
    }
}

async function toggleVisa(vt) {
    await api.patch(`/owner/visa-types/${vt.slug}`, { is_active: !vt.is_active });
    vt.is_active = !vt.is_active;
}

onMounted(() => {
    load();
    loadVisaTypes();
});
</script>

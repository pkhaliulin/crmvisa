<template>
    <div class="space-y-5">

        <div class="flex items-center justify-between">
            <p class="text-sm text-gray-500">Веса используются в публичном скоринге. Изменения применяются сразу.</p>
            <button @click="openAdd = true"
                class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl
                       hover:bg-[#0d2a5e] transition-colors">
                + Добавить страну
            </button>
        </div>

        <!-- Таблица стран -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">Страна</th>
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
                        <td colspan="9" class="px-5 py-4">
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
                            <td class="px-3 py-3 text-center">
                                <WeightBadge :v="c.weight_finance" />
                            </td>
                            <td class="px-3 py-3 text-center">
                                <WeightBadge :v="c.weight_ties" />
                            </td>
                            <td class="px-3 py-3 text-center">
                                <WeightBadge :v="c.weight_travel" />
                            </td>
                            <td class="px-3 py-3 text-center">
                                <WeightBadge :v="c.weight_profile" />
                            </td>
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
                                <button @click="openEdit(c)"
                                    class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg
                                           hover:bg-gray-50 text-gray-600 transition-colors">
                                    Изменить
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-100">
                    <tr>
                        <td class="px-5 py-2 text-xs text-gray-400">Итого: {{ countries.length }} стран</td>
                        <td colspan="8"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Модалка редактирования -->
        <div v-if="editingCountry" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">
                    {{ editingCountry.flag_emoji }} {{ editingCountry.name }}
                </h3>

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
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import api from '@/api/axios';

const countries    = ref([]);
const loading      = ref(true);
const saving       = ref(false);
const editingCountry = ref(null);
const editForm       = ref({});
const openAdd        = ref(false);

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
              :class="v >= 0.40 ? 'bg-blue-50 text-blue-700' : v >= 0.20 ? 'bg-gray-50 text-gray-600' : 'bg-gray-50 text-gray-400'">
            {{ (v * 100).toFixed(0) }}%
        </span>
    `,
};

const weightsSum = computed(() => {
    const f = editForm.value;
    return +(( (f.weight_finance ?? 0) + (f.weight_ties ?? 0) + (f.weight_travel ?? 0) + (f.weight_profile ?? 0) ).toFixed(2));
});

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
        weight_finance:         c.weight_finance,
        weight_ties:            c.weight_ties,
        weight_travel:          c.weight_travel,
        weight_profile:         c.weight_profile,
        min_monthly_income_usd: c.min_monthly_income_usd,
        min_score:              c.min_score,
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

onMounted(load);
</script>

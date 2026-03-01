<template>
    <div class="space-y-5">

        <!-- Фильтры -->
        <div class="flex flex-wrap gap-3 items-center">
            <input v-model="search" @input="debouncedLoad" placeholder="Поиск по имени / email..."
                class="border border-gray-200 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#1BA97F]
                       w-64"/>
            <select v-model="filterPlan" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">Все тарифы</option>
                <option value="trial">Trial</option>
                <option value="starter">Starter</option>
                <option value="professional">Professional</option>
                <option value="premium">Premium</option>
            </select>
            <select v-model="filterStatus" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">Все статусы</option>
                <option value="active">Активные</option>
                <option value="inactive">Заблокированные</option>
            </select>
        </div>

        <!-- Таблица -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="px-5 py-3 text-left font-medium">Агентство</th>
                        <th class="px-4 py-3 text-left font-medium">Тариф</th>
                        <th class="px-4 py-3 text-right font-medium">Лиды</th>
                        <th class="px-4 py-3 text-right font-medium">Комиссия</th>
                        <th class="px-4 py-3 text-center font-medium">Статус</th>
                        <th class="px-4 py-3 text-center font-medium">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 5" :key="i">
                        <td colspan="6" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else v-for="a in agencies" :key="a.id"
                        class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ a.name }}</div>
                            <div class="text-xs text-gray-400">{{ a.email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full font-medium"
                                :class="planClass(a.plan)">{{ a.plan }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ a.leads_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ a.commission_rate ?? 10 }}%</td>
                        <td class="px-4 py-3 text-center">
                            <span :class="a.is_active ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50'"
                                class="text-xs px-2 py-1 rounded-full font-medium">
                                {{ a.is_active ? 'Активно' : 'Заблок.' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="openEdit(a)"
                                    class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg
                                           hover:bg-gray-50 text-gray-600 transition-colors">
                                    Изменить
                                </button>
                                <button @click="toggleBlock(a)"
                                    class="text-xs px-3 py-1.5 rounded-lg transition-colors"
                                    :class="a.is_active
                                        ? 'border border-red-200 text-red-500 hover:bg-red-50'
                                        : 'border border-green-200 text-green-600 hover:bg-green-50'">
                                    {{ a.is_active ? 'Блок.' : 'Разблок.' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пагинация -->
            <div v-if="pagination.last_page > 1" class="px-5 py-3 border-t border-gray-50
                                                          flex items-center justify-between text-xs text-gray-500">
                <span>Всего: {{ pagination.total }}</span>
                <div class="flex gap-1">
                    <button v-for="p in pagination.last_page" :key="p" @click="page = p; load()"
                        class="w-7 h-7 rounded-lg"
                        :class="p === pagination.current_page
                            ? 'bg-[#0A1F44] text-white font-bold'
                            : 'hover:bg-gray-100 text-gray-600'">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Модалка редактирования -->
        <div v-if="editingAgency" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">{{ editingAgency.name }}</h3>

                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Тариф</label>
                        <select v-model="editForm.plan"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                            <option value="trial">Trial</option>
                            <option value="starter">Starter</option>
                            <option value="professional">Professional</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Комиссия (%)</label>
                        <input v-model.number="editForm.commission_rate" type="number" min="0" max="100" step="0.5"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_verified" v-model="editForm.is_verified" class="rounded"/>
                        <label for="is_verified" class="text-sm text-gray-700">Верифицировано</label>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="saveEdit" :disabled="saving"
                        class="flex-1 py-3 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] transition-colors disabled:opacity-60">
                        {{ saving ? 'Сохраняем...' : 'Сохранить' }}
                    </button>
                    <button @click="editingAgency = null"
                        class="px-5 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
                        Отмена
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/axios';

const agencies    = ref([]);
const loading     = ref(true);
const saving      = ref(false);
const search      = ref('');
const filterPlan  = ref('');
const filterStatus= ref('');
const page        = ref(1);
const pagination  = ref({ last_page: 1, current_page: 1, total: 0 });
const editingAgency = ref(null);
const editForm      = ref({});

let debounceTimer = null;
function debouncedLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { page.value = 1; load(); }, 400);
}

function planClass(plan) {
    return {
        trial:        'bg-gray-100 text-gray-600',
        starter:      'bg-blue-50 text-blue-700',
        professional: 'bg-purple-50 text-purple-700',
        premium:      'bg-amber-50 text-amber-700',
    }[plan] ?? 'bg-gray-50 text-gray-500';
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/agencies', {
            params: { search: search.value, plan: filterPlan.value, status: filterStatus.value, page: page.value },
        });
        agencies.value  = data.data.data;
        pagination.value = { last_page: data.data.last_page, current_page: data.data.current_page, total: data.data.total };
    } finally {
        loading.value = false;
    }
}

function openEdit(a) {
    editingAgency.value = a;
    editForm.value = { plan: a.plan, commission_rate: a.commission_rate ?? 10, is_verified: a.is_verified ?? false };
}

async function saveEdit() {
    saving.value = true;
    try {
        await api.patch(`/owner/agencies/${editingAgency.value.id}`, editForm.value);
        editingAgency.value = null;
        load();
    } finally {
        saving.value = false;
    }
}

async function toggleBlock(a) {
    await api.patch(`/owner/agencies/${a.id}`, { is_active: !a.is_active });
    a.is_active = !a.is_active;
}

onMounted(load);
</script>

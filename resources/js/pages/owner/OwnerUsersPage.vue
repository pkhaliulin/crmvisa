<template>
    <div class="space-y-5">

        <div class="flex gap-3">
            <input v-model="search" @input="debouncedLoad" placeholder="Поиск по телефону или имени..."
                class="border border-gray-200 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#1BA97F] w-72"/>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">Пользователь</th>
                        <th class="px-4 py-3 text-left">Занятость</th>
                        <th class="px-4 py-3 text-center">Доход</th>
                        <th class="px-4 py-3 text-center">Профиль</th>
                        <th class="px-4 py-3 text-center">Зарегистрирован</th>
                        <th class="px-4 py-3 text-center">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="6" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else v-for="u in users" :key="u.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ u.name || '—' }}</div>
                            <div class="text-xs text-gray-400">{{ u.phone }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ empLabel(u.employment_type) }}</td>
                        <td class="px-4 py-3 text-center text-gray-600 text-xs">
                            {{ u.monthly_income_usd ? '$' + u.monthly_income_usd : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-flex items-center gap-1.5">
                                <div class="w-16 bg-gray-100 rounded-full h-1.5">
                                    <div class="h-1.5 bg-[#1BA97F] rounded-full"
                                         :style="{ width: (u.profile_completeness || 0) + '%' }"></div>
                                </div>
                                <span class="text-xs text-gray-500">{{ u.profile_completeness || 0 }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-400">
                            {{ new Date(u.created_at).toLocaleDateString('ru-RU') }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="blockUser(u)"
                                class="text-xs px-3 py-1.5 border border-red-200 text-red-500
                                       rounded-lg hover:bg-red-50 transition-colors">
                                Сброс токена
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="px-5 py-3 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
                <span>Всего: {{ pagination.total }}</span>
                <div class="flex gap-1">
                    <button v-for="p in Math.min(pagination.last_page, 10)" :key="p"
                        @click="page = p; load()"
                        class="w-7 h-7 rounded-lg"
                        :class="p === pagination.current_page ? 'bg-[#0A1F44] text-white' : 'hover:bg-gray-100'">
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const users      = ref([]);
const loading    = ref(true);
const search     = ref('');
const page       = ref(1);
const pagination = ref({ last_page: 1, current_page: 1, total: 0 });

const empLabels = {
    employed: 'Наёмный', business_owner: 'Бизнес', self_employed: 'ИП',
    retired: 'Пенсионер', student: 'Студент', unemployed: 'Безработный',
};
const empLabel = (v) => empLabels[v] ?? '—';

let timer = null;
function debouncedLoad() {
    clearTimeout(timer);
    timer = setTimeout(() => { page.value = 1; load(); }, 400);
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/public-users', {
            params: { search: search.value, page: page.value },
        });
        users.value      = data.data.data;
        pagination.value = { last_page: data.data.last_page, current_page: data.data.current_page, total: data.data.total };
    } finally {
        loading.value = false;
    }
}

async function blockUser(u) {
    await api.post(`/owner/public-users/${u.id}/block`);
    u.api_token = null;
}

onMounted(load);
</script>

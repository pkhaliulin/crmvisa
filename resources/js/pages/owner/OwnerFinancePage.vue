<template>
    <div class="space-y-5">

        <div class="flex gap-3">
            <select v-model="filterType" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">Все типы</option>
                <option value="payment">Оплаты</option>
                <option value="commission">Комиссии</option>
                <option value="refund">Возвраты</option>
            </select>
            <select v-model="filterStatus" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">Все статусы</option>
                <option value="paid">Оплачено</option>
                <option value="pending">Ожидание</option>
                <option value="failed">Ошибка</option>
            </select>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">Агентство</th>
                        <th class="px-4 py-3 text-left">Тип</th>
                        <th class="px-4 py-3 text-right">Сумма</th>
                        <th class="px-4 py-3 text-center">Статус</th>
                        <th class="px-4 py-3 text-left">Дата</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="5" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else-if="!transactions.length">
                        <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-400">
                            Транзакций пока нет
                        </td>
                    </tr>
                    <tr v-else v-for="t in transactions" :key="t.id" class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-800">{{ t.agency_name || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="typeClass(t.type)">
                                {{ typeLabel(t.type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800">
                            ${{ Number(t.amount || 0).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="statusClass(t.status)">
                                {{ statusLabel(t.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400">
                            {{ new Date(t.created_at).toLocaleDateString('ru-RU') }}
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
import api from '@/api/axios';

const transactions = ref([]);
const loading      = ref(true);
const filterType   = ref('');
const filterStatus = ref('');
const page         = ref(1);
const pagination   = ref({ last_page: 1, current_page: 1, total: 0 });

const typeLabel  = (t) => ({ payment: 'Оплата', commission: 'Комиссия', refund: 'Возврат' }[t] ?? t);
const typeClass  = (t) => ({ payment: 'bg-blue-50 text-blue-700', commission: 'bg-amber-50 text-amber-700', refund: 'bg-gray-100 text-gray-600' }[t]);
const statusLabel= (s) => ({ paid: 'Оплачено', pending: 'Ожидание', failed: 'Ошибка' }[s] ?? s);
const statusClass= (s) => ({ paid: 'bg-green-50 text-green-700', pending: 'bg-amber-50 text-amber-600', failed: 'bg-red-50 text-red-600' }[s]);

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/transactions', {
            params: { type: filterType.value, status: filterStatus.value, page: page.value },
        });
        transactions.value = data.data.data;
        pagination.value   = { last_page: data.data.last_page, current_page: data.data.current_page, total: data.data.total };
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

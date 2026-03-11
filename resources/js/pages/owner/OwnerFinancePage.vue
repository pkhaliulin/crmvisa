<template>
    <div class="space-y-5">

        <div class="flex gap-3">
            <SearchSelect v-model="filterType" @change="load" compact
                :items="filterTypeOptions" allow-all :all-label="t('owner.finance.allTypes')" />
            <SearchSelect v-model="filterStatus" @change="load" compact
                :items="filterStatusOptions" allow-all :all-label="t('owner.finance.allStatuses')" />
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">{{ t('owner.finance.thAgency') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.finance.thType') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('owner.finance.thAmount') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.finance.thStatus') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.finance.thDate') }}</th>
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
                            {{ t('owner.finance.noTransactions') }}
                        </td>
                    </tr>
                    <tr v-else v-for="tx in transactions" :key="tx.id" class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-800">{{ tx.agency_name || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="typeClass(tx.type)">
                                {{ typeLabel(tx.type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-gray-800">
                            ${{ Number(tx.amount || 0).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="statusClass(tx.status)">
                                {{ statusLabel(tx.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400">
                            {{ new Date(tx.created_at).toLocaleDateString('ru-RU') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="px-5 py-3 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
                <span>{{ t('owner.finance.total', { count: pagination.total }) }}</span>
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
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const transactions = ref([]);
const loading      = ref(true);
const filterType   = ref('');
const filterStatus = ref('');
const page         = ref(1);
const pagination   = ref({ last_page: 1, current_page: 1, total: 0 });

const filterTypeOptions = computed(() => [
    { value: 'payment', label: t('owner.finance.payments') },
    { value: 'commission', label: t('owner.finance.commissions') },
    { value: 'refund', label: t('owner.finance.refunds') },
]);

const filterStatusOptions = computed(() => [
    { value: 'paid', label: t('owner.finance.paid') },
    { value: 'pending', label: t('owner.finance.pending') },
    { value: 'failed', label: t('owner.finance.failed') },
]);

const typeLabel = computed(() => (tp) => ({
    payment: t('owner.finance.typePayment'),
    commission: t('owner.finance.typeCommission'),
    refund: t('owner.finance.typeRefund'),
}[tp] ?? tp));

const typeClass  = (tp) => ({ payment: 'bg-blue-50 text-blue-700', commission: 'bg-amber-50 text-amber-700', refund: 'bg-gray-100 text-gray-600' }[tp]);

const statusLabel = computed(() => (s) => ({
    paid: t('owner.finance.paid'),
    pending: t('owner.finance.pending'),
    failed: t('owner.finance.failed'),
}[s] ?? s));

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

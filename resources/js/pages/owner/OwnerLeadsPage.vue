<template>
    <div class="space-y-5">

        <!-- Фильтры -->
        <div class="flex flex-wrap gap-3">
            <select v-model="filterStatus" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">{{ t('owner.leads.allStatuses') }}</option>
                <option v-for="s in statusOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
            <select v-model="filterCountry" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">{{ t('owner.leads.allCountries') }}</option>
                <option v-for="cc in ['DE','ES','FR','IT','PL','CZ','GB','US','CA','KR','AE']" :key="cc" :value="cc">
                    {{ cc }}
                </option>
            </select>
        </div>

        <!-- Таблица -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">{{ t('owner.leads.client') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.leads.country') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.leads.score') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.leads.agency') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.leads.status') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.leads.created') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="6" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else v-for="l in leads" :key="l.id" class="hover:bg-gray-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ l.user_name || '—' }}</div>
                            <div class="text-xs text-gray-400">{{ l.user_phone }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span>{{ flagMap[l.country_code] ?? '' }} {{ l.country_code }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-sm"
                                :class="l.score >= 60 ? 'text-[#1BA97F]' : l.score >= 40 ? 'text-amber-500' : 'text-red-500'">
                                {{ l.score }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs">{{ l.agency_name || '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full font-medium"
                                :class="statusClass(l.status)">
                                {{ statusLabel(l.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400">
                            {{ new Date(l.created_at).toLocaleDateString('ru-RU') }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Пагинация -->
            <div class="px-5 py-3 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
                <span>{{ t('owner.leads.total', { count: pagination.total }) }}</span>
                <div class="flex gap-1">
                    <button v-for="p in Math.min(pagination.last_page, 10)" :key="p"
                        @click="page = p; load()"
                        class="w-7 h-7 rounded-lg"
                        :class="p === pagination.current_page
                            ? 'bg-[#0A1F44] text-white'
                            : 'hover:bg-gray-100 text-gray-600'">
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

const { t } = useI18n();

const leads        = ref([]);
const loading      = ref(true);
const filterStatus = ref('');
const filterCountry= ref('');
const page         = ref(1);
const pagination   = ref({ last_page: 1, current_page: 1, total: 0 });

const flagMap = {
    DE: '🇩🇪', ES: '🇪🇸', FR: '🇫🇷', IT: '🇮🇹', PL: '🇵🇱',
    CZ: '🇨🇿', GB: '🇬🇧', US: '🇺🇸', CA: '🇨🇦', KR: '🇰🇷', AE: '🇦🇪',
};

const statusOptions = computed(() => [
    { value: 'new', label: t('owner.leads.new') },
    { value: 'assigned', label: t('owner.leads.assigned') },
    { value: 'converted', label: t('owner.leads.converted') },
    { value: 'rejected', label: t('owner.leads.rejected') },
]);

const statusLabelMap = computed(() => ({
    new: t('owner.leads.statusNew'),
    assigned: t('owner.leads.statusAssigned'),
    converted: t('owner.leads.statusConverted'),
    rejected: t('owner.leads.statusRejected'),
}));

const statusLabel = (s) => statusLabelMap.value[s] ?? s;
const statusClass = (s) => ({
    new:       'bg-blue-50 text-blue-700',
    assigned:  'bg-amber-50 text-amber-700',
    converted: 'bg-green-50 text-green-700',
    rejected:  'bg-gray-100 text-gray-500',
}[s] ?? 'bg-gray-50 text-gray-500');

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/leads', {
            params: { status: filterStatus.value, country: filterCountry.value, page: page.value },
        });
        leads.value      = data.data.data;
        pagination.value = { last_page: data.data.last_page, current_page: data.data.current_page, total: data.data.total };
    } finally {
        loading.value = false;
    }
}

onMounted(load);
</script>

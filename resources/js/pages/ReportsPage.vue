<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Отчёты</h1>
      <p class="text-sm text-gray-500 mt-1">Аналитика по агентству</p>
    </div>

    <!-- Вкладки -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
      <button v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key"
        :class="['px-4 py-1.5 text-sm rounded-lg transition-all font-medium',
          activeTab === tab.key
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700']">
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <!-- Обзор -->
    <div v-else-if="activeTab === 'overview'" class="space-y-6">
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Всего заявок</p>
          <p class="text-3xl font-bold text-gray-900 mt-1">{{ overview.total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Завершено</p>
          <p class="text-3xl font-bold text-green-600 mt-1">{{ overview.completed }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-xs text-gray-500 uppercase tracking-wide">Конверсия</p>
          <p class="text-3xl font-bold text-blue-600 mt-1">{{ overview.conversion }}%</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">Источники лидов</h3>
        <div class="space-y-2">
          <div v-for="src in overview.by_source" :key="src.source"
            class="flex items-center justify-between text-sm">
            <span class="text-gray-600 capitalize">{{ src.source }}</span>
            <span class="font-medium text-gray-900">{{ src.count }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">По месяцам</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="pb-2 font-medium text-gray-500">Месяц</th>
                <th class="pb-2 font-medium text-gray-500">Всего</th>
                <th class="pb-2 font-medium text-gray-500">Завершено</th>
                <th class="pb-2 font-medium text-gray-500">Конверсия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in overview.by_month" :key="row.month" class="border-b border-gray-50">
                <td class="py-2 text-gray-700">{{ row.month }}</td>
                <td class="py-2">{{ row.total }}</td>
                <td class="py-2 text-green-600">{{ row.completed }}</td>
                <td class="py-2 text-blue-600">
                  {{ row.total > 0 ? Math.round(row.completed / row.total * 100) : 0 }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- По менеджерам -->
    <div v-else-if="activeTab === 'managers'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-500">Менеджер</th>
            <th class="px-4 py-3 font-medium text-gray-500">Всего</th>
            <th class="px-4 py-3 font-medium text-gray-500">Активных</th>
            <th class="px-4 py-3 font-medium text-gray-500">Завершено</th>
            <th class="px-4 py-3 font-medium text-gray-500">Конверсия</th>
            <th class="px-4 py-3 font-medium text-gray-500">Просрочено</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="mgr in managers" :key="mgr.id"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ mgr.name }}</div>
              <div class="text-xs text-gray-400">{{ mgr.email }}</div>
            </td>
            <td class="px-4 py-3 text-gray-700">{{ mgr.total_cases }}</td>
            <td class="px-4 py-3 text-gray-700">{{ mgr.active_cases }}</td>
            <td class="px-4 py-3 text-green-600 font-medium">{{ mgr.completed_cases }}</td>
            <td class="px-4 py-3 text-blue-600 font-medium">{{ mgr.conversion }}%</td>
            <td class="px-4 py-3">
              <span :class="['font-medium', mgr.overdue_cases > 0 ? 'text-red-600' : 'text-gray-400']">
                {{ mgr.overdue_cases }}
              </span>
            </td>
          </tr>
          <tr v-if="managers.length === 0">
            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Нет данных</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- По странам -->
    <div v-else-if="activeTab === 'countries'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-500">Страна</th>
            <th class="px-4 py-3 font-medium text-gray-500">Всего заявок</th>
            <th class="px-4 py-3 font-medium text-gray-500">Завершено</th>
            <th class="px-4 py-3 font-medium text-gray-500">Конверсия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in countries" :key="row.country_code"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ countryFlag(row.country_code) }} {{ countryName(row.country_code) }}</td>
            <td class="px-4 py-3 text-gray-700">{{ row.total }}</td>
            <td class="px-4 py-3 text-green-600">{{ row.completed }}</td>
            <td class="px-4 py-3 text-blue-600">
              {{ row.total > 0 ? Math.round(row.completed / row.total * 100) : 0 }}%
            </td>
          </tr>
          <tr v-if="countries.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Нет данных</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- SLA -->
    <div v-else-if="activeTab === 'sla'" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700">Среднее время по этапам</h3>
      </div>
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-500">Этап</th>
            <th class="px-4 py-3 font-medium text-gray-500">Количество</th>
            <th class="px-4 py-3 font-medium text-gray-500">Среднее (дней)</th>
            <th class="px-4 py-3 font-medium text-gray-500">Максимум (ч)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in slaPerf" :key="row.stage"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900 capitalize">{{ stageLabel(row.stage) }}</td>
            <td class="px-4 py-3 text-gray-700">{{ row.count }}</td>
            <td class="px-4 py-3 text-blue-600 font-medium">{{ row.avg_days }}</td>
            <td class="px-4 py-3 text-gray-500">{{ row.max_hours }}ч</td>
          </tr>
          <tr v-if="slaPerf.length === 0">
            <td colspan="4" class="px-4 py-8 text-center text-gray-400">Нет данных</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '@/api/index';
import { useCountries } from '@/composables/useCountries';

const { countryName, countryFlag } = useCountries();

const activeTab = ref('overview');
const loading = ref(false);

const overview = ref({ total: 0, completed: 0, conversion: 0, by_source: [], by_month: [] });
const managers = ref([]);
const countries = ref([]);
const slaPerf = ref([]);

const tabs = [
  { key: 'overview',  label: 'Обзор' },
  { key: 'managers',  label: 'Менеджеры' },
  { key: 'countries', label: 'Страны' },
  { key: 'sla',       label: 'SLA' },
];

const stageLabels = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'На рассмотрении', result: 'Результат',
};

function stageLabel(s) { return stageLabels[s] || s; }

async function loadTab(tab) {
  loading.value = true;
  try {
    if (tab === 'overview') {
      const res = await api.get('/reports/overview');
      overview.value = res.data.data;
    } else if (tab === 'managers') {
      const res = await api.get('/reports/managers');
      managers.value = res.data.data;
    } else if (tab === 'countries') {
      const res = await api.get('/reports/countries');
      countries.value = res.data.data;
    } else if (tab === 'sla') {
      const res = await api.get('/reports/sla-performance');
      slaPerf.value = res.data.data;
    }
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
}

watch(activeTab, (tab) => loadTab(tab));
onMounted(() => loadTab('overview'));
</script>

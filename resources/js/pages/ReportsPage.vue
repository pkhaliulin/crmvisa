<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.reports.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('crm.reports.subtitle') }}</p>
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

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- ОБЗОР -->
    <div v-else-if="activeTab === 'overview'" class="space-y-4">
      <!-- Метрики -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.reports.totalCases') }}</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">{{ overview.total }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.reports.completedCases') }}</p>
          <p class="text-2xl font-bold text-green-600 mt-1">{{ overview.completed }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.reports.conversion') }}</p>
          <p class="text-2xl font-bold text-blue-600 mt-1">{{ overview.conversion }}%</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.reports.avgTime') }}</p>
          <p class="text-2xl font-bold text-purple-600 mt-1">{{ overview.avg_processing_days ?? '--' }}</p>
        </div>
      </div>

      <!-- Источники лидов с прогресс-барами -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.reports.leadSources') }}</h3>
        <div class="space-y-3">
          <div v-for="src in overview.by_source" :key="src.source" class="space-y-1">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">{{ sourceLabel(src.source) }}</span>
              <span class="font-semibold text-gray-900">{{ src.count }}</span>
            </div>
            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full bg-blue-500 transition-all"
                :style="{ width: maxSource ? (src.count / maxSource * 100) + '%' : '0%' }"></div>
            </div>
          </div>
          <div v-if="!overview.by_source?.length" class="text-sm text-gray-400 text-center py-4">{{ t('crm.reports.noData') }}</div>
        </div>
      </div>

      <!-- По месяцам -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.reports.byMonth') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.reports.month') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.reports.total') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.reports.completed') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.reports.conversion') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in overview.by_month" :key="row.month" class="border-b border-gray-50">
                <td class="py-2 text-gray-700">{{ row.month }}</td>
                <td class="py-2">{{ row.total }}</td>
                <td class="py-2 text-green-600">{{ row.completed }}</td>
                <td class="py-2 text-blue-600">{{ row.total > 0 ? Math.round(row.completed / row.total * 100) : 0 }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- МЕНЕДЖЕРЫ -->
    <div v-else-if="activeTab === 'managers'" class="space-y-3">
      <div v-for="mgr in managers" :key="mgr.id"
        class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
            {{ mgr.name?.[0]?.toUpperCase() ?? '?' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 truncate">{{ mgr.name }}</p>
            <p class="text-xs text-gray-400">{{ mgr.email }}</p>
          </div>
          <span v-if="mgr.overdue_cases > 0" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-50 text-red-600">
            {{ t('crm.reports.overdueCount', { n: mgr.overdue_cases }) }}
          </span>
        </div>

        <div class="grid grid-cols-4 gap-3 text-center">
          <div>
            <p class="text-lg font-bold text-gray-900">{{ mgr.total_cases }}</p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.total') }}</p>
          </div>
          <div>
            <p class="text-lg font-bold text-blue-600">{{ mgr.active_cases }}</p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.activeCases') }}</p>
          </div>
          <div>
            <p class="text-lg font-bold text-green-600">{{ mgr.completed_cases }}</p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.completed') }}</p>
          </div>
          <div>
            <p class="text-lg font-bold text-purple-600">{{ mgr.conversion }}%</p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.conversion') }}</p>
          </div>
        </div>

        <!-- Нагрузка бар -->
        <div class="mt-3 pt-3 border-t border-gray-100">
          <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
            <span>{{ t('crm.reports.loadBar') }}</span>
            <span>{{ t('crm.reports.activeCount', { n: mgr.active_cases }) }}</span>
          </div>
          <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all"
              :class="mgr.active_cases > 15 ? 'bg-red-500' : mgr.active_cases > 8 ? 'bg-yellow-500' : 'bg-green-500'"
              :style="{ width: Math.min(mgr.active_cases / 20 * 100, 100) + '%' }"></div>
          </div>
        </div>
      </div>
      <div v-if="managers.length === 0" class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">{{ t('crm.reports.noData') }}</div>
    </div>

    <!-- СТРАНЫ -->
    <div v-else-if="activeTab === 'countries'" class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <div v-for="row in countries" :key="row.country_code"
        class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3 mb-2">
          <span class="text-2xl">{{ countryFlag(row.country_code) }}</span>
          <div class="flex-1">
            <p class="font-semibold text-gray-900">{{ countryName(row.country_code) }}</p>
            <p class="text-xs text-gray-400">{{ row.country_code }}</p>
          </div>
          <span class="text-xl font-bold text-gray-900">{{ row.total }}</span>
        </div>
        <div class="flex items-center gap-3 text-xs">
          <span class="text-green-600 font-medium">{{ t('crm.reports.completedCount', { n: row.completed }) }}</span>
          <span class="text-blue-600">{{ t('crm.reports.conversionPercent', { n: row.total > 0 ? Math.round(row.completed / row.total * 100) : 0 }) }}</span>
        </div>
        <div class="mt-2 w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full bg-green-500 rounded-full"
            :style="{ width: row.total > 0 ? (row.completed / row.total * 100) + '%' : '0%' }"></div>
        </div>
      </div>
      <div v-if="countries.length === 0" class="col-span-2 bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">{{ t('crm.reports.noData') }}</div>
    </div>

    <!-- SLA -->
    <div v-else-if="activeTab === 'sla'" class="space-y-3">
      <div v-for="row in slaPerf" :key="row.stage"
        class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full" :class="stageDot(row.stage)"></span>
            <span class="font-semibold text-gray-800">{{ stageLabel(row.stage) }}</span>
          </div>
          <span class="text-xs text-gray-400">{{ t('crm.reports.transitions', { n: row.count }) }}</span>
        </div>
        <div class="grid grid-cols-3 gap-3 text-center">
          <div>
            <p class="text-lg font-bold text-blue-600">{{ row.avg_days }}</p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.avgDays') }}</p>
          </div>
          <div>
            <p class="text-lg font-bold text-gray-700">{{ row.max_hours }}ч</p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.maxDays') }}</p>
          </div>
          <div>
            <p class="text-lg font-bold" :class="row.overdue_pct > 20 ? 'text-red-600' : 'text-green-600'">
              {{ row.overdue_pct ?? 0 }}%
            </p>
            <p class="text-[10px] text-gray-400 uppercase">{{ t('crm.reports.overdueLabel') }}</p>
          </div>
        </div>
      </div>
      <div v-if="slaPerf.length === 0" class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400 text-sm">{{ t('crm.reports.noData') }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { useCountries } from '@/composables/useCountries';

const { t } = useI18n();
const { countryName, countryFlag } = useCountries();

const activeTab = ref('overview');
const loading = ref(false);

const overview = ref({ total: 0, completed: 0, conversion: 0, avg_processing_days: null, by_source: [], by_month: [] });
const managers = ref([]);
const countries = ref([]);
const slaPerf = ref([]);

const tabs = computed(() => [
  { key: 'overview',  label: t('crm.reports.tabOverview') },
  { key: 'managers',  label: t('crm.reports.tabManagers') },
  { key: 'countries', label: t('crm.reports.tabCountries') },
  { key: 'sla',       label: t('crm.reports.tabSla') },
]);

const maxSource = computed(() => {
  if (!overview.value.by_source?.length) return 0;
  return Math.max(...overview.value.by_source.map(s => s.count));
});

const sourceLabels = computed(() => ({
  direct: t('crm.sources.direct'), referral: t('crm.sources.referral'), marketplace: t('crm.sources.marketplace'),
  instagram: t('crm.sources.instagram'), facebook: t('crm.sources.facebook'), telegram: t('crm.sources.telegram'),
  website: t('crm.sources.website'), other: t('crm.sources.other'),
}));
function sourceLabel(s) { return sourceLabels.value[s] ?? s ?? '--'; }

const stageLabelsMap = computed(() => ({
  lead: t('crm.stages.lead'), qualification: t('crm.stages.qualification'), documents: t('crm.stages.documents'),
  doc_review: t('crm.stages.doc_review'), translation: t('crm.stages.translation'), ready: t('crm.stages.ready'),
  review: t('crm.stages.review'), result: t('crm.stages.result'),
}));
const stageDots = {
  lead: 'bg-gray-400', qualification: 'bg-blue-500', documents: 'bg-purple-500',
  doc_review: 'bg-cyan-500', translation: 'bg-yellow-500', ready: 'bg-orange-500',
  review: 'bg-indigo-500', result: 'bg-green-500',
};
function stageLabel(s) { return stageLabelsMap.value[s] || s; }
function stageDot(s) { return stageDots[s] || 'bg-gray-400'; }

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
  } catch { /* ignore */ } finally {
    loading.value = false;
  }
}

watch(activeTab, (tab) => loadTab(tab));
onMounted(() => loadTab('overview'));
</script>

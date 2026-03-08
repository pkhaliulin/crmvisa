<template>
  <div class="space-y-4">
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Фильтр периода -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-1.5 bg-gray-100 rounded-lg p-0.5">
          <button v-for="p in periodOptions" :key="p.value"
            @click="changePeriod(p.value)"
            :class="[
              'px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
              period === p.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
            ]">
            {{ p.label }}
          </button>
        </div>
        <p class="text-xs text-gray-400">{{ periodDescription }}</p>
      </div>

      <!-- Подсказки -->
      <div v-if="hints.length" class="space-y-2">
        <div v-for="(hint, i) in hints" :key="i"
          :class="[
            'flex items-start gap-3 px-4 py-3 rounded-xl border text-sm',
            hint.type === 'warning' ? 'bg-amber-50 border-amber-200 text-amber-800' :
            hint.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
            hint.type === 'tip' ? 'bg-purple-50 border-purple-200 text-purple-800' :
            'bg-blue-50 border-blue-200 text-blue-800',
          ]">
          <div :class="[
            'w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5',
            hint.type === 'warning' ? 'bg-amber-100' :
            hint.type === 'success' ? 'bg-green-100' :
            hint.type === 'tip' ? 'bg-purple-100' : 'bg-blue-100',
          ]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path v-if="hint.type === 'warning'" stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
              <path v-else-if="hint.type === 'success'" stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M12 14a2 2 0 00.914-3.782 1.136 1.136 0 01-.535-.612.647.647 0 00-.379-.38.647.647 0 00-.379.38 1.136 1.136 0 01-.535.612A2 2 0 0012 14z"/>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-xs uppercase tracking-wide opacity-70">{{ t(`crm.dashboard.hints.${hint.key}_title`, hint.params || {}) }}</p>
            <p class="mt-0.5">{{ t(`crm.dashboard.hints.${hint.key}_msg`, hint.params || {}) }}</p>
          </div>
          <router-link v-if="hint.action" :to="hint.action"
            class="shrink-0 text-xs font-medium underline opacity-70 hover:opacity-100 mt-1">
            {{ t('crm.dashboard.goTo') }}
          </router-link>
          <button @click="hints.splice(i, 1)" class="shrink-0 opacity-40 hover:opacity-80 mt-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Ключевые метрики (кликабельные) -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <component :is="m.to ? 'router-link' : 'div'" v-for="m in metricCards" :key="m.label"
          :to="m.to || undefined"
          :class="[
            'bg-white rounded-xl border border-gray-200 px-4 py-3 transition-all',
            m.to ? 'hover:border-blue-300 hover:shadow-md cursor-pointer' : ''
          ]">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ m.label }}</p>
          <div class="flex items-end gap-2">
            <p class="text-2xl font-bold mt-0.5" :class="m.color">{{ m.value }}</p>
            <span v-if="m.growth !== undefined && m.growth !== 0"
              class="text-[10px] font-semibold mb-1 px-1.5 py-0.5 rounded-full"
              :class="m.growth > 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'">
              {{ m.growth > 0 ? '+' : '' }}{{ m.growth }}%
            </span>
          </div>
          <p v-if="m.sub" class="text-[10px] text-gray-400 mt-0.5">{{ m.sub }}</p>
        </component>
      </div>

      <!-- Графики row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Динамика -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.chartTitle') }}</h3>
          <div class="h-48 relative">
            <svg v-if="chartData.length" class="w-full h-full" :viewBox="`0 0 ${chartW} ${chartH}`" preserveAspectRatio="none">
              <line v-for="i in 4" :key="'g'+i" :x1="0" :y1="chartH * i / 4" :x2="chartW" :y2="chartH * i / 4" stroke="#f3f4f6" stroke-width="1"/>
              <polygon :points="areaCreated" fill="rgba(59,130,246,0.1)"/>
              <polyline :points="lineCreated" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linejoin="round"/>
              <polygon :points="areaCompleted" fill="rgba(16,185,129,0.1)"/>
              <polyline :points="lineCompleted" fill="none" stroke="#10b981" stroke-width="2" stroke-linejoin="round"/>
            </svg>
            <div v-else class="flex items-center justify-center h-full text-sm text-gray-400">{{ t('crm.dashboard.noData') }}</div>
          </div>
          <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-blue-500 rounded"></span> {{ t('crm.dashboard.created') }}</span>
            <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-green-500 rounded"></span> {{ t('crm.dashboard.completed') }}</span>
          </div>
        </div>

        <!-- Pie: Источники лидов -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.leadSourcesTitle') }}</h3>
          <div v-if="leadSources.length" class="flex flex-col items-center">
            <svg width="140" height="140" viewBox="0 0 140 140">
              <circle v-for="(s, i) in pieSlices" :key="i"
                cx="70" cy="70" r="55" fill="none" :stroke="s.color" stroke-width="30"
                :stroke-dasharray="`${s.dash} ${s.gap}`" :stroke-dashoffset="s.offset"
                class="transition-all duration-500"
              />
            </svg>
            <div class="mt-3 w-full space-y-1.5">
              <div v-for="(s, i) in leadSources" :key="i" class="flex items-center gap-2 text-xs">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{background: sourceColors[i % sourceColors.length]}"></span>
                <span class="flex-1 text-gray-600 truncate">{{ sourceLabels[s.source] || s.source }}</span>
                <span class="font-bold text-gray-800">{{ s.count }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400 text-center py-8">{{ t('crm.dashboard.noData') }}</div>
        </div>
      </div>

      <!-- Второй ряд: Этапы + Страны -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Заявки по этапам -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.stagesTitle') }}</h3>
          <div class="space-y-2.5">
            <router-link v-for="stage in stageRows" :key="stage.key"
              :to="{ name: 'cases', query: { stage: stage.key } }"
              class="flex items-center gap-3 group hover:bg-gray-50 rounded-lg px-1 -mx-1 py-0.5 transition-colors">
              <span class="text-xs text-gray-500 w-28 shrink-0 group-hover:text-blue-600 transition-colors">{{ stage.label }}</span>
              <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700" :style="{ width: stage.percent + '%', background: stageColor(stage.key) }"/>
              </div>
              <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ stage.count }}</span>
            </router-link>
          </div>
        </div>

        <!-- Топ-5 стран -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.topCountries') }}</h3>
          <div v-if="topCountries.length" class="space-y-2.5">
            <div v-for="(c, i) in topCountries" :key="c.country_code" class="flex items-center gap-3">
              <span class="text-sm font-bold text-gray-400 w-4">{{ i + 1 }}</span>
              <span class="text-sm text-gray-700 flex-1">{{ c.country_code }}</span>
              <div class="w-20 bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full" :style="{ width: (c.total / maxCountry * 100) + '%' }"/>
              </div>
              <span class="text-xs font-bold text-gray-700 w-6 text-right">{{ c.total }}</span>
            </div>
          </div>
          <div v-else class="text-sm text-gray-400 text-center py-8">{{ t('crm.dashboard.noData') }}</div>
        </div>
      </div>

      <!-- Аналитика по этапам (SLA) -->
      <div v-if="stageAnalyticsRows.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.stageAnalytics') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-500 text-[11px] uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 font-medium">{{ t('crm.dashboard.stageCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.avgTime') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.maxTime') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.slaCompliance') }}</th>
                <th class="px-4 py-2.5 w-32"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="s in stageAnalyticsRows" :key="s.stage" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-800">{{ stageLabel(s.stage) }}</td>
                <td class="px-4 py-2.5 text-right text-gray-700">{{ formatHours(s.avg_hours) }}</td>
                <td class="px-4 py-2.5 text-right text-gray-500">{{ formatHours(s.max_hours) }}</td>
                <td class="px-4 py-2.5 text-right font-bold" :class="s.sla_compliance >= 80 ? 'text-green-600' : s.sla_compliance >= 60 ? 'text-amber-600' : 'text-red-600'">
                  {{ s.sla_compliance }}%
                </td>
                <td class="px-4 py-2.5">
                  <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                      :class="s.sla_compliance >= 80 ? 'bg-green-500' : s.sla_compliance >= 60 ? 'bg-amber-400' : 'bg-red-500'"
                      :style="{ width: s.sla_compliance + '%' }"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Менеджеры (расширенная таблица) -->
      <div v-if="stats?.managers?.length" class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 text-sm mb-4">{{ t('crm.dashboard.managersLoad') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-gray-500 text-[11px] uppercase tracking-wide">
              <tr>
                <th class="text-left px-4 py-2.5 font-medium">{{ t('crm.dashboard.managerCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.activeCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.completedCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.overdueCol') }}</th>
                <th class="text-right px-4 py-2.5 font-medium">{{ t('crm.dashboard.conversionCol') }}</th>
                <th class="px-4 py-2.5 w-32"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="m in stats.managers" :key="m.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2.5 font-medium text-gray-800">{{ m.name }}</td>
                <td class="px-4 py-2.5 text-right font-bold text-gray-900">{{ m.active_cases }}</td>
                <td class="px-4 py-2.5 text-right text-green-600 font-semibold">{{ m.completed_cases }}</td>
                <td class="px-4 py-2.5 text-right font-semibold" :class="m.overdue_cases > 0 ? 'text-red-600' : 'text-gray-400'">{{ m.overdue_cases }}</td>
                <td class="px-4 py-2.5 text-right font-semibold" :class="m.conversion >= 80 ? 'text-green-600' : m.conversion >= 50 ? 'text-amber-600' : 'text-gray-500'">{{ m.conversion }}%</td>
                <td class="px-4 py-2.5">
                  <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div class="h-full rounded-full transition-all"
                      :class="m.active_cases > maxManagerLoad * 0.8 ? 'bg-red-500' : m.active_cases > maxManagerLoad * 0.5 ? 'bg-amber-400' : 'bg-indigo-500'"
                      :style="{ width: `${Math.min(100, (m.active_cases / maxManagerLoad) * 100)}%` }"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Конверсии + Среднее время + Повторные клиенты -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.conversionLeadCase') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-blue-600">{{ metrics.conversion_lead_case }}%</p>
          </div>
          <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-blue-500 rounded-full transition-all" :style="{ width: metrics.conversion_lead_case + '%' }"/>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.conversionCaseVisa') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-green-600">{{ metrics.conversion_case_visa }}%</p>
          </div>
          <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: metrics.conversion_case_visa + '%' }"/>
          </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.avgProcessing') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-indigo-600">{{ formatHours(metrics.avg_processing_hours) }}</p>
          </div>
          <p class="text-[10px] text-gray-400 mt-1">{{ t('crm.dashboard.avgProcessingSub') }}</p>
        </div>
        <router-link :to="{ name: 'clients' }" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:shadow-md transition-all">
          <p class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ t('crm.dashboard.repeatClients') }}</p>
          <div class="flex items-end gap-2 mt-2">
            <p class="text-3xl font-bold text-purple-600">{{ stats?.repeat_clients ?? 0 }}</p>
            <span v-if="stats?.clients_total" class="text-xs text-gray-400 mb-1">/ {{ stats.clients_total }}</span>
          </div>
          <p class="text-[10px] text-gray-400 mt-1">{{ t('crm.dashboard.repeatClientsSub') }}</p>
        </router-link>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { dashboardApi } from '@/api/dashboard';

const { t } = useI18n();

const loading = ref(true);
const stats = ref(null);
const hints = ref([]);
const period = ref('30d');

const periodOptions = computed(() => [
  { value: '7d',   label: t('crm.dashboard.period7d') },
  { value: '30d',  label: t('crm.dashboard.period30d') },
  { value: '90d',  label: t('crm.dashboard.period90d') },
  { value: '365d', label: t('crm.dashboard.period365d') },
  { value: 'all',  label: t('crm.dashboard.periodAll') },
]);

const periodDescription = computed(() => {
  const labels = { '7d': t('crm.dashboard.periodDesc7d'), '30d': t('crm.dashboard.periodDesc30d'), '90d': t('crm.dashboard.periodDesc90d'), '365d': t('crm.dashboard.periodDesc365d'), 'all': t('crm.dashboard.periodDescAll') };
  return labels[period.value] || '';
});

const STAGES = computed(() => [
  { key: 'lead',          label: t('crm.stages.lead') },
  { key: 'qualification', label: t('crm.stages.qualification') },
  { key: 'documents',     label: t('crm.stages.documents') },
  { key: 'doc_review',    label: t('crm.stages.doc_review') },
  { key: 'translation',   label: t('crm.stages.translation') },
  { key: 'ready',         label: t('crm.stages.ready') },
  { key: 'review',        label: t('crm.stages.review') },
  { key: 'result',        label: t('crm.stages.result') },
]);

const STAGE_COLORS = {
  lead: '#3b82f6', qualification: '#8b5cf6', documents: '#f59e0b',
  doc_review: '#06b6d4', translation: '#ec4899', ready: '#f97316',
  review: '#6366f1', result: '#10b981',
};

function stageColor(key) { return STAGE_COLORS[key] || '#6b7280'; }
function stageLabel(key) {
  const s = STAGES.value.find(s => s.key === key);
  return s ? s.label : key;
}

function formatHours(h) {
  if (!h || h === 0) return '0';
  if (h < 1) return `${Math.round(h * 60)}${t('crm.dashboard.minuteShort')}`;
  if (h < 24) return `${Math.round(h)}${t('crm.dashboard.hourShort')}`;
  return `${Math.round(h / 24)}${t('crm.dashboard.dayShort')}`;
}

const sourceColors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#ef4444'];
const sourceLabels = computed(() => ({
  direct: t('crm.sources.direct'), referral: t('crm.sources.referral'), marketplace: t('crm.sources.marketplace'),
  instagram: 'Instagram', facebook: 'Facebook', telegram: t('crm.sources.telegram'),
  website: t('crm.sources.website'), other: t('crm.sources.other'),
}));

const metrics = computed(() => stats.value?.metrics ?? {
  new_leads: 0, completed: 0, visa_issued: 0, completed_total: 0,
  conversion_lead_case: 0, conversion_case_visa: 0, avg_processing_hours: 0,
});

const growth = computed(() => stats.value?.growth ?? {});

const metricCards = computed(() => {
  const m = metrics.value;
  const c = stats.value?.cases ?? {};
  const g = growth.value;
  return [
    { label: t('crm.dashboard.active'), value: c.total_active ?? 0, color: 'text-gray-900', to: { name: 'cases' } },
    { label: t('crm.dashboard.newLeads'), value: m.new_leads ?? 0, color: 'text-blue-600', growth: g.new_leads, to: { name: 'cases', query: { stage: 'lead' } } },
    { label: t('crm.dashboard.completedPeriod'), value: m.completed ?? 0, color: 'text-green-600', growth: g.completed, to: { name: 'cases', query: { stage: 'result' } } },
    { label: t('crm.dashboard.overdue'), value: c.overdue ?? 0, color: c.overdue > 0 ? 'text-red-600' : 'text-gray-900', to: { name: 'overdue' } },
    { label: t('crm.dashboard.critical'), value: c.critical ?? 0, color: c.critical > 0 ? 'text-amber-600' : 'text-gray-900' },
    { label: t('crm.dashboard.unassigned'), value: c.unassigned ?? 0, color: c.unassigned > 0 ? 'text-purple-600' : 'text-gray-900', to: { name: 'cases', query: { assigned_to: 'unassigned' } } },
  ];
});

const stageRows = computed(() => {
  if (!stats.value?.cases?.by_stage) return [];
  const byStage = stats.value.cases.by_stage;
  const maxCount = Math.max(1, ...Object.values(byStage).map(Number));
  return STAGES.value.map(s => ({
    ...s,
    count: Number(byStage[s.key] ?? 0),
    percent: Math.round((Number(byStage[s.key] ?? 0) / maxCount) * 100),
  }));
});

const stageAnalyticsRows = computed(() => {
  if (!stats.value?.stage_analytics) return [];
  const sa = stats.value.stage_analytics;
  return STAGES.value
    .filter(s => sa[s.key])
    .map(s => ({ stage: s.key, ...sa[s.key] }));
});

const maxManagerLoad = computed(() =>
  Math.max(1, ...(stats.value?.managers ?? []).map(m => m.active_cases))
);

const leadSources = computed(() => stats.value?.lead_sources ?? []);
const topCountries = computed(() => stats.value?.top_countries ?? []);
const maxCountry = computed(() => Math.max(1, ...topCountries.value.map(c => c.total)));

// Pie chart slices
const pieSlices = computed(() => {
  const total = leadSources.value.reduce((s, v) => s + v.count, 0);
  if (total === 0) return [];
  const circ = 2 * Math.PI * 55;
  let offset = 0;
  return leadSources.value.map((s, i) => {
    const pct = s.count / total;
    const dash = circ * pct;
    const gap = circ - dash;
    const slice = { dash, gap, offset: -offset + circ / 4, color: sourceColors[i % sourceColors.length] };
    offset += dash;
    return slice;
  });
});

// Line chart
const chartData = computed(() => stats.value?.daily_trend ?? []);
const chartW = 600;
const chartH = 150;

function linePoints(key) {
  if (!chartData.value.length) return '';
  const maxVal = Math.max(1, ...chartData.value.map(d => Math.max(d.created, d.completed)));
  const step = chartW / Math.max(1, chartData.value.length - 1);
  return chartData.value.map((d, i) => `${i * step},${chartH - (d[key] / maxVal * (chartH - 10))}`).join(' ');
}

function areaPoints(key) {
  if (!chartData.value.length) return '';
  const pts = linePoints(key);
  const step = chartW / Math.max(1, chartData.value.length - 1);
  const last = (chartData.value.length - 1) * step;
  return `0,${chartH} ${pts} ${last},${chartH}`;
}

const lineCreated = computed(() => linePoints('created'));
const lineCompleted = computed(() => linePoints('completed'));
const areaCreated = computed(() => areaPoints('created'));
const areaCompleted = computed(() => areaPoints('completed'));

async function fetchDashboard() {
  loading.value = true;
  try {
    const { data } = await dashboardApi.index({ period: period.value });
    stats.value = data.data;
    hints.value = data.data?.hints ?? [];
  } finally {
    loading.value = false;
  }
}

function changePeriod(p) {
  period.value = p;
  fetchDashboard();
}

onMounted(fetchDashboard);
</script>

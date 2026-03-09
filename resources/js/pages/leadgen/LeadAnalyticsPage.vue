<template>
  <div class="space-y-6">
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3">
              <button @click="router.push({ name: 'leadgen' })"
                class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
              </button>
              <div>
                <h1 class="text-xl font-bold text-gray-900">{{ t('crm.leadgen.analytics.title') }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ t('crm.leadgen.analytics.subtitle') }}</p>
              </div>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">{{ t('crm.leadgen.analytics.period') }}:</span>
            <select v-model="period" @change="fetchData"
              class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white">
              <option value="7">{{ t('crm.leadgen.analytics.days7') }}</option>
              <option value="30">{{ t('crm.leadgen.analytics.days30') }}</option>
              <option value="90">{{ t('crm.leadgen.analytics.days90') }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Total -->
      <div class="bg-white rounded-xl border border-gray-200 p-6 text-center">
        <div class="text-4xl font-bold text-gray-900">{{ data.total_leads }}</div>
        <div class="text-sm text-gray-500 mt-1">{{ t('crm.leadgen.analytics.totalLeads') }}</div>
      </div>

      <!-- By Source + By Channel -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- By Source -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.analytics.bySource') }}</h2>
          <div v-if="data.by_source && data.by_source.length" class="space-y-2">
            <div v-for="item in data.by_source" :key="item.lead_source"
              class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="w-2 h-2 rounded-full" :class="sourceColor(item.lead_source)"></div>
                <span class="text-sm text-gray-700">{{ item.lead_source || 'N/A' }}</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="h-2 rounded-full bg-blue-100" :style="{ width: barWidth(item.count, maxSourceCount) + 'px' }">
                  <div class="h-full rounded-full bg-blue-500" :style="{ width: '100%' }"></div>
                </div>
                <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ item.count }}</span>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">{{ t('crm.leadgen.analytics.noData') }}</p>
        </div>

        <!-- By Channel -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.analytics.byChannel') }}</h2>
          <div v-if="data.by_channel && data.by_channel.length" class="space-y-2">
            <div v-for="item in data.by_channel" :key="item.lead_channel_code"
              class="flex items-center justify-between">
              <span class="text-sm text-gray-700">{{ item.lead_channel_code }}</span>
              <div class="flex items-center gap-2">
                <div class="h-2 rounded-full bg-green-100" :style="{ width: barWidth(item.count, maxChannelCount) + 'px' }">
                  <div class="h-full rounded-full bg-green-500" :style="{ width: '100%' }"></div>
                </div>
                <span class="text-sm font-semibold text-gray-900 w-8 text-right">{{ item.count }}</span>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">{{ t('crm.leadgen.analytics.noData') }}</p>
        </div>
      </div>

      <!-- By Stage -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.analytics.byStage') }}</h2>
        <div v-if="data.by_stage && data.by_stage.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <div v-for="item in data.by_stage" :key="item.stage"
            class="bg-gray-50 rounded-lg p-3 text-center">
            <div class="text-lg font-bold text-gray-900">{{ item.count }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ stageLabel(item.stage) }}</div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ t('crm.leadgen.analytics.noData') }}</p>
      </div>

      <!-- Daily Trend -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.analytics.dailyTrend') }}</h2>
        <div v-if="data.daily_trend && data.daily_trend.length" class="flex items-end gap-1 h-32">
          <div v-for="day in data.daily_trend" :key="day.date"
            class="flex-1 bg-blue-500 rounded-t hover:bg-blue-600 transition-colors relative group"
            :style="{ height: trendHeight(day.count) + '%', minHeight: day.count > 0 ? '4px' : '1px' }">
            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 bg-gray-800 text-white text-[10px] rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
              {{ formatDate(day.date) }}: {{ day.count }}
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ t('crm.leadgen.analytics.noData') }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import api from '@/api/index';

const { t } = useI18n();
const router = useRouter();

const loading = ref(true);
const period = ref('30');
const data = ref({ total_leads: 0, by_source: [], by_channel: [], by_stage: [], daily_trend: [] });

const maxSourceCount = computed(() => Math.max(...(data.value.by_source || []).map(s => s.count), 1));
const maxChannelCount = computed(() => Math.max(...(data.value.by_channel || []).map(c => c.count), 1));
const maxTrendCount = computed(() => Math.max(...(data.value.daily_trend || []).map(d => d.count), 1));

function barWidth(count, max) {
  return Math.max(Math.round((count / max) * 80), 4);
}

function trendHeight(count) {
  if (maxTrendCount.value === 0) return 0;
  return Math.max(Math.round((count / maxTrendCount.value) * 100), 2);
}

function sourceColor(source) {
  const map = {
    api: 'bg-blue-500', instagram: 'bg-pink-500', facebook: 'bg-indigo-500',
    telegram: 'bg-sky-500', google_ads: 'bg-yellow-500', website: 'bg-green-500',
    referral: 'bg-purple-500', direct: 'bg-gray-500', visabor: 'bg-teal-500',
    other: 'bg-gray-400',
  };
  return map[source] || 'bg-gray-400';
}

const stageLabels = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  doc_review: 'Проверка', translation: 'Перевод', ready: 'К подаче',
  review: 'Рассмотрение', result: 'Результат',
};

function stageLabel(stage) {
  return stageLabels[stage] || stage;
}

function formatDate(dateStr) {
  const d = new Date(dateStr);
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
}

async function fetchData() {
  loading.value = true;
  try {
    const { data: res } = await api.get('/lead-analytics', { params: { period: period.value } });
    const payload = res?.data || res;
    data.value = payload;
  } catch {
    data.value = { total_leads: 0, by_source: [], by_channel: [], by_stage: [], daily_trend: [] };
  } finally {
    loading.value = false;
  }
}

onMounted(fetchData);
</script>

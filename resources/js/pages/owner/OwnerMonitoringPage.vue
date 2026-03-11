<template>
  <div class="space-y-6">
    <!-- Period filter -->
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800">{{ t('monitoring.title') }}</h2>
      <SearchSelect v-model="period" @change="loadAll" compact
        :items="periodOptions" />
    </div>

    <!-- Alerts Panel -->
    <div v-if="alerts.count > 0"
         :class="[
           'rounded-xl border p-4',
           alerts.level === 'critical' ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'
         ]">
      <div class="flex items-center gap-2 mb-2">
        <span :class="[
          'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold text-white',
          alerts.level === 'critical' ? 'bg-red-500' : 'bg-amber-500'
        ]">!</span>
        <span class="font-semibold text-sm" :class="alerts.level === 'critical' ? 'text-red-700' : 'text-amber-700'">
          {{ t('monitoring.activeAlerts') }}: {{ alerts.count }}
        </span>
      </div>
      <ul class="space-y-1">
        <li v-for="(item, i) in alerts.items" :key="i" class="text-sm"
            :class="item.level === 'critical' ? 'text-red-600' : 'text-amber-600'">
          {{ item.message }}
        </li>
      </ul>
    </div>

    <!-- Health Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      <HealthCard :label="t('monitoring.database')"
                  :value="health.database?.status === 'ok' ? (health.database.response_ms + ' ms') : 'Error'"
                  :color="health.database?.status === 'ok' ? 'green' : 'red'" />
      <HealthCard :label="t('monitoring.cache')"
                  :value="health.cache?.status === 'ok' ? 'OK' : 'Error'"
                  :color="health.cache?.status === 'ok' ? 'green' : 'red'" />
      <HealthCard :label="t('monitoring.queuePending')"
                  :value="String(health.queue?.pending ?? 0)"
                  :color="(health.queue?.pending ?? 0) > 100 ? 'red' : (health.queue?.pending ?? 0) > 10 ? 'yellow' : 'green'" />
      <HealthCard :label="t('monitoring.failedJobs')"
                  :value="String(health.failed_jobs?.count ?? 0)"
                  :color="(health.failed_jobs?.count ?? 0) > 0 ? 'red' : 'green'" />
      <HealthCard :label="t('monitoring.disk')"
                  :value="health.disk?.usage_percent != null ? (health.disk.usage_percent + '%') : 'N/A'"
                  :color="(health.disk?.usage_percent ?? 0) > 90 ? 'red' : (health.disk?.usage_percent ?? 0) > 75 ? 'yellow' : 'green'" />
      <HealthCard :label="t('monitoring.memory')"
                  :value="health.memory?.usage_mb != null ? (health.memory.usage_mb + ' MB') : 'N/A'"
                  :color="'green'" />
    </div>

    <!-- Traffic Chart + Response Time -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">{{ t('monitoring.traffic') }}</h3>
        <div class="flex gap-4 text-xs text-gray-500">
          <span>Avg: <b class="text-gray-700">{{ metrics.response_time?.avg_ms ?? 0 }} ms</b></span>
          <span>P95: <b class="text-gray-700">{{ metrics.response_time?.p95_ms ?? 0 }} ms</b></span>
          <span>Max: <b class="text-gray-700">{{ metrics.response_time?.max_ms ?? 0 }} ms</b></span>
          <span>RPM: <b class="text-gray-700">{{ metrics.rpm ?? 0 }}</b></span>
        </div>
      </div>

      <!-- Bar chart -->
      <div class="flex items-end gap-0.5 h-32">
        <div v-for="(bar, i) in metrics.traffic_hourly ?? []" :key="i"
             class="flex-1 flex flex-col items-stretch justify-end gap-0" :title="barTooltip(bar)">
          <div class="bg-red-400 rounded-t-sm" :style="{ height: barHeight(bar.errors, maxTraffic) + 'px' }"></div>
          <div class="bg-emerald-400" :class="{ 'rounded-t-sm': !bar.errors }"
               :style="{ height: barHeight(bar.ok, maxTraffic) + 'px' }"></div>
        </div>
        <div v-if="!metrics.traffic_hourly?.length" class="flex-1 flex items-center justify-center h-full text-xs text-gray-400">
          {{ t('monitoring.noData') }}
        </div>
      </div>

      <!-- Status distribution -->
      <div class="flex gap-4 mt-3 text-xs text-gray-500">
        <span v-for="(count, group) in metrics.status_distribution ?? {}" :key="group"
              :class="{
                'text-emerald-600': group === '2xx',
                'text-blue-600': group === '3xx',
                'text-amber-600': group === '4xx',
                'text-red-600': group === '5xx',
              }">
          {{ group }}: <b>{{ count }}</b>
        </span>
        <span class="ml-auto text-gray-400">{{ t('monitoring.totalRequests') }}: {{ metrics.total_requests ?? 0 }}</span>
      </div>
    </div>

    <!-- Sentry Issues -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">{{ t('monitoring.sentryTitle') }}</h3>
        <div v-if="sentry.configured && sentry.stats" class="flex gap-4 text-xs text-gray-500">
          <span v-if="sentry.stats.critical" class="text-red-600 font-semibold">
            {{ t('monitoring.sentryCritical') }}: {{ sentry.stats.critical }}
          </span>
          <span v-if="sentry.stats.errors" class="text-orange-600">
            {{ t('monitoring.sentryErrors') }}: {{ sentry.stats.errors }}
          </span>
          <span v-if="sentry.stats.warnings" class="text-amber-600">
            {{ t('monitoring.sentryWarnings') }}: {{ sentry.stats.warnings }}
          </span>
          <span class="text-gray-400">{{ t('monitoring.sentryTotalEvents') }}: {{ sentry.stats.total_events }}</span>
        </div>
      </div>

      <!-- Not configured -->
      <div v-if="!sentry.configured" class="text-xs text-amber-600 bg-amber-50 border border-amber-100 rounded-lg p-3">
        {{ t('monitoring.sentryNotConfigured') }}
      </div>

      <!-- Connection error -->
      <div v-else-if="sentry.error" class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg p-3">
        {{ t('monitoring.sentryConnectionError') }}: {{ sentry.error }}
      </div>

      <!-- No issues -->
      <div v-else-if="!sentry.issues?.length" class="text-xs text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-lg p-3 text-center">
        {{ t('monitoring.sentryNoIssues') }}
      </div>

      <!-- Issues list -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="text-left text-gray-400 border-b">
              <th class="pb-2 pr-2">{{ t('monitoring.sentryIssues') }}</th>
              <th class="pb-2 pr-2 text-center">{{ t('monitoring.sentryEvents') }}</th>
              <th class="pb-2 pr-2 text-center">{{ t('monitoring.sentryUsers') }}</th>
              <th class="pb-2 pr-2">{{ t('monitoring.sentryLastSeen') }}</th>
              <th class="pb-2"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="issue in sentry.issues" :key="issue.id" class="border-b border-gray-50 hover:bg-gray-50/50">
              <td class="py-2 pr-2 max-w-[400px]">
                <div class="flex items-center gap-1.5">
                  <span class="inline-block w-2 h-2 rounded-full shrink-0"
                        :class="{
                          'bg-red-500': issue.level === 'fatal',
                          'bg-orange-500': issue.level === 'error',
                          'bg-amber-400': issue.level === 'warning',
                          'bg-blue-400': issue.level === 'info',
                        }"></span>
                  <span class="font-medium text-gray-700 truncate">{{ issue.title }}</span>
                </div>
                <div v-if="issue.culprit" class="text-[10px] text-gray-400 font-mono mt-0.5 truncate pl-3.5">
                  {{ issue.culprit }}
                </div>
              </td>
              <td class="py-2 pr-2 text-center font-semibold text-gray-600">{{ issue.count }}</td>
              <td class="py-2 pr-2 text-center text-gray-500">{{ issue.userCount }}</td>
              <td class="py-2 pr-2 text-gray-400">{{ formatTime(issue.lastSeen) }}</td>
              <td class="py-2">
                <a v-if="issue.permalink" :href="issue.permalink" target="_blank" rel="noopener"
                   class="text-[10px] text-[#1BA97F] hover:underline whitespace-nowrap">
                  {{ t('monitoring.sentryOpenInSentry') }}
                </a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Errors + Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Error Log -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ t('monitoring.errors') }}</h3>

        <div v-if="errors.server_errors?.length" class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-gray-400 border-b">
                <th class="pb-2 pr-2">Path</th>
                <th class="pb-2 pr-2">Status</th>
                <th class="pb-2 pr-2">{{ t('monitoring.count') }}</th>
                <th class="pb-2">{{ t('monitoring.lastAt') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(err, i) in errors.server_errors" :key="i" class="border-b border-gray-50">
                <td class="py-1.5 pr-2 font-mono text-gray-600 max-w-[200px] truncate">{{ err.path }}</td>
                <td class="py-1.5 pr-2"><span class="text-red-600 font-semibold">{{ err.status_code }}</span></td>
                <td class="py-1.5 pr-2 font-semibold">{{ err.count }}</td>
                <td class="py-1.5 text-gray-400">{{ formatTime(err.last_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-xs text-gray-400 py-4 text-center">{{ t('monitoring.noErrors') }}</div>

        <!-- Log errors -->
        <div v-if="errors.log_errors?.length" class="mt-4">
          <div class="text-xs font-medium text-gray-500 mb-2">{{ t('monitoring.logErrors') }}</div>
          <div class="bg-gray-900 text-green-400 rounded-lg p-3 text-[10px] font-mono max-h-48 overflow-y-auto space-y-0.5">
            <div v-for="(line, i) in errors.log_errors" :key="i" class="break-all">{{ line }}</div>
          </div>
        </div>
      </div>

      <!-- Activity Feed -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ t('monitoring.activity') }}</h3>

        <!-- Top endpoints -->
        <div v-if="activity.top_endpoints?.length" class="mb-4">
          <div class="text-xs font-medium text-gray-500 mb-2">{{ t('monitoring.topEndpoints') }}</div>
          <div v-for="(ep, i) in activity.top_endpoints?.slice(0, 5)" :key="i"
               class="flex items-center gap-2 text-xs py-1">
            <span class="font-mono text-gray-500 w-12">{{ ep.method }}</span>
            <span class="font-mono text-gray-700 truncate flex-1">{{ ep.path }}</span>
            <span class="font-semibold text-gray-600">{{ ep.count }}</span>
          </div>
        </div>

        <!-- Recent activity_log -->
        <div class="text-xs font-medium text-gray-500 mb-2">{{ t('monitoring.recentActions') }}</div>
        <div v-if="activity.recent_activity?.length" class="space-y-1.5 max-h-64 overflow-y-auto">
          <div v-for="(act, i) in activity.recent_activity" :key="i"
               class="flex items-start gap-2 text-xs py-1 border-b border-gray-50">
            <span class="text-gray-400 shrink-0 w-28">{{ formatTime(act.created_at) }}</span>
            <span class="text-gray-700">{{ act.description }}</span>
          </div>
        </div>
        <div v-else class="text-xs text-gray-400 py-2">{{ t('monitoring.noActivity') }}</div>

        <div class="mt-3 text-xs text-gray-400">
          {{ t('monitoring.uniqueUsers') }}: <b class="text-gray-600">{{ activity.unique_users ?? 0 }}</b>
        </div>
      </div>
    </div>

    <!-- Queue Monitor -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ t('monitoring.queueTitle') }}</h3>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pending -->
        <div>
          <div class="text-xs font-medium text-gray-500 mb-2">{{ t('monitoring.pendingJobs') }}</div>
          <div v-if="queueData.pending?.length">
            <div v-for="(q, i) in queueData.pending" :key="i"
                 class="flex items-center justify-between text-sm py-1 border-b border-gray-50">
              <span class="font-mono text-gray-700">{{ q.queue }}</span>
              <span class="font-semibold text-gray-600">{{ q.count }}</span>
            </div>
          </div>
          <div v-else class="text-xs text-gray-400">{{ t('monitoring.emptyQueue') }}</div>
        </div>

        <!-- Failed -->
        <div>
          <div class="text-xs font-medium text-gray-500 mb-2">{{ t('monitoring.failedJobsTitle') }}</div>
          <div v-if="queueData.failed?.length" class="space-y-2 max-h-64 overflow-y-auto">
            <div v-for="job in queueData.failed" :key="job.id"
                 class="bg-red-50 border border-red-100 rounded-lg p-2.5 text-xs">
              <div class="flex items-center justify-between mb-1">
                <span class="font-semibold text-red-700">{{ job.job_name }}</span>
                <span class="text-gray-400">{{ formatTime(job.failed_at) }}</span>
              </div>
              <div class="text-red-600/80 font-mono text-[10px] mb-2 line-clamp-2">{{ job.exception }}</div>
              <div class="flex gap-2">
                <button @click="retryJob(job.id)"
                        class="px-2 py-0.5 bg-emerald-500 text-white rounded text-[10px] hover:bg-emerald-600">
                  {{ t('monitoring.retry') }}
                </button>
                <button @click="confirmDeleteJob(job.id)"
                        class="px-2 py-0.5 bg-red-500 text-white rounded text-[10px] hover:bg-red-600">
                  {{ t('monitoring.delete') }}
                </button>
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-gray-400">{{ t('monitoring.noFailedJobs') }}</div>
        </div>
      </div>
    </div>

    <!-- Modal: Confirm Delete Job -->
    <div v-if="deleteJobId" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-red-600 text-lg mb-2">{{ t('common.confirmDeleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ t('common.confirmDeleteMessage') }}</p>
        <div class="flex gap-3">
          <button @click="doDeleteJob"
            class="flex-1 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700">
            {{ t('common.confirmDeleteBtn') }}
          </button>
          <button @click="deleteJobId = null"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading overlay -->
    <div v-if="loading" class="fixed inset-0 bg-white/50 z-50 flex items-center justify-center">
      <div class="text-sm text-gray-500">{{ t('common.loading') }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { monitoringApi } from '@/api/monitoring';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const period = ref('24h');
const loading = ref(true);

const periodOptions = computed(() => [
  { value: '1h', label: t('monitoring.period1h') },
  { value: '6h', label: t('monitoring.period6h') },
  { value: '24h', label: t('monitoring.period24h') },
  { value: '7d', label: t('monitoring.period7d') },
  { value: '30d', label: t('monitoring.period30d') },
]);

const health = ref({});
const alerts = ref({ count: 0, level: 'ok', items: [] });
const metrics = ref({});
const errors = ref({});
const activity = ref({});
const queueData = ref({ pending: [], failed: [] });
const sentry = ref({ configured: false, issues: [], stats: {} });

const maxTraffic = computed(() => {
  if (!metrics.value.traffic_hourly?.length) return 1;
  return Math.max(...metrics.value.traffic_hourly.map(b => b.total)) || 1;
});

function barHeight(value, max) {
  if (!value || !max) return 0;
  return Math.max(1, Math.round((value / max) * 112));
}

function barTooltip(bar) {
  const hour = bar.hour ? new Date(bar.hour).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' }) : '';
  return `${hour}: ${bar.total} req (${bar.ok} ok, ${bar.errors} err)`;
}

function formatTime(dt) {
  if (!dt) return '';
  return new Date(dt).toLocaleString('ru-RU', {
    day: '2-digit', month: '2-digit',
    hour: '2-digit', minute: '2-digit'
  });
}

async function loadAll() {
  loading.value = true;
  try {
    const [h, a, m, e, act, q, s] = await Promise.all([
      monitoringApi.health(),
      monitoringApi.alerts(),
      monitoringApi.metrics(period.value),
      monitoringApi.errors(period.value),
      monitoringApi.activity(period.value),
      monitoringApi.queue(),
      monitoringApi.sentry(period.value),
    ]);
    health.value = h.data.data;
    alerts.value = a.data.data;
    metrics.value = m.data.data;
    errors.value = e.data.data;
    activity.value = act.data.data;
    queueData.value = q.data.data;
    sentry.value = s.data.data;
  } catch {
    /* silent */
  } finally {
    loading.value = false;
  }
}

async function loadAlerts() {
  try {
    const { data } = await monitoringApi.alerts();
    alerts.value = data.data;
  } catch { /* silent */ }
}

async function retryJob(id) {
  try {
    await monitoringApi.retryJob(id);
    queueData.value.failed = queueData.value.failed.filter(j => j.id !== id);
  } catch { /* silent */ }
}

const deleteJobId = ref(null);

function confirmDeleteJob(id) {
  deleteJobId.value = id;
}

async function doDeleteJob() {
  const id = deleteJobId.value;
  deleteJobId.value = null;
  try {
    await monitoringApi.deleteJob(id);
    queueData.value.failed = queueData.value.failed.filter(j => j.id !== id);
  } catch { /* silent */ }
}

let mainInterval, alertInterval;

onMounted(() => {
  loadAll();
  mainInterval = setInterval(loadAll, 60000);
  alertInterval = setInterval(loadAlerts, 30000);
});

onUnmounted(() => {
  clearInterval(mainInterval);
  clearInterval(alertInterval);
});

// Inline HealthCard component
const HealthCard = {
  name: 'HealthCard',
  props: {
    label: String,
    value: String,
    color: { type: String, default: 'green' },
  },
  template: `
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
      <div class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">{{ label }}</div>
      <div class="text-lg font-bold" :class="{
        'text-emerald-600': color === 'green',
        'text-amber-500': color === 'yellow',
        'text-red-500': color === 'red',
      }">{{ value }}</div>
    </div>
  `,
};
</script>

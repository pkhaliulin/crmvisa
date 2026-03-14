<template>
  <div class="bg-white rounded-xl border border-gray-100 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-800 text-sm">{{ t('crm.aiDash.title') }}</h3>
      <button @click="refresh" :disabled="loading"
        class="text-xs text-blue-600 hover:text-blue-700 px-2.5 py-1 rounded-lg hover:bg-blue-50 font-medium transition-colors disabled:opacity-50">
        {{ loading ? t('crm.aiDash.loading') : t('crm.aiDash.refresh') }}
      </button>
    </div>

    <!-- No data -->
    <p v-if="!data && !loading" class="text-sm text-gray-400 py-6 text-center">
      {{ t('crm.aiDash.noData') }}
    </p>

    <!-- Loading spinner -->
    <div v-if="loading && !data" class="flex items-center justify-center py-6">
      <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Data -->
    <div v-if="data">
      <!-- Top row: ring + risk + submission -->
      <div class="flex items-center gap-4 mb-4">
        <!-- Completeness ring -->
        <div class="relative w-16 h-16 shrink-0">
          <svg class="w-16 h-16 -rotate-90" viewBox="0 0 64 64">
            <circle cx="32" cy="32" r="28" fill="none" stroke="#f3f4f6" stroke-width="4" />
            <circle cx="32" cy="32" r="28" fill="none"
              :stroke="ringColor" stroke-width="4" stroke-linecap="round"
              :stroke-dasharray="circumference"
              :stroke-dashoffset="circumference - (circumference * completeness / 100)" />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <span class="text-sm font-bold" :class="ringTextClass">{{ completeness }}%</span>
          </div>
        </div>

        <div class="flex-1 space-y-2">
          <!-- Risk level badge -->
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">{{ t('crm.aiDash.riskLevel') }}:</span>
            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', riskBadgeClass]">
              {{ riskLabel }}
            </span>
          </div>
          <!-- Submission ready -->
          <div class="flex items-center gap-2">
            <div :class="['w-2 h-2 rounded-full', data.submission_ready ? 'bg-green-500' : 'bg-gray-300']"></div>
            <span :class="['text-xs font-medium', data.submission_ready ? 'text-green-700' : 'text-gray-400']">
              {{ data.submission_ready ? t('crm.aiDash.submissionReady') : t('crm.aiDash.notReady') }}
            </span>
          </div>
        </div>
      </div>

      <!-- Metrics row -->
      <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="text-center p-2 rounded-lg bg-red-50">
          <p class="text-lg font-bold text-red-600">{{ criticalCount }}</p>
          <p class="text-[10px] text-red-500 leading-tight">{{ t('crm.aiDash.missingRequired') }}</p>
        </div>
        <div class="text-center p-2 rounded-lg bg-yellow-50">
          <p class="text-lg font-bold text-yellow-600">{{ lowConfCount }}</p>
          <p class="text-[10px] text-yellow-600 leading-tight">{{ t('crm.aiDash.lowConfidence') }}</p>
        </div>
        <div class="text-center p-2 rounded-lg bg-gray-50">
          <p class="text-lg font-bold text-gray-700">{{ stopCount }}</p>
          <p class="text-[10px] text-gray-500 leading-tight">{{ t('crm.aiDash.stopFactorsLabel') }}</p>
        </div>
      </div>

      <!-- Issues list -->
      <div v-if="hasIssues" class="space-y-3">
        <!-- Critical missing -->
        <div v-if="criticalCount > 0">
          <p class="text-[10px] uppercase tracking-widest font-bold text-red-500 mb-1">{{ t('crm.aiDash.missingRequired') }}</p>
          <div class="flex flex-wrap gap-1">
            <span v-for="doc in data.critical_missing" :key="doc"
              class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">
              {{ doc }}
            </span>
          </div>
        </div>

        <!-- Mismatches -->
        <div v-if="data.mismatches?.length">
          <p class="text-[10px] uppercase tracking-widest font-bold text-yellow-600 mb-1">{{ t('crm.aiDash.mismatches') }}</p>
          <div class="space-y-1">
            <div v-for="(m, idx) in data.mismatches" :key="idx"
              class="flex items-start gap-1.5 text-xs text-yellow-700">
              <svg class="w-3.5 h-3.5 shrink-0 mt-0.5 text-yellow-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <span>{{ m.description || m }}</span>
            </div>
          </div>
        </div>

        <!-- Stop factors -->
        <div v-if="stopCount > 0">
          <p class="text-[10px] uppercase tracking-widest font-bold text-red-600 mb-1">{{ t('crm.aiDash.stopFactorsLabel') }}</p>
          <div class="space-y-1">
            <div v-for="(sf, idx) in data.stop_factors" :key="idx"
              class="text-xs px-3 py-2 rounded-lg bg-red-50 border border-red-100 text-red-700">
              {{ sf.description || sf }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { casesApi } from '@/api/cases';

const props = defineProps({
  caseId: { type: String, required: true },
});

const { t } = useI18n();

const data = ref(null);
const loading = ref(false);

const circumference = 2 * Math.PI * 28; // r=28

const completeness = computed(() => data.value?.completeness_percent ?? 0);

const ringColor = computed(() => {
  const v = completeness.value;
  if (v >= 80) return '#22c55e';
  if (v >= 50) return '#eab308';
  return '#ef4444';
});

const ringTextClass = computed(() => {
  const v = completeness.value;
  if (v >= 80) return 'text-green-600';
  if (v >= 50) return 'text-yellow-600';
  return 'text-red-600';
});

const riskBadgeClass = computed(() => {
  const level = data.value?.risk_level;
  const map = {
    minimal:  'bg-green-100 text-green-700',
    low:      'bg-blue-100 text-blue-700',
    medium:   'bg-yellow-100 text-yellow-700',
    critical: 'bg-red-100 text-red-700',
  };
  return map[level] || 'bg-gray-100 text-gray-600';
});

const riskLabel = computed(() => {
  const level = data.value?.risk_level;
  const map = {
    minimal:  t('crm.aiDash.riskMinimal'),
    low:      t('crm.aiDash.riskLow'),
    medium:   t('crm.aiDash.riskMedium'),
    critical: t('crm.aiDash.riskCritical'),
  };
  return map[level] || level;
});

const criticalCount = computed(() => data.value?.critical_missing?.length ?? 0);
const lowConfCount = computed(() => data.value?.low_confidence_docs?.length ?? 0);
const stopCount = computed(() => data.value?.stop_factors?.length ?? 0);
const hasIssues = computed(() => criticalCount.value > 0 || lowConfCount.value > 0 || stopCount.value > 0 || data.value?.mismatches?.length > 0);

async function refresh() {
  loading.value = true;
  try {
    const res = await casesApi.aiRiskScore(props.caseId);
    data.value = res.data?.data ?? res.data;
  } catch (e) {
    // AI risk fetch failed silently
  } finally {
    loading.value = false;
  }
}

defineExpose({ refresh });

onMounted(() => {
  refresh();
});
</script>

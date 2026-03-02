<template>
  <div class="space-y-5">
    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <template v-else>
      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
          <div class="text-2xl font-bold text-[#0A1F44]">{{ stats.avg_processing_days ?? '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.avgProcessingDays') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
          <div class="text-2xl font-bold text-[#0A1F44]">{{ totalCases }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.totalCases') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 p-4 text-center">
          <div class="text-2xl font-bold text-[#0A1F44]">{{ totalLeads }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.totalLeads') }}</div>
        </div>
      </div>

      <!-- Cases by stage -->
      <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h4 class="text-sm font-semibold text-[#0A1F44] mb-3">{{ $t('countryDetail.casesByStage') }}</h4>
        <div v-if="stats.cases_by_stage?.length" class="space-y-2">
          <div v-for="s in stats.cases_by_stage" :key="s.stage"
            class="flex items-center gap-3">
            <span class="text-sm text-gray-600 w-32">{{ s.stage }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-5 overflow-hidden">
              <div class="h-full bg-blue-500 rounded-full flex items-center justify-end pr-2"
                :style="{ width: barWidth(s.count) + '%' }">
                <span class="text-[10px] text-white font-bold">{{ s.count }}</span>
              </div>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('countryDetail.noData') }}</p>
      </div>

      <!-- Monthly leads -->
      <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h4 class="text-sm font-semibold text-[#0A1F44] mb-3">{{ $t('countryDetail.monthlyLeads') }}</h4>
        <div v-if="stats.monthly_leads?.length" class="flex items-end gap-1 h-32">
          <div v-for="m in stats.monthly_leads" :key="m.month"
            class="flex-1 flex flex-col items-center justify-end">
            <div class="bg-blue-400 rounded-t w-full min-h-[4px]"
              :style="{ height: leadBarHeight(m.count) + '%' }"></div>
            <span class="text-[9px] text-gray-400 mt-1 truncate w-full text-center">{{ m.month?.slice(5) }}</span>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('countryDetail.noData') }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ countryCode: String });

const loading = ref(true);
const stats   = ref({});

const totalCases = computed(() =>
  (stats.value.cases_by_stage || []).reduce((s, x) => s + x.count, 0)
);
const totalLeads = computed(() =>
  (stats.value.monthly_leads || []).reduce((s, x) => s + x.count, 0)
);

const maxCases = computed(() =>
  Math.max(...(stats.value.cases_by_stage || []).map(s => s.count), 1)
);
const maxLeads = computed(() =>
  Math.max(...(stats.value.monthly_leads || []).map(m => m.count), 1)
);

function barWidth(count) {
  return Math.max((count / maxCases.value) * 100, 5);
}
function leadBarHeight(count) {
  return Math.max((count / maxLeads.value) * 100, 3);
}

onMounted(async () => {
  try {
    const { data } = await ownerCountriesApi.stats(props.countryCode);
    stats.value = data.data;
  } finally {
    loading.value = false;
  }
});
</script>

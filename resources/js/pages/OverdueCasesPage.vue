<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.overdue.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('crm.overdue.subtitle') }}</p>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">{{ t('crm.overdue.loading') }}</div>

    <div v-else-if="cases.length === 0"
      class="text-center py-16 bg-white rounded-xl border border-gray-200">
      <p class="text-2xl mb-2">✓</p>
      <p class="text-gray-600 font-medium">{{ t('crm.overdue.empty') }}</p>
      <p class="text-sm text-gray-400 mt-1">{{ t('crm.overdue.allOnTime') }}</p>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">
          {{ t('crm.overdue.count', { n: cases.length }, cases.length) }}
        </span>
      </div>

      <div class="divide-y divide-gray-100">
        <div v-for="c in cases" :key="c.id"
          :class="['px-4 py-4 hover:bg-gray-50 transition-colors',
            c.severity === 'critical' ? 'border-l-4 border-red-500' : 'border-l-4 border-yellow-400']">
          <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3">
                <span :class="['text-xs font-bold px-2 py-0.5 rounded-full',
                  c.severity === 'critical'
                    ? 'bg-red-100 text-red-700'
                    : 'bg-yellow-100 text-yellow-700']">
                  {{ c.severity === 'critical' ? t('crm.overdue.critical') : t('crm.overdue.warning') }}
                </span>
                <span class="text-sm font-medium text-gray-900">
                  {{ c.client?.name }}
                </span>
                <span class="text-xs text-gray-400">{{ countryFlag(c.country_code) }} {{ countryName(c.country_code) }} / {{ visaTypeName(c.visa_type) }}</span>
              </div>

              <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-500">
                <span>{{ t('crm.overdue.stageLabel') }} <span class="font-medium text-gray-700">{{ stageLabel(c.stage) }}</span></span>
                <span v-if="c.assignee">{{ t('crm.overdue.managerLabel') }} <span class="font-medium text-gray-700">{{ c.assignee.name }}</span></span>
                <span>{{ t('crm.overdue.deadlineLabel') }} <span class="font-medium text-gray-700">{{ formatDate(c.critical_date) }}</span></span>
              </div>
            </div>

            <div class="text-right shrink-0 ml-4">
              <div :class="['text-2xl font-bold',
                c.severity === 'critical' ? 'text-red-600' : 'text-yellow-600']">
                +{{ c.days_overdue }}
              </div>
              <div class="text-xs text-gray-400">{{ t('crm.overdue.days') }}</div>
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
import api from '@/api/index';
import { useCountries } from '@/composables/useCountries';
import { formatDate } from '@/utils/format';

const { t } = useI18n();
const { countryName, countryFlag, visaTypeName } = useCountries();

const loading = ref(true);
const cases = ref([]);

const stageLabels = computed(() => ({
  lead: t('crm.stages.lead'), qualification: t('crm.stages.qualification'), documents: t('crm.stages.documents'),
  translation: t('crm.stages.translation'), appointment: t('crm.stages.appointment'), review: t('crm.stages.review'), result: t('crm.stages.result'),
}));

function stageLabel(s) { return stageLabels.value[s] || s; }


onMounted(async () => {
  try {
    const res = await api.get('/reports/overdue');
    cases.value = res.data.data || [];
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});
</script>

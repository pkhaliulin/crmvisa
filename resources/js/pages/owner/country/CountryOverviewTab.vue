<template>
  <div class="space-y-5">
    <!-- Основная информация -->
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <h4 class="text-sm font-semibold text-gray-500 mb-3">{{ $t('countryDetail.generalInfo') }}</h4>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.countryCode') }}</dt>
            <dd class="font-mono font-bold">{{ country.country_code }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.name') }}</dt>
            <dd class="font-medium">{{ country.name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.nameUz') }}</dt>
            <dd>{{ country.name_uz || '---' }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.status') }}</dt>
            <dd>
              <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="country.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
                {{ country.is_active ? $t('countryDetail.active') : $t('countryDetail.inactive') }}
              </span>
            </dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.riskLevel') }}</dt>
            <dd>
              <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="riskClass">
                {{ riskLabel }}
              </span>
            </dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.commission') }}</dt>
            <dd>{{ country.commission_rate ?? 5 }}%</dd>
          </div>
        </dl>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 p-4">
        <h4 class="text-sm font-semibold text-gray-500 mb-3">{{ $t('countryDetail.visaTypes') }}</h4>
        <div class="flex flex-wrap gap-2 mb-4">
          <span v-for="vt in (country.visa_types || [])" :key="vt"
            class="text-xs px-2 py-1 bg-blue-50 text-blue-700 rounded-lg">
            {{ vt }}
          </span>
          <span v-if="!(country.visa_types?.length)" class="text-sm text-gray-400">---</span>
        </div>
        <h4 class="text-sm font-semibold text-gray-500 mb-3 mt-4">{{ $t('countryDetail.submissionType') }}</h4>
        <span class="text-sm">{{ submissionLabel }}</span>
      </div>
    </div>

    <!-- Публичный скоринг -->
    <div class="bg-white rounded-xl border border-gray-100 p-4">
      <h4 class="text-sm font-semibold text-gray-500 mb-3">{{ $t('countryDetail.publicScoring') }}</h4>
      <div class="grid grid-cols-4 gap-4 text-center">
        <div v-for="w in weights" :key="w.key" class="p-3 bg-gray-50 rounded-lg">
          <div class="text-2xl font-bold" :class="parseFloat(country[w.key]) >= 0.30 ? 'text-blue-700' : 'text-gray-500'">
            {{ ((parseFloat(country[w.key]) || 0) * 100).toFixed(0) }}%
          </div>
          <div class="text-xs text-gray-500 mt-1">{{ w.label }}</div>
        </div>
      </div>
      <div class="mt-3 flex gap-6 text-sm text-gray-600">
        <span>{{ $t('countryDetail.minIncome') }}: <strong>${{ country.min_monthly_income_usd }}</strong></span>
        <span>{{ $t('countryDetail.minScore') }}: <strong>{{ country.min_score }}%</strong></span>
      </div>
    </div>

    <!-- Общие сроки (из portal_countries) -->
    <div class="bg-white rounded-xl border border-gray-100 p-4">
      <h4 class="text-sm font-semibold text-gray-500 mb-3">{{ $t('countryDetail.generalTimeline') }}</h4>
      <div class="grid grid-cols-4 gap-4 text-center">
        <div class="p-3 bg-blue-50 rounded-lg">
          <div class="text-xl font-bold text-blue-700">{{ country.processing_days_standard ?? '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.standardDays') }}</div>
        </div>
        <div class="p-3 bg-green-50 rounded-lg">
          <div class="text-xl font-bold text-green-700">{{ country.processing_days_expedited ?? '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.expeditedDays') }}</div>
        </div>
        <div class="p-3 bg-orange-50 rounded-lg">
          <div class="text-xl font-bold text-orange-700">{{ country.appointment_wait_days ?? '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.appointmentWait') }}</div>
        </div>
        <div class="p-3 bg-gray-50 rounded-lg">
          <div class="text-xl font-bold text-gray-700">{{ country.buffer_days_recommended ?? '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.bufferDays') }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const props = defineProps({ country: Object });

const weights = computed(() => [
  { key: 'weight_finance', label: t('countryDetail.finances') },
  { key: 'weight_ties',    label: t('countryDetail.ties') },
  { key: 'weight_travel',  label: t('countryDetail.travelHistory') },
  { key: 'weight_profile', label: t('countryDetail.profile') },
]);

const riskClass = computed(() => {
  const r = props.country.risk_level;
  if (r === 'low') return 'bg-green-50 text-green-700';
  if (r === 'high') return 'bg-red-50 text-red-700';
  return 'bg-yellow-50 text-yellow-700';
});

const riskLabel = computed(() => {
  const r = props.country.risk_level;
  if (r === 'low') return t('countryDetail.riskLow');
  if (r === 'high') return t('countryDetail.riskHigh');
  return t('countryDetail.riskMedium');
});

const submissionLabel = computed(() => {
  const map = {
    embassy_direct: t('countryDetail.embassyDirect'),
    visa_center:    t('countryDetail.visaCenter'),
    both:           t('countryDetail.both'),
    online:         t('countryDetail.online'),
  };
  return map[props.country.submission_type] || props.country.submission_type || '---';
});
</script>

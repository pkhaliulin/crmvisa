<template>
  <div class="space-y-5">
    <!-- Loading -->
    <div v-if="loading" class="space-y-4">
      <div class="h-8 bg-gray-100 rounded animate-pulse w-64"></div>
      <div class="h-32 bg-gray-100 rounded-xl animate-pulse"></div>
    </div>

    <template v-else-if="detail">
      <!-- Header -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
          <button @click="$router.push({ name: 'owner.countries' })"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          <div class="flex items-center gap-3">
            <span class="text-3xl">{{ country.flag_emoji }}</span>
            <div>
              <h1 class="text-xl font-bold text-[#0A1F44]">{{ country.name }}</h1>
              <span class="text-xs text-gray-400 font-mono">{{ country.country_code }}</span>
            </div>
          </div>
        </div>
        <!-- Metric chips -->
        <div class="flex gap-3">
          <MetricChip :label="$t('countryDetail.cases')" :value="stats.total_cases" color="blue" />
          <MetricChip :label="$t('countryDetail.visaTypes')" :value="stats.visa_types_count" color="purple" />
          <MetricChip :label="$t('countryDetail.documents')" :value="stats.documents_count" color="green" />
          <MetricChip :label="$t('countryDetail.minIncome')" :value="'$' + country.min_monthly_income_usd" color="orange" />
          <MetricChip :label="$t('countryDetail.risk')" :value="riskBadge" :color="riskColor" />
        </div>
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200">
        <nav class="flex gap-6 overflow-x-auto">
          <button v-for="tab in tabs" :key="tab.id"
            @click="activeTab = tab.id"
            class="pb-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeTab === tab.id
              ? 'border-[#0A1F44] text-[#0A1F44]'
              : 'border-transparent text-gray-400 hover:text-gray-600'">
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Tab content -->
      <CountryOverviewTab v-if="activeTab === 'overview'" :country="country" :visa-settings="visaSettings" @updated="reload" @go-tab="activeTab = $event" />
      <CountryVisaTypesTab v-else-if="activeTab === 'visa-types'" :country-code="country.country_code" :visa-settings="visaSettings" @updated="reload" />
      <CountrySubmissionTab v-else-if="activeTab === 'submission'" :country="country" @updated="reload" />
      <CountryVisaCenterTab v-else-if="activeTab === 'visa-center'" :country="country" @updated="reload" />
      <CountryEmbassyTab v-else-if="activeTab === 'embassy'" :country="country" @updated="reload" />
      <CountryFinanceTab v-else-if="activeTab === 'finance'" :country="country" @updated="reload" />
      <CountryScoringTab v-else-if="activeTab === 'scoring'" :country="country" :country-code="country.country_code" @updated="reload" />
      <CountryDocumentsTab v-else-if="activeTab === 'documents'" :country-code="country.country_code" @updated="reload" />
      <CountryAnalyticsTab v-else-if="activeTab === 'analytics'" :country-code="country.country_code" />
    </template>

    <!-- Error -->
    <div v-else class="text-center py-16 text-gray-400">
      {{ $t('countryDetail.notFound') }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';
import CountryOverviewTab from './country/CountryOverviewTab.vue';
import CountryEmbassyTab from './country/CountryEmbassyTab.vue';
import CountryVisaTypesTab from './country/CountryVisaTypesTab.vue';
import CountryDocumentsTab from './country/CountryDocumentsTab.vue';
import CountryScoringTab from './country/CountryScoringTab.vue';
import CountryAnalyticsTab from './country/CountryAnalyticsTab.vue';
import CountrySubmissionTab from './country/CountrySubmissionTab.vue';
import CountryVisaCenterTab from './country/CountryVisaCenterTab.vue';
import CountryFinanceTab from './country/CountryFinanceTab.vue';

const { t } = useI18n();
const route = useRoute();

const loading   = ref(true);
const detail    = ref(null);
const activeTab = ref('overview');

const country      = computed(() => detail.value?.country ?? {});
const stats        = computed(() => detail.value?.stats ?? {});
const visaSettings = computed(() => detail.value?.visa_settings ?? []);

const riskBadge = computed(() => {
  const r = country.value.risk_level;
  if (r === 'low') return t('countryDetail.riskLow');
  if (r === 'high') return t('countryDetail.riskHigh');
  return t('countryDetail.riskMedium');
});
const riskColor = computed(() => {
  const r = country.value.risk_level;
  if (r === 'low') return 'green';
  if (r === 'high') return 'red';
  return 'orange';
});

const tabs = computed(() => [
  { id: 'overview',     label: t('countryDetail.tabOverview') },
  { id: 'visa-types',   label: t('countryDetail.tabVisaTypes') },
  { id: 'submission',   label: t('countryDetail.tabSubmission') },
  { id: 'visa-center',  label: t('countryDetail.tabVisaCenter') },
  { id: 'embassy',      label: t('countryDetail.tabEmbassy') },
  { id: 'finance',      label: t('countryDetail.tabFinance') },
  { id: 'scoring',      label: t('countryDetail.tabScoring') },
  { id: 'documents',    label: t('countryDetail.tabDocuments') },
  { id: 'analytics',    label: t('countryDetail.tabAnalytics') },
]);

async function loadDetail() {
  loading.value = true;
  try {
    const { data } = await ownerCountriesApi.detail(route.params.code);
    detail.value = data.data;
  } catch {
    detail.value = null;
  } finally {
    loading.value = false;
  }
}

async function reload() {
  await loadDetail();
}

onMounted(loadDetail);

// Inline MetricChip component
const MetricChip = {
  props: ['label', 'value', 'color'],
  template: `
    <div class="px-3 py-1.5 rounded-lg text-center"
      :class="{
        'bg-blue-50':   color === 'blue',
        'bg-purple-50': color === 'purple',
        'bg-green-50':  color === 'green',
        'bg-orange-50': color === 'orange',
        'bg-red-50':    color === 'red',
      }">
      <div class="text-sm font-bold"
        :class="{
          'text-blue-700':   color === 'blue',
          'text-purple-700': color === 'purple',
          'text-green-700':  color === 'green',
          'text-orange-700': color === 'orange',
          'text-red-700':    color === 'red',
        }">{{ value }}</div>
      <div class="text-[10px] text-gray-500">{{ label }}</div>
    </div>
  `,
};
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('crm.agencyCountries.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('crm.agencyCountries.subtitle') }}</p>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-gray-400">{{ t('crm.agencyCountries.countOf', { n: filtered.length, total: countries.length }) }}</span>
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none bg-white border border-gray-200 rounded-lg px-3 py-1.5">
          <input type="checkbox" v-model="onlyOurs"
            class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" />
          {{ t('crm.agencyCountries.onlyOurs') }}
        </label>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="flex flex-wrap gap-2">
      <div class="relative flex-1 min-w-[200px]">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="search" type="text" :placeholder="t('crm.agencyCountries.searchPlaceholder')"
          class="w-full pl-8 pr-3 py-2 rounded-lg border border-gray-200 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
      </div>
      <SearchSelect
        v-model="filterRegime"
        :items="regimeFilterItems"
        :placeholder="t('crm.agencyCountries.allRegimes')"
        allow-all
        :all-label="t('crm.agencyCountries.allRegimes')"
        compact
      />
      <SearchSelect
        v-model="filterContinent"
        :items="continentFilterItems"
        :placeholder="t('crm.agencyCountries.allContinents')"
        allow-all
        :all-label="t('crm.agencyCountries.allContinents')"
        compact
      />
    </div>

    <!-- Загрузка -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Карточки стран -->
      <div v-if="filtered.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="c in filtered" :key="c.country_code"
          class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:shadow-md transition-all cursor-pointer"
          :class="workCodes.includes(c.country_code) ? 'border-l-4 border-l-green-400' : ''"
          @click="router.push({ name: 'countries.show', params: { code: c.country_code } })">

          <!-- Header -->
          <div class="flex items-center gap-3 mb-3">
            <span class="text-3xl leading-none">{{ codeToFlag(c.country_code) }}</span>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors truncate">
                {{ localName(c) }}
              </p>
              <p class="text-xs text-gray-400">{{ c.continent || '--' }}</p>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full shrink-0"
              :class="regimeBadge(c.visa_regime)">
              {{ regimeLabel(c.visa_regime) }}
            </span>
          </div>

          <!-- Info -->
          <div class="flex items-center gap-4 text-xs text-gray-500 mb-3">
            <span v-if="c.visa_fee">
              <span class="font-semibold text-gray-700">${{ c.visa_fee }}</span> {{ t('crm.agencyCountries.fee') }}
            </span>
            <span v-if="c.processing_days_avg">
              {{ t('crm.agencyCountries.processingDays', { n: c.processing_days_avg }) }}
            </span>
            <span v-if="c.risk_level" class="ml-auto">
              <span class="font-semibold" :class="{
                'text-green-600': c.risk_level === 'low',
                'text-yellow-600': c.risk_level === 'medium',
                'text-red-600': c.risk_level === 'high',
              }">{{ { low: t('crm.agencyCountries.riskLow'), medium: t('crm.agencyCountries.riskMedium'), high: t('crm.agencyCountries.riskHigh') }[c.risk_level] }}</span> {{ t('crm.agencyCountries.risk') }}
            </span>
          </div>

          <!-- Toggle работаем/не работаем -->
          <div class="flex items-center justify-between pt-3 border-t border-gray-100" @click.stop>
            <span class="text-xs" :class="workCodes.includes(c.country_code) ? 'text-green-600 font-medium' : 'text-gray-400'">
              {{ workCodes.includes(c.country_code) ? t('crm.agencyCountries.weWork') : t('crm.agencyCountries.weDontWork') }}
            </span>
            <button
              @click="toggleWork(c.country_code)"
              :disabled="togglingCode === c.country_code"
              class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none"
              :class="workCodes.includes(c.country_code) ? 'bg-green-500' : 'bg-gray-300'">
              <span class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow-sm transition-transform duration-200"
                :class="workCodes.includes(c.country_code) ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="bg-white rounded-xl border border-gray-200 p-8 text-center text-gray-400">
        <p class="text-sm">{{ t('crm.agencyCountries.empty') }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { codeToFlag, countryName } from '@/utils/countries';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const router = useRouter();
const loading = ref(true);
const countries = ref([]);
const workCodes = ref([]);
const togglingCode = ref(null);

const search = ref('');
const filterRegime = ref('');
const filterContinent = ref('');
const onlyOurs = ref(false);

const continents = ['Asia', 'Europe', 'Africa', 'North America', 'South America', 'Oceania'];

const regimeFilterItems = computed(() => [
  { value: 'visa_free', label: t('crm.agencyCountries.visaFree') },
  { value: 'visa_on_arrival', label: t('crm.agencyCountries.visaOnArrival') },
  { value: 'evisa', label: t('crm.agencyCountries.evisa') },
  { value: 'visa_required', label: t('crm.agencyCountries.visaRequired') },
]);
const continentFilterItems = continents.map(c => ({ value: c, label: c }));

function localName(c) {
  return countryName(c.country_code) || c.name || c.country_code;
}

function regimeBadge(regime) {
  return {
    visa_free:       'bg-green-50 text-green-700',
    visa_on_arrival: 'bg-blue-50 text-blue-600',
    evisa:           'bg-cyan-50 text-cyan-700',
    visa_required:   'bg-red-50 text-red-600',
  }[regime] || 'bg-gray-100 text-gray-500';
}

function regimeLabel(regime) {
  return {
    visa_free:       t('crm.agencyCountries.visaFree'),
    visa_on_arrival: t('crm.agencyCountries.visaOnArrival'),
    evisa:           t('crm.agencyCountries.evisa'),
    visa_required:   t('crm.agencyCountries.visaRequired'),
  }[regime] || regime;
}

const filtered = computed(() => {
  let list = countries.value;
  if (onlyOurs.value) list = list.filter(c => workCodes.value.includes(c.country_code));
  if (filterRegime.value) list = list.filter(c => c.visa_regime === filterRegime.value);
  if (filterContinent.value) list = list.filter(c => c.continent === filterContinent.value);
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(c =>
      (localName(c)).toLowerCase().includes(q) ||
      c.country_code.toLowerCase().includes(q)
    );
  }
  return list;
});

async function toggleWork(code) {
  togglingCode.value = code;
  try {
    if (workCodes.value.includes(code)) {
      await api.delete(`/agency/work-countries/${code}`);
      workCodes.value = workCodes.value.filter(c => c !== code);
    } else {
      await api.post(`/agency/work-countries/${code}`);
      workCodes.value.push(code);
    }
  } catch { /* ignore */ } finally {
    togglingCode.value = null;
  }
}

onMounted(async () => {
  try {
    const [countriesRes, workRes] = await Promise.all([
      api.get('/countries'),
      api.get('/agency/work-countries').catch(() => null),
    ]);
    countries.value = (countriesRes.data.data ?? []).sort((a, b) =>
      (localName(a)).localeCompare(localName(b), 'uz')
    );
    workCodes.value = (workRes?.data?.data ?? []).map(c => c.country_code);
  } catch { /* ignore */ } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-5">
    <!-- Основная информация -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-4">{{ $t('countryDetail.generalInfo') }}</h4>

      <div v-if="!editing" class="space-y-3">
        <dl class="grid grid-cols-3 gap-4 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.name') }}</dt>
            <dd class="font-medium">{{ country.name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('countryDetail.continent') }}</dt>
            <dd class="font-medium">{{ country.continent || '---' }}</dd>
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
        </dl>
        <div class="flex items-center gap-4 text-sm pt-1">
          <span class="text-gray-500">{{ $t('countryDetail.commission') }}:</span>
          <span class="font-bold">{{ country.commission_rate ?? 5 }}%</span>
          <span v-if="country.commission_description" class="text-gray-400 text-xs">{{ country.commission_description }}</span>
        </div>
        <button @click="startEdit" class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600 mt-2">
          {{ $t('countryDetail.edit') }}
        </button>
      </div>

      <form v-else @submit.prevent="saveOverview" class="space-y-3">
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.name') }}</label>
            <input v-model="form.name" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.continent') }}</label>
            <select v-model="form.continent" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="">---</option>
              <option v-for="c in continents" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.status') }}</label>
            <label class="flex items-center gap-2 text-sm mt-1">
              <input type="checkbox" v-model="form.is_active" class="rounded" />
              {{ $t('countryDetail.active') }}
            </label>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.commission') }} (%)</label>
            <input v-model.number="form.commission_rate" type="number" min="0" max="100" step="0.1"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.commissionDesc') }}</label>
            <input v-model="form.commission_description" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
        </div>
        <div class="flex gap-3 pt-2">
          <button type="submit" :disabled="saving"
            class="px-5 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
            {{ saving ? $t('common.loading') : $t('common.save') }}
          </button>
          <button type="button" @click="editing = false"
            class="px-5 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">
            {{ $t('common.cancel') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Индикаторы заполненности -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-4">{{ $t('countryDetail.completeness') }}</h4>
      <div class="grid grid-cols-3 gap-3">
        <button v-for="ind in indicators" :key="ind.tab"
          @click="$emit('goTab', ind.tab)"
          class="flex items-center gap-3 p-3 rounded-lg border transition-colors hover:bg-gray-50 text-left"
          :class="ind.filled ? 'border-green-200 bg-green-50/50' : 'border-gray-100'">
          <span class="w-3 h-3 rounded-full flex-shrink-0"
            :class="ind.filled ? 'bg-green-500' : 'bg-gray-300'"></span>
          <div>
            <div class="text-sm font-medium text-gray-800">{{ ind.label }}</div>
            <div class="text-[11px] text-gray-400">{{ ind.filled ? $t('countryDetail.filled') : $t('countryDetail.notFilled') }}</div>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object, visaSettings: Array });
const emit = defineEmits(['updated', 'goTab']);

const editing = ref(false);
const saving  = ref(false);
const form    = reactive({});

const continents = ['Europe', 'Asia', 'North America', 'South America', 'Africa', 'Oceania'];

const indicators = computed(() => [
  {
    tab: 'visa-types',
    label: t('countryDetail.tabVisaTypes'),
    filled: (props.visaSettings?.length ?? 0) > 0,
  },
  {
    tab: 'submission',
    label: t('countryDetail.tabSubmission'),
    filled: (props.country.submission_types?.length ?? 0) > 0,
  },
  {
    tab: 'embassy',
    label: t('countryDetail.tabEmbassy'),
    filled: props.country.has_embassy && !!props.country.embassy_name,
  },
  {
    tab: 'visa-center',
    label: t('countryDetail.tabVisaCenter'),
    filled: props.country.has_visa_center && !!props.country.visa_center_name,
  },
  {
    tab: 'finance',
    label: t('countryDetail.tabFinance'),
    filled: (props.country.min_monthly_income_usd ?? 0) > 0,
  },
  {
    tab: 'scoring',
    label: t('countryDetail.tabScoring'),
    filled: (parseFloat(props.country.weight_finance) || 0) > 0,
  },
]);

function startEdit() {
  Object.assign(form, {
    name: props.country.name ?? '',
    continent: props.country.continent ?? '',
    is_active: props.country.is_active ?? true,
    commission_rate: props.country.commission_rate ?? 5,
    commission_description: props.country.commission_description ?? '',
  });
  editing.value = true;
}

async function saveOverview() {
  saving.value = true;
  try {
    await ownerCountriesApi.update(props.country.country_code, form);
    editing.value = false;
    emit('updated');
  } finally {
    saving.value = false;
  }
}
</script>

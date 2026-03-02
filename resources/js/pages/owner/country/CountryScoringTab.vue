<template>
  <div class="space-y-5">
    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <template v-else>
      <!-- Публичные веса -->
      <div class="bg-white rounded-xl border border-gray-100 p-5">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-sm font-semibold text-[#0A1F44]">{{ $t('countryDetail.publicWeights') }}</h4>
          <button v-if="!editing" @click="startEdit"
            class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
            {{ $t('countryDetail.edit') }}
          </button>
        </div>

        <div v-if="!editing">
          <div class="grid grid-cols-4 gap-4 text-center">
            <div v-for="w in publicWeights" :key="w.key" class="p-3 bg-gray-50 rounded-lg">
              <div class="text-2xl font-bold" :class="val(w.key) >= 0.30 ? 'text-blue-700' : 'text-gray-500'">
                {{ (val(w.key) * 100).toFixed(0) }}%
              </div>
              <div class="text-xs text-gray-500 mt-1">{{ w.label }}</div>
            </div>
          </div>
          <p class="text-xs text-gray-400 mt-2">
            {{ $t('countryDetail.weightsSum') }}:
            <strong :class="publicSum === 1 ? 'text-green-600' : 'text-red-500'">
              {{ publicSum.toFixed(2) }}
            </strong>
          </p>
        </div>

        <!-- Форма -->
        <form v-else @submit.prevent="save" class="space-y-3">
          <div class="grid grid-cols-4 gap-3">
            <div v-for="w in publicWeights" :key="w.key">
              <label class="text-xs text-gray-500 mb-1 block">{{ w.label }}</label>
              <input v-model.number="form[w.key]" type="number" min="0" max="1" step="0.05"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
          <p class="text-xs text-gray-400">
            {{ $t('countryDetail.weightsSum') }}:
            <strong :class="editSum === 1 ? 'text-green-600' : 'text-red-500'">
              {{ editSum.toFixed(2) }}
            </strong>
          </p>

          <div class="grid grid-cols-3 gap-3 pt-2">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.minIncome') }} ($)</label>
              <input v-model.number="form.min_monthly_income_usd" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.minScore') }} (%)</label>
              <input v-model.number="form.min_score" type="number" min="0" max="100"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.riskLevel') }}</label>
              <select v-model="form.risk_level"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="low">{{ $t('countryDetail.riskLow') }}</option>
                <option value="medium">{{ $t('countryDetail.riskMedium') }}</option>
                <option value="high">{{ $t('countryDetail.riskHigh') }}</option>
              </select>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="saving || editSum !== 1"
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

      <!-- Расширенные веса скоринга (scoring_country_weights) -->
      <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h4 class="text-sm font-semibold text-[#0A1F44] mb-3">{{ $t('countryDetail.scoringWeights') }}</h4>
        <div v-if="scoringData.weights?.length" class="space-y-2">
          <div v-for="w in scoringData.weights" :key="w.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg text-sm">
            <span class="text-gray-700 font-medium">{{ w.category }}</span>
            <span class="font-bold text-blue-700">{{ ((w.weight || 0) * 100).toFixed(0) }}%</span>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('countryDetail.noScoringWeights') }}</p>
      </div>

      <!-- Финансовые пороги -->
      <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h4 class="text-sm font-semibold text-[#0A1F44] mb-3">{{ $t('countryDetail.financialThresholds') }}</h4>
        <div v-if="scoringData.thresholds" class="grid grid-cols-3 gap-4 text-sm">
          <div v-for="(val, key) in scoringData.thresholds" :key="key" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-500">{{ key }}</div>
            <div class="font-bold text-gray-800 mt-1">{{ val }}</div>
          </div>
        </div>
        <p v-else class="text-sm text-gray-400">{{ $t('countryDetail.noThresholds') }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object, countryCode: String });
const emit  = defineEmits(['updated']);

const loading     = ref(true);
const editing     = ref(false);
const saving      = ref(false);
const scoringData = ref({});
const form        = reactive({});

const publicWeights = computed(() => [
  { key: 'weight_finance', label: t('countryDetail.finances') },
  { key: 'weight_ties',    label: t('countryDetail.ties') },
  { key: 'weight_travel',  label: t('countryDetail.travelHistory') },
  { key: 'weight_profile', label: t('countryDetail.profile') },
]);

function val(key) {
  return parseFloat(props.country?.[key]) || 0;
}

const publicSum = computed(() =>
  +(publicWeights.value.reduce((s, w) => s + val(w.key), 0).toFixed(2))
);

const editSum = computed(() =>
  +(publicWeights.value.reduce((s, w) => s + (form[w.key] ?? 0), 0).toFixed(2))
);

function startEdit() {
  for (const w of publicWeights.value) {
    form[w.key] = val(w.key);
  }
  form.min_monthly_income_usd = props.country.min_monthly_income_usd;
  form.min_score              = props.country.min_score;
  form.risk_level             = props.country.risk_level ?? 'medium';
  editing.value = true;
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateScoring(props.countryCode, form);
    editing.value = false;
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  try {
    const { data } = await ownerCountriesApi.scoring(props.countryCode);
    scoringData.value = data.data;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-5">
    <!-- Публичные веса -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-[#0A1F44] mb-4">{{ $t('countryDetail.publicWeights') }}</h4>

      <form @submit.prevent="save" class="space-y-4">
        <div class="bg-gray-50 rounded-lg overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-gray-500 text-xs uppercase">
                <th class="text-left px-4 py-2.5 font-medium">{{ $t('countryDetail.parameter') }}</th>
                <th class="text-left px-4 py-2.5 font-medium w-32">{{ $t('countryDetail.weight') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
              <tr v-for="w in publicWeights" :key="w.key">
                <td class="px-4 py-3 font-medium text-gray-800">{{ w.label }}</td>
                <td class="px-4 py-3">
                  <input v-model.number="form[w.key]" type="number" min="0" max="1" step="0.05"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <p class="text-xs text-gray-400">
          {{ $t('countryDetail.weightsSum') }}:
          <strong :class="editSum === 1 ? 'text-green-600' : 'text-red-500'">
            {{ editSum.toFixed(2) }}
          </strong>
          <span v-if="editSum !== 1" class="text-red-400 ml-2">{{ $t('countryDetail.weightsSumWarning') }}</span>
        </p>

        <!-- Расширенные веса -->
        <fieldset class="border border-gray-200 rounded-xl p-4">
          <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.extendedWeights') }}</legend>
          <div class="grid grid-cols-3 gap-4 mt-2">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.weightFinances') }}</label>
              <input v-model.number="form.weight_finances" type="number" min="0" max="1" step="0.05"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.weightVisaHistory') }}</label>
              <input v-model.number="form.weight_visa_history" type="number" min="0" max="1" step="0.05"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.weightSocialTies') }}</label>
              <input v-model.number="form.weight_social_ties" type="number" min="0" max="1" step="0.05"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
        </fieldset>

        <!-- Пороги и бонусы -->
        <fieldset class="border border-gray-200 rounded-xl p-4">
          <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.thresholdsAndBonuses') }}</legend>
          <div class="grid grid-cols-3 gap-4 mt-2">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.minScore') }} (0-100)</label>
              <input v-model.number="form.min_score" type="number" min="0" max="100"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.destinationBonus') }}</label>
              <input v-model.number="form.destination_score_bonus" type="number" min="-50" max="50"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.minIncome') }} ($)</label>
              <input v-model.number="form.min_monthly_income_usd" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
        </fieldset>

        <!-- Правило при незаполненных данных -->
        <div>
          <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.scoringEmptyRule') }}</label>
          <textarea v-model="form.scoring_empty_rule" rows="3"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
        </div>

        <button type="submit" :disabled="saving || editSum !== 1"
          class="px-5 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
          {{ saving ? $t('common.loading') : $t('common.save') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object, countryCode: String });
const emit  = defineEmits(['updated']);

const saving = ref(false);
const form   = reactive({});

const publicWeights = computed(() => [
  { key: 'weight_finance', label: t('countryDetail.finances') },
  { key: 'weight_ties',    label: t('countryDetail.ties') },
  { key: 'weight_travel',  label: t('countryDetail.travelHistory') },
  { key: 'weight_profile', label: t('countryDetail.profile') },
]);

const editSum = computed(() =>
  +(publicWeights.value.reduce((s, w) => s + (form[w.key] ?? 0), 0).toFixed(2))
);

function initForm() {
  for (const w of publicWeights.value) {
    form[w.key] = parseFloat(props.country?.[w.key]) || 0;
  }
  form.weight_finances = parseFloat(props.country?.weight_finances) || 0;
  form.weight_visa_history = parseFloat(props.country?.weight_visa_history) || 0;
  form.weight_social_ties = parseFloat(props.country?.weight_social_ties) || 0;
  form.min_score = props.country?.min_score ?? 0;
  form.min_monthly_income_usd = props.country?.min_monthly_income_usd ?? 0;
  form.destination_score_bonus = props.country?.destination_score_bonus ?? 0;
  form.scoring_empty_rule = props.country?.scoring_empty_rule ?? '';
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateScoring(props.countryCode, {
      weight_finance: form.weight_finance,
      weight_ties: form.weight_ties,
      weight_travel: form.weight_travel,
      weight_profile: form.weight_profile,
      weight_finances: form.weight_finances,
      weight_visa_history: form.weight_visa_history,
      weight_social_ties: form.weight_social_ties,
      min_score: form.min_score,
      min_monthly_income_usd: form.min_monthly_income_usd,
      destination_score_bonus: form.destination_score_bonus,
      scoring_empty_rule: form.scoring_empty_rule,
    });
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(initForm);
</script>

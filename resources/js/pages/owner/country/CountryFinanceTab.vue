<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-[#0A1F44] mb-4">{{ $t('countryDetail.financeSettings') }}</h4>

      <form @submit.prevent="save" class="space-y-4">
        <!-- Минимальный доход -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.minIncome') }}</label>
            <input v-model.number="form.min_monthly_income_usd" type="number" min="0"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.currency') }}</label>
            <select v-model="form.min_income_currency"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="USD">USD</option>
              <option value="UZS">UZS</option>
              <option value="EUR">EUR</option>
            </select>
          </div>
        </div>

        <!-- Минимальный остаток -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.minBalance') }}</label>
            <input v-model.number="form.min_balance_usd" type="number" min="0"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.currency') }}</label>
            <select v-model="form.min_balance_currency"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="USD">USD</option>
              <option value="UZS">UZS</option>
              <option value="EUR">EUR</option>
            </select>
          </div>
        </div>

        <!-- Примечания -->
        <div>
          <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.financeNotes') }}</label>
          <textarea v-model="form.finance_notes" rows="3"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
        </div>

        <!-- Порог -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.financeThreshold') }} (0-100)</label>
            <input v-model.number="form.finance_threshold" type="number" min="0" max="100" step="0.01"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
        </div>
        <div>
          <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.thresholdComment') }}</label>
          <textarea v-model="form.finance_threshold_comment" rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
        </div>

        <button type="submit" :disabled="saving"
          class="px-5 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
          {{ saving ? $t('common.loading') : $t('common.save') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object });
const emit  = defineEmits(['updated']);

const saving = ref(false);

const form = reactive({
  min_monthly_income_usd: null,
  min_income_currency: 'USD',
  min_balance_usd: null,
  min_balance_currency: 'USD',
  finance_notes: '',
  finance_threshold: null,
  finance_threshold_comment: '',
});

function initForm() {
  form.min_monthly_income_usd = props.country.min_monthly_income_usd ?? null;
  form.min_income_currency = props.country.min_income_currency ?? 'USD';
  form.min_balance_usd = props.country.min_balance_usd ?? null;
  form.min_balance_currency = props.country.min_balance_currency ?? 'USD';
  form.finance_notes = props.country.finance_notes ?? '';
  form.finance_threshold = props.country.finance_threshold ?? null;
  form.finance_threshold_comment = props.country.finance_threshold_comment ?? '';
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateFinance(props.country.country_code, form);
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(initForm);
</script>

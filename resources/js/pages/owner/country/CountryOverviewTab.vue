<template>
  <div class="space-y-5">
    <!-- Основная информация + редактирование -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h4 class="text-sm font-semibold text-gray-500">{{ $t('countryDetail.generalInfo') }}</h4>
          <p class="text-xs text-gray-400 mt-0.5">{{ $t('countryDetail.generalInfoHint') }}</p>
        </div>
        <button v-if="!editing" @click="startEdit" class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
          {{ $t('countryDetail.edit') }}
        </button>
      </div>

      <div v-if="!editing">
        <dl class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3 text-sm">
          <InfoItem :label="$t('countryDetail.name')" :value="country.name" />
          <InfoItem :label="$t('countryDetail.nameUz')" :value="country.name_uz" />
          <InfoItem :label="$t('countryDetail.continent')" :value="country.continent" />
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('countryDetail.status') }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
              :class="country.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'">
              {{ country.is_active ? $t('countryDetail.active') : $t('countryDetail.inactive') }}
            </span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">{{ $t('countryDetail.visaRegime') }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium" :class="regimeClass">{{ regimeLabel }}</span>
          </div>
          <InfoItem :label="$t('countryDetail.riskLevel')" :value="riskLabel" />
          <InfoItem :label="$t('countryDetail.countryCode')" :value="country.country_code" mono />
          <InfoItem :label="$t('countryDetail.sortOrder')" :value="country.sort_order" />
          <InfoItem :label="$t('countryDetail.commission')" :value="country.commission_rate ? country.commission_rate + '%' : '---'" />
        </dl>

        <!-- Флаги -->
        <div v-if="country.is_popular || country.is_high_approval || country.is_high_refusal" class="mt-3 flex flex-wrap gap-2">
          <span v-if="country.is_popular" class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">{{ $t('countryDetail.popular') }}</span>
          <span v-if="country.is_high_approval" class="text-xs px-2.5 py-1 rounded-full bg-green-50 text-green-700">{{ $t('countryDetail.highApproval') }}</span>
          <span v-if="country.is_high_refusal" class="text-xs px-2.5 py-1 rounded-full bg-red-50 text-red-700">{{ $t('countryDetail.highRefusal') }}</span>
        </div>
      </div>

      <form v-else @submit.prevent="saveOverview" class="space-y-4">
        <!-- Ряд 1: Название -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.name') }}</label>
            <input v-model="form.name" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.nameUz') }}</label>
            <input v-model="form.name_uz" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.nameUzHint') }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.flagEmoji') }}</label>
            <input v-model="form.flag_emoji" maxlength="10" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
        </div>

        <!-- Ряд 2 -->
        <div class="grid grid-cols-4 gap-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.continent') }}</label>
            <select v-model="form.continent" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="">---</option>
              <option v-for="c in continents" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.riskLevel') }}</label>
            <select v-model="form.risk_level" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="low">{{ $t('countryDetail.riskLow') }}</option>
              <option value="medium">{{ $t('countryDetail.riskMedium') }}</option>
              <option value="high">{{ $t('countryDetail.riskHigh') }}</option>
            </select>
            <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.riskLevelHint') }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.sortOrder') }}</label>
            <input v-model.number="form.sort_order" type="number" min="0"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.sortOrderHint') }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.commission') }} (%)</label>
            <input v-model.number="form.commission_rate" type="number" min="0" max="100" step="0.01"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.commissionHint') }}</p>
          </div>
        </div>

        <!-- Чекбоксы с подсказками -->
        <div class="grid grid-cols-2 gap-3">
          <label class="flex items-start gap-2 text-sm p-3 bg-gray-50 rounded-lg">
            <input type="checkbox" v-model="form.is_active" class="rounded mt-0.5" />
            <div>
              <span>{{ $t('countryDetail.active') }}</span>
              <p class="text-[10px] text-gray-400">{{ $t('countryDetail.activeHint') }}</p>
            </div>
          </label>
          <label class="flex items-start gap-2 text-sm p-3 bg-gray-50 rounded-lg">
            <input type="checkbox" v-model="form.is_popular" class="rounded mt-0.5" />
            <div>
              <span>{{ $t('countryDetail.popular') }}</span>
              <p class="text-[10px] text-gray-400">{{ $t('countryDetail.popularHint') }}</p>
            </div>
          </label>
          <label class="flex items-start gap-2 text-sm p-3 bg-gray-50 rounded-lg">
            <input type="checkbox" v-model="form.is_high_approval" class="rounded mt-0.5" />
            <div>
              <span>{{ $t('countryDetail.highApproval') }}</span>
              <p class="text-[10px] text-gray-400">{{ $t('countryDetail.highApprovalHint') }}</p>
            </div>
          </label>
          <label class="flex items-start gap-2 text-sm p-3 bg-gray-50 rounded-lg">
            <input type="checkbox" v-model="form.is_high_refusal" class="rounded mt-0.5" />
            <div>
              <span>{{ $t('countryDetail.highRefusal') }}</span>
              <p class="text-[10px] text-gray-400">{{ $t('countryDetail.highRefusalHint') }}</p>
            </div>
          </label>
        </div>

        <!-- Общие сроки обработки -->
        <fieldset class="border border-gray-200 rounded-xl p-4">
          <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.generalTimeline') }}</legend>
          <p class="text-[11px] text-gray-400 mb-3">{{ $t('countryDetail.generalTimelineHint') }}</p>
          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.standardDays') }}</label>
              <input v-model.number="form.processing_days_standard" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.standardDaysHint') }}</p>
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.expeditedDays') }}</label>
              <input v-model.number="form.processing_days_expedited" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.expeditedDaysHint') }}</p>
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.appointmentWait') }}</label>
              <input v-model.number="form.appointment_wait_days" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.appointmentWaitOverviewHint') }}</p>
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.bufferDays') }}</label>
              <input v-model.number="form.buffer_days_recommended" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.bufferOverviewHint') }}</p>
            </div>
          </div>
        </fieldset>

        <!-- Стоимость -->
        <fieldset class="border border-gray-200 rounded-xl p-4">
          <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.costsTitle') }}</legend>
          <p class="text-[11px] text-gray-400 mb-3">{{ $t('countryDetail.costsTitleHint') }}</p>
          <div class="grid grid-cols-4 gap-3">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.visaFee') }} ($)</label>
              <input v-model.number="form.visa_fee_usd" type="number" min="0" step="0.01"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.evisaFee') }} ($)</label>
              <input v-model.number="form.evisa_fee_usd" type="number" min="0" step="0.01"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.avgFlight') }} ($)</label>
              <input v-model.number="form.avg_flight_cost_usd" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.avgFlightHint') }}</p>
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.avgHotel') }} ($/{{ $t('countryDetail.perNight') }})</label>
              <input v-model.number="form.avg_hotel_per_night_usd" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
        </fieldset>

        <!-- Заметки -->
        <div>
          <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.notes') }}</label>
          <textarea v-model="form.notes" rows="3"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"
            :placeholder="$t('countryDetail.notesPlaceholder')"></textarea>
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

    <!-- Визовый режим и требования (только отображение) -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-4">{{ $t('countryDetail.visaRegime') }}</h4>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="p-3 rounded-lg text-center" :class="regimeBgClass">
          <div class="text-lg font-bold" :class="regimeTextClass">{{ regimeLabel }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.visaRegime') }}</div>
        </div>
        <div v-if="country.visa_regime === 'visa_free'" class="p-3 bg-green-50 rounded-lg text-center">
          <div class="text-lg font-bold text-green-700">{{ country.visa_free_days || '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.visaFreeDays') }}</div>
        </div>
        <div v-if="country.visa_regime === 'visa_on_arrival'" class="p-3 bg-yellow-50 rounded-lg text-center">
          <div class="text-lg font-bold text-yellow-700">{{ country.visa_on_arrival_days || '---' }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.visaOnArrivalDays') }}</div>
        </div>
        <div v-if="country.visa_fee_usd" class="p-3 bg-gray-50 rounded-lg text-center">
          <div class="text-lg font-bold text-gray-700">${{ country.visa_fee_usd }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.visaFee') }}</div>
        </div>
        <div v-if="country.evisa_fee_usd" class="p-3 bg-blue-50 rounded-lg text-center">
          <div class="text-lg font-bold text-blue-700">${{ country.evisa_fee_usd }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.evisaFee') }}</div>
        </div>
      </div>

      <div v-if="hasRequirements" class="mt-4 flex flex-wrap gap-2">
        <span v-if="country.invitation_required" class="text-xs px-2.5 py-1 rounded-full bg-red-50 text-red-700">{{ $t('countryDetail.invitationRequired') }}</span>
        <span v-if="country.hotel_booking_required" class="text-xs px-2.5 py-1 rounded-full bg-orange-50 text-orange-700">{{ $t('countryDetail.hotelRequired') }}</span>
        <span v-if="country.insurance_required" class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">{{ $t('countryDetail.insuranceRequired') }}</span>
        <span v-if="country.bank_statement_required" class="text-xs px-2.5 py-1 rounded-full bg-purple-50 text-purple-700">{{ $t('countryDetail.bankStatementRequired') }}</span>
        <span v-if="country.return_ticket_required" class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $t('countryDetail.returnTicketRequired') }}</span>
      </div>
    </div>

    <!-- Сроки обработки (только отображение) -->
    <div v-if="country.processing_days_standard || country.processing_days_expedited" class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-1">{{ $t('countryDetail.generalTimeline') }}</h4>
      <p class="text-xs text-gray-400 mb-4">{{ $t('countryDetail.generalTimelineViewHint') }}</p>
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

    <!-- Стоимость поездки (только отображение) -->
    <div v-if="country.avg_flight_cost_usd || country.avg_hotel_per_night_usd" class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-4">{{ $t('countryDetail.travelCosts') }}</h4>
      <div class="grid grid-cols-2 gap-4 text-center">
        <div v-if="country.avg_flight_cost_usd" class="p-3 bg-gray-50 rounded-lg">
          <div class="text-xl font-bold text-gray-700">${{ country.avg_flight_cost_usd }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.avgFlight') }}</div>
        </div>
        <div v-if="country.avg_hotel_per_night_usd" class="p-3 bg-gray-50 rounded-lg">
          <div class="text-xl font-bold text-gray-700">${{ country.avg_hotel_per_night_usd }}</div>
          <div class="text-xs text-gray-500 mt-1">{{ $t('countryDetail.avgHotel') }}</div>
        </div>
      </div>
    </div>

    <!-- Скоринг (краткая сводка) -->
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-4">{{ $t('countryDetail.publicScoring') }}</h4>
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

    <!-- Заметки -->
    <div v-if="country.notes" class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-gray-500 mb-2">{{ $t('countryDetail.notes') }}</h4>
      <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ country.notes }}</p>
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

const regimeLabel = computed(() => {
  const map = {
    visa_required: t('countryDetail.visaRequired'),
    visa_free: t('countryDetail.visaFreeRegime'),
    visa_on_arrival: t('countryDetail.visaOnArrivalRegime'),
    evisa: t('countryDetail.evisaRegime'),
  };
  return map[props.country.visa_regime] || props.country.visa_regime || '---';
});

const regimeClass = computed(() => {
  if (props.country.visa_regime === 'visa_free') return 'bg-green-50 text-green-700';
  if (props.country.visa_regime === 'visa_on_arrival') return 'bg-yellow-50 text-yellow-700';
  if (props.country.visa_regime === 'evisa') return 'bg-blue-50 text-blue-700';
  return 'bg-gray-100 text-gray-500';
});

const regimeBgClass = computed(() => {
  if (props.country.visa_regime === 'visa_free') return 'bg-green-50';
  if (props.country.visa_regime === 'visa_on_arrival') return 'bg-yellow-50';
  if (props.country.visa_regime === 'evisa') return 'bg-blue-50';
  return 'bg-gray-50';
});

const regimeTextClass = computed(() => {
  if (props.country.visa_regime === 'visa_free') return 'text-green-700';
  if (props.country.visa_regime === 'visa_on_arrival') return 'text-yellow-700';
  if (props.country.visa_regime === 'evisa') return 'text-blue-700';
  return 'text-gray-700';
});

const riskLabel = computed(() => {
  if (props.country.risk_level === 'low') return t('countryDetail.riskLow');
  if (props.country.risk_level === 'high') return t('countryDetail.riskHigh');
  return t('countryDetail.riskMedium');
});

const hasRequirements = computed(() =>
  props.country.invitation_required || props.country.hotel_booking_required ||
  props.country.insurance_required || props.country.bank_statement_required ||
  props.country.return_ticket_required
);

const weights = computed(() => [
  { key: 'weight_finance', label: t('countryDetail.finances') },
  { key: 'weight_ties',    label: t('countryDetail.ties') },
  { key: 'weight_travel',  label: t('countryDetail.travelHistory') },
  { key: 'weight_profile', label: t('countryDetail.profile') },
]);

const indicators = computed(() => [
  { tab: 'visa-types', label: t('countryDetail.tabVisaTypes'), filled: (props.visaSettings?.length ?? 0) > 0 },
  { tab: 'submission', label: t('countryDetail.tabSubmission'), filled: (props.country.submission_types?.length ?? 0) > 0 || props.country.visa_regime !== 'visa_required' },
  { tab: 'embassy', label: t('countryDetail.tabEmbassy'), filled: props.country.has_embassy && !!props.country.embassy_name },
  { tab: 'visa-center', label: t('countryDetail.tabVisaCenter'), filled: props.country.has_visa_center && !!props.country.visa_center_name },
  { tab: 'finance', label: t('countryDetail.tabFinance'), filled: (props.country.min_monthly_income_usd ?? 0) > 0 },
  { tab: 'scoring', label: t('countryDetail.tabScoring'), filled: (parseFloat(props.country.weight_finance) || 0) > 0 },
]);

function startEdit() {
  Object.assign(form, {
    name: props.country.name ?? '',
    name_uz: props.country.name_uz ?? '',
    flag_emoji: props.country.flag_emoji ?? '',
    continent: props.country.continent ?? '',
    is_active: props.country.is_active ?? true,
    risk_level: props.country.risk_level ?? 'medium',
    sort_order: props.country.sort_order ?? 0,
    commission_rate: props.country.commission_rate ?? null,
    is_popular: props.country.is_popular ?? false,
    is_high_approval: props.country.is_high_approval ?? false,
    is_high_refusal: props.country.is_high_refusal ?? false,
    processing_days_standard: props.country.processing_days_standard ?? null,
    processing_days_expedited: props.country.processing_days_expedited ?? null,
    appointment_wait_days: props.country.appointment_wait_days ?? null,
    buffer_days_recommended: props.country.buffer_days_recommended ?? null,
    visa_fee_usd: props.country.visa_fee_usd ?? null,
    evisa_fee_usd: props.country.evisa_fee_usd ?? null,
    avg_flight_cost_usd: props.country.avg_flight_cost_usd ?? null,
    avg_hotel_per_night_usd: props.country.avg_hotel_per_night_usd ?? null,
    notes: props.country.notes ?? '',
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

const InfoItem = {
  props: ['label', 'value', 'mono'],
  template: `
    <div class="flex justify-between">
      <span class="text-gray-500">{{ label }}</span>
      <span :class="mono ? 'font-mono font-bold' : 'font-medium'" class="text-gray-800">{{ value || '---' }}</span>
    </div>
  `,
};
</script>

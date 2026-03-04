<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-[#0A1F44] mb-4">{{ $t('countryDetail.submissionSettings') }}</h4>

      <form @submit.prevent="save" class="space-y-5">
        <!-- Визовый режим -->
        <div>
          <label class="text-xs text-gray-500 mb-2 block">{{ $t('countryDetail.visaRegime') }}</label>
          <div class="flex gap-3 flex-wrap">
            <label v-for="r in regimeOptions" :key="r.value"
              class="flex items-center gap-2 text-sm px-3 py-2 rounded-lg border cursor-pointer transition-colors"
              :class="form.visa_regime === r.value ? 'border-blue-300 bg-blue-50 text-blue-800' : 'border-gray-200 hover:bg-gray-50'">
              <input type="radio" :value="r.value" v-model="form.visa_regime" class="text-blue-600" />
              {{ r.label }}
            </label>
          </div>
        </div>

        <!-- Если безвизовый или по прибытии — доп. поля -->
        <div v-if="form.visa_regime === 'visa_free' || form.visa_regime === 'visa_on_arrival'" class="grid grid-cols-2 gap-4">
          <div v-if="form.visa_regime === 'visa_free'">
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.visaFreeDays') }}</label>
            <input v-model.number="form.visa_free_days" type="number" min="0"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div v-if="form.visa_regime === 'visa_on_arrival'">
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.visaOnArrivalDays') }}</label>
            <input v-model.number="form.visa_on_arrival_days" type="number" min="0"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
        </div>

        <!-- E-виза -->
        <fieldset class="border border-gray-200 rounded-xl p-4">
          <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.evisaSection') }}</legend>
          <div class="flex items-center gap-4 mt-2">
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="form.evisa_available" class="rounded" />
              {{ $t('countryDetail.evisaAvailable') }}
            </label>
          </div>
          <div v-if="form.evisa_available" class="grid grid-cols-2 gap-4 mt-3">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.evisaDays') }}</label>
              <input v-model.number="form.evisa_processing_days" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.evisaUrl') }}</label>
              <input v-model="form.evisa_url" type="url"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
        </fieldset>

        <!-- Типы подачи (только для визового) -->
        <template v-if="form.visa_regime === 'visa_required'">
          <div>
            <label class="text-xs text-gray-500 mb-2 block">{{ $t('countryDetail.submissionTypes') }}</label>
            <div class="flex gap-4">
              <label v-for="st in submissionOptions" :key="st.value" class="flex items-center gap-2 text-sm">
                <input type="checkbox" :value="st.value" v-model="form.submission_types" class="rounded" />
                {{ st.label }}
              </label>
            </div>
          </div>

          <!-- Правила подачи -->
          <fieldset class="border border-gray-200 rounded-xl p-4">
            <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.submissionRules') }}</legend>
            <div class="grid grid-cols-2 gap-3 mt-2">
              <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
                <input type="checkbox" v-model="form.appointment_required" class="rounded" />
                {{ $t('countryDetail.appointmentRequired') }}
              </label>
              <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
                <input type="checkbox" v-model="form.personal_submission_required" class="rounded" />
                {{ $t('countryDetail.personalSubmission') }}
              </label>
              <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
                <input type="checkbox" v-model="form.biometrics_required" class="rounded" />
                {{ $t('countryDetail.biometrics') }}
              </label>
              <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
                <input type="checkbox" v-model="form.photo_required" class="rounded" />
                {{ $t('countryDetail.photoRequired') }}
              </label>
            </div>
          </fieldset>
        </template>

        <!-- Требования к документам -->
        <fieldset class="border border-gray-200 rounded-xl p-4">
          <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.docRequirements') }}</legend>
          <div class="grid grid-cols-2 gap-3 mt-2">
            <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
              <input type="checkbox" v-model="form.invitation_required" class="rounded" />
              {{ $t('countryDetail.invitationRequired') }}
            </label>
            <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
              <input type="checkbox" v-model="form.hotel_booking_required" class="rounded" />
              {{ $t('countryDetail.hotelRequired') }}
            </label>
            <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
              <input type="checkbox" v-model="form.insurance_required" class="rounded" />
              {{ $t('countryDetail.insuranceRequired') }}
            </label>
            <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
              <input type="checkbox" v-model="form.bank_statement_required" class="rounded" />
              {{ $t('countryDetail.bankStatementRequired') }}
            </label>
            <label class="flex items-center gap-2 text-sm p-3 bg-gray-50 rounded-lg">
              <input type="checkbox" v-model="form.return_ticket_required" class="rounded" />
              {{ $t('countryDetail.returnTicketRequired') }}
            </label>
          </div>
        </fieldset>

        <!-- Примечания -->
        <div>
          <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.submissionNotes') }}</label>
          <textarea v-model="form.submission_notes" rows="3"
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
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object });
const emit  = defineEmits(['updated']);

const saving = ref(false);

const regimeOptions = computed(() => [
  { value: 'visa_required',    label: t('countryDetail.visaRequired') },
  { value: 'visa_free',        label: t('countryDetail.visaFreeRegime') },
  { value: 'visa_on_arrival',  label: t('countryDetail.visaOnArrivalRegime') },
  { value: 'evisa',            label: t('countryDetail.evisaRegime') },
]);

const submissionOptions = computed(() => [
  { value: 'visa_center', label: t('countryDetail.visaCenter') },
  { value: 'embassy',     label: t('countryDetail.embassy') },
  { value: 'online',      label: t('countryDetail.online') },
]);

const form = reactive({
  visa_regime: 'visa_required',
  visa_free_days: null,
  visa_on_arrival_days: null,
  evisa_processing_days: null,
  evisa_available: false,
  evisa_url: '',
  submission_types: [],
  appointment_required: false,
  personal_submission_required: false,
  biometrics_required: false,
  photo_required: false,
  submission_notes: '',
  invitation_required: false,
  hotel_booking_required: false,
  insurance_required: false,
  bank_statement_required: false,
  return_ticket_required: false,
});

function initForm() {
  form.visa_regime = props.country.visa_regime ?? 'visa_required';
  form.visa_free_days = props.country.visa_free_days ?? null;
  form.visa_on_arrival_days = props.country.visa_on_arrival_days ?? null;
  form.evisa_processing_days = props.country.evisa_processing_days ?? null;
  form.evisa_available = props.country.evisa_available ?? false;
  form.evisa_url = props.country.evisa_url ?? '';
  form.submission_types = [...(props.country.submission_types || [])];
  form.appointment_required = props.country.appointment_required ?? false;
  form.personal_submission_required = props.country.personal_submission_required ?? false;
  form.biometrics_required = props.country.biometrics_required ?? false;
  form.photo_required = props.country.photo_required ?? false;
  form.submission_notes = props.country.submission_notes ?? '';
  form.invitation_required = props.country.invitation_required ?? false;
  form.hotel_booking_required = props.country.hotel_booking_required ?? false;
  form.insurance_required = props.country.insurance_required ?? false;
  form.bank_statement_required = props.country.bank_statement_required ?? false;
  form.return_ticket_required = props.country.return_ticket_required ?? false;
}

async function save() {
  saving.value = true;
  try {
    // Сохраняем визовый режим + e-visa + требования + стоимости через общий update
    await ownerCountriesApi.update(props.country.country_code, {
      visa_regime: form.visa_regime,
      visa_free_days: form.visa_free_days,
      visa_on_arrival_days: form.visa_on_arrival_days,
      evisa_available: form.evisa_available,
      evisa_url: form.evisa_url,
      evisa_processing_days: form.evisa_processing_days,
      invitation_required: form.invitation_required,
      hotel_booking_required: form.hotel_booking_required,
      insurance_required: form.insurance_required,
      bank_statement_required: form.bank_statement_required,
      return_ticket_required: form.return_ticket_required,
    });
    // Сохраняем данные подачи
    await ownerCountriesApi.updateSubmission(props.country.country_code, {
      submission_types: form.submission_types,
      appointment_required: form.appointment_required,
      personal_submission_required: form.personal_submission_required,
      biometrics_required: form.biometrics_required,
      photo_required: form.photo_required,
      submission_notes: form.submission_notes,
    });
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(initForm);
</script>

<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-[#0A1F44] mb-4">{{ $t('countryDetail.submissionSettings') }}</h4>

      <form @submit.prevent="save" class="space-y-5">
        <!-- Типы подачи -->
        <div>
          <label class="text-xs text-gray-500 mb-2 block">{{ $t('countryDetail.submissionTypes') }}</label>
          <div class="flex gap-4">
            <label v-for="st in submissionOptions" :key="st.value" class="flex items-center gap-2 text-sm">
              <input type="checkbox" :value="st.value" v-model="form.submission_types" class="rounded" />
              {{ st.label }}
            </label>
          </div>
        </div>

        <!-- Правила -->
        <div>
          <label class="text-xs text-gray-500 mb-3 block">{{ $t('countryDetail.submissionRules') }}</label>
          <div class="grid grid-cols-2 gap-3">
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
        </div>

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

const submissionOptions = computed(() => [
  { value: 'visa_center', label: t('countryDetail.visaCenter') },
  { value: 'embassy',     label: t('countryDetail.embassy') },
  { value: 'online',      label: t('countryDetail.online') },
]);

const form = reactive({
  submission_types: [],
  appointment_required: false,
  personal_submission_required: false,
  biometrics_required: false,
  photo_required: false,
  submission_notes: '',
});

function initForm() {
  form.submission_types = [...(props.country.submission_types || [])];
  form.appointment_required = props.country.appointment_required ?? false;
  form.personal_submission_required = props.country.personal_submission_required ?? false;
  form.biometrics_required = props.country.biometrics_required ?? false;
  form.photo_required = props.country.photo_required ?? false;
  form.submission_notes = props.country.submission_notes ?? '';
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateSubmission(props.country.country_code, form);
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(initForm);
</script>

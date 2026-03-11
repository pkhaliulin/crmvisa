<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-[#0A1F44] mb-4">{{ $t('countryDetail.embassyInfo') }}</h4>

      <form @submit.prevent="save" class="space-y-4">
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.has_embassy" class="rounded" />
          {{ $t('countryDetail.hasEmbassy') }}
        </label>
        <p class="text-[10px] text-gray-400 -mt-2">{{ $t('countryDetail.hasEmbassyHint') }}</p>

        <!-- Есть посольство в Узбекистане -->
        <template v-if="form.has_embassy">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.embassyName') }}</label>
              <input v-model="form.embassy_name"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.city') }}</label>
              <input v-model="form.embassy_city"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.phone') }}</label>
              <input v-model="form.embassy_phone"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.email') }}</label>
              <input v-model="form.embassy_email" type="email"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.website') }}</label>
              <input v-model="form.embassy_website" type="url"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.appointmentUrl') }}</label>
              <input v-model="form.appointment_url" type="url"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.address') }}</label>
            <input v-model="form.embassy_address"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.latitude') }}</label>
              <input v-model.number="form.latitude" type="number" step="0.0000001"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.longitude') }}</label>
              <input v-model.number="form.longitude" type="number" step="0.0000001"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>

          <!-- Карта -->
          <div v-if="form.latitude && form.longitude" class="rounded-lg overflow-hidden border border-gray-200 h-48">
            <iframe
              :src="`https://www.openstreetmap.org/export/embed.html?bbox=${form.longitude - 0.01},${form.latitude - 0.01},${form.longitude + 0.01},${form.latitude + 0.01}&layer=mapnik&marker=${form.latitude},${form.longitude}`"
              class="w-full h-full border-0"
              loading="lazy"
            ></iframe>
          </div>

          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.description') }}</label>
            <textarea v-model="form.embassy_description" rows="3"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.rules') }}</label>
            <textarea v-model="form.embassy_rules" rows="3"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
          </div>
        </template>

        <!-- НЕТ посольства в Узбекистане -->
        <template v-else>
          <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
            <p class="text-sm text-amber-800 font-medium mb-1">{{ $t('countryDetail.noEmbassyTitle') }}</p>
            <p class="text-xs text-amber-600">{{ $t('countryDetail.noEmbassyDesc') }}</p>
          </div>

          <!-- Процедура подачи -->
          <fieldset class="border border-gray-200 rounded-xl p-4">
            <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.submissionProcedure') }}</legend>
            <p class="text-[10px] text-gray-400 mb-3">{{ $t('countryDetail.submissionProcedureHint') }}</p>

            <div class="space-y-3">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.procedureType') }}</label>
                <SearchSelect
                  v-model="form.submission_procedure"
                  :items="procedureItems"
                  allow-all
                  :all-label="$t('common.notSpecified')"
                />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.procedureTypeHint') }}</p>
              </div>
            </div>
          </fieldset>

          <!-- Посольство в другой стране -->
          <fieldset class="border border-gray-200 rounded-xl p-4">
            <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.referralEmbassy') }}</legend>
            <p class="text-[10px] text-gray-400 mb-3">{{ $t('countryDetail.referralEmbassyHint') }}</p>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.referralCountry') }}</label>
                <input v-model="form.referral_embassy_country" :placeholder="$t('countryDetail.referralCountryPlaceholder')"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.referralCity') }}</label>
                <input v-model="form.referral_embassy_city" :placeholder="$t('countryDetail.referralCityPlaceholder')"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-3">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.referralName') }}</label>
                <input v-model="form.referral_embassy_name" :placeholder="$t('countryDetail.referralNamePlaceholder')"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.website') }}</label>
                <input v-model="form.referral_embassy_website" type="url"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
              </div>
            </div>
            <div class="mt-3">
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.referralAddress') }}</label>
              <input v-model="form.referral_embassy_address"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </fieldset>

          <!-- Заметки о процедуре -->
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.noEmbassyNotes') }}</label>
            <textarea v-model="form.no_embassy_notes" rows="4"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"
              :placeholder="$t('countryDetail.noEmbassyNotesPlaceholder')"></textarea>
          </div>
        </template>

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
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const props = defineProps({ country: Object });
const emit  = defineEmits(['updated']);

const saving = ref(false);

const procedureItems = computed(() => [
  { value: 'visa_center_local', label: t('countryDetail.procedureVcLocal') },
  { value: 'embassy_abroad', label: t('countryDetail.procedureEmbassyAbroad') },
  { value: 'visa_center_and_embassy', label: t('countryDetail.procedureVcAndEmbassy') },
  { value: 'mail_submission', label: t('countryDetail.procedureMail') },
  { value: 'online_only', label: t('countryDetail.procedureOnline') },
]);

const form = reactive({
  has_embassy: false,
  embassy_name: '',
  embassy_address: '',
  embassy_city: '',
  embassy_phone: '',
  embassy_email: '',
  embassy_website: '',
  appointment_url: '',
  embassy_description: '',
  embassy_rules: '',
  latitude: null,
  longitude: null,
  // Поля для случая "нет посольства"
  referral_embassy_country: '',
  referral_embassy_city: '',
  referral_embassy_name: '',
  referral_embassy_address: '',
  referral_embassy_website: '',
  submission_procedure: '',
  no_embassy_notes: '',
});

function initForm() {
  form.has_embassy = props.country.has_embassy ?? false;
  form.embassy_name = props.country.embassy_name ?? '';
  form.embassy_address = props.country.embassy_address ?? '';
  form.embassy_city = props.country.embassy_city ?? '';
  form.embassy_phone = props.country.embassy_phone ?? '';
  form.embassy_email = props.country.embassy_email ?? '';
  form.embassy_website = props.country.embassy_website ?? '';
  form.appointment_url = props.country.appointment_url ?? '';
  form.embassy_description = props.country.embassy_description ?? '';
  form.embassy_rules = props.country.embassy_rules ?? '';
  form.latitude = props.country.latitude ?? null;
  form.longitude = props.country.longitude ?? null;
  form.referral_embassy_country = props.country.referral_embassy_country ?? '';
  form.referral_embassy_city = props.country.referral_embassy_city ?? '';
  form.referral_embassy_name = props.country.referral_embassy_name ?? '';
  form.referral_embassy_address = props.country.referral_embassy_address ?? '';
  form.referral_embassy_website = props.country.referral_embassy_website ?? '';
  form.submission_procedure = props.country.submission_procedure ?? '';
  form.no_embassy_notes = props.country.no_embassy_notes ?? '';
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateEmbassy(props.country.country_code, form);
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(initForm);
</script>

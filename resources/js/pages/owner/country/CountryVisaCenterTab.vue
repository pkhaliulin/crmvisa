<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <h4 class="text-sm font-semibold text-[#0A1F44] mb-4">{{ $t('countryDetail.visaCenterTitle') }}</h4>

      <form @submit.prevent="save" class="space-y-4">
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" v-model="form.has_visa_center" class="rounded" />
          {{ $t('countryDetail.hasVisaCenter') }}
        </label>

        <template v-if="form.has_visa_center">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.vcName') }}</label>
              <input v-model="form.visa_center_name"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.website') }}</label>
              <input v-model="form.visa_center_website" type="url"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.phone') }}</label>
              <input v-model="form.visa_center_phone"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.email') }}</label>
              <input v-model="form.visa_center_email" type="email"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.address') }}</label>
            <input v-model="form.visa_center_address"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.notes') }}</label>
            <textarea v-model="form.visa_center_notes" rows="3"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
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
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object });
const emit  = defineEmits(['updated']);

const saving = ref(false);

const form = reactive({
  has_visa_center: false,
  visa_center_name: '',
  visa_center_website: '',
  visa_center_phone: '',
  visa_center_email: '',
  visa_center_address: '',
  visa_center_notes: '',
});

function initForm() {
  form.has_visa_center = props.country.has_visa_center ?? false;
  form.visa_center_name = props.country.visa_center_name ?? '';
  form.visa_center_website = props.country.visa_center_website ?? '';
  form.visa_center_phone = props.country.visa_center_phone ?? '';
  form.visa_center_email = props.country.visa_center_email ?? '';
  form.visa_center_address = props.country.visa_center_address ?? '';
  form.visa_center_notes = props.country.visa_center_notes ?? '';
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateVisaCenter(props.country.country_code, form);
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(initForm);
</script>

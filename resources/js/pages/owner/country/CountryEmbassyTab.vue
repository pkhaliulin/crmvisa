<template>
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
      <div class="flex items-center justify-between mb-4">
        <h4 class="text-sm font-semibold text-[#0A1F44]">{{ $t('countryDetail.embassyInfo') }}</h4>
        <button v-if="!editing" @click="startEdit"
          class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
          {{ $t('countryDetail.edit') }}
        </button>
      </div>

      <div v-if="!editing" class="space-y-4">
        <!-- Посольство -->
        <div>
          <h5 class="text-xs text-gray-400 uppercase mb-2">{{ $t('countryDetail.embassy') }}</h5>
          <dl class="space-y-1.5 text-sm">
            <InfoRow :label="$t('countryDetail.embassyName')" :value="country.embassy_name" />
            <InfoRow :label="$t('countryDetail.address')" :value="country.embassy_address" />
            <InfoRow :label="$t('countryDetail.city')" :value="country.embassy_city" />
            <InfoRow :label="$t('countryDetail.phone')" :value="country.embassy_phone" />
            <InfoRow :label="$t('countryDetail.email')" :value="country.embassy_email" />
            <InfoRow :label="$t('countryDetail.website')" :value="country.embassy_website" link />
            <InfoRow :label="$t('countryDetail.appointmentUrl')" :value="country.appointment_url" link />
          </dl>
        </div>

        <!-- Визовый центр -->
        <div v-if="country.submission_type === 'visa_center' || country.submission_type === 'both'">
          <h5 class="text-xs text-gray-400 uppercase mb-2 mt-4">{{ $t('countryDetail.visaCenterTitle') }}</h5>
          <dl class="space-y-1.5 text-sm">
            <InfoRow :label="$t('countryDetail.vcName')" :value="country.visa_center_name" />
            <InfoRow :label="$t('countryDetail.address')" :value="country.visa_center_address" />
            <InfoRow :label="$t('countryDetail.phone')" :value="country.visa_center_phone" />
            <InfoRow :label="$t('countryDetail.website')" :value="country.visa_center_website" link />
          </dl>
        </div>

        <!-- Координаты -->
        <div v-if="country.latitude && country.longitude">
          <h5 class="text-xs text-gray-400 uppercase mb-2 mt-4">{{ $t('countryDetail.coordinates') }}</h5>
          <p class="text-sm text-gray-600">{{ country.latitude }}, {{ country.longitude }}</p>
        </div>

        <!-- Описание -->
        <div v-if="country.embassy_description">
          <h5 class="text-xs text-gray-400 uppercase mb-2 mt-4">{{ $t('countryDetail.description') }}</h5>
          <p class="text-sm text-gray-700 whitespace-pre-line">{{ country.embassy_description }}</p>
        </div>

        <!-- Правила -->
        <div v-if="country.embassy_rules">
          <h5 class="text-xs text-gray-400 uppercase mb-2 mt-4">{{ $t('countryDetail.rules') }}</h5>
          <p class="text-sm text-gray-700 whitespace-pre-line">{{ country.embassy_rules }}</p>
        </div>
      </div>

      <!-- Форма редактирования -->
      <form v-else @submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <FormField :label="$t('countryDetail.embassyName')" v-model="form.embassy_name" />
          <FormField :label="$t('countryDetail.city')" v-model="form.embassy_city" />
        </div>
        <FormField :label="$t('countryDetail.address')" v-model="form.embassy_address" />
        <div class="grid grid-cols-2 gap-4">
          <FormField :label="$t('countryDetail.phone')" v-model="form.embassy_phone" />
          <FormField :label="$t('countryDetail.email')" v-model="form.embassy_email" type="email" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <FormField :label="$t('countryDetail.website')" v-model="form.embassy_website" type="url" />
          <FormField :label="$t('countryDetail.appointmentUrl')" v-model="form.appointment_url" type="url" />
        </div>
        <div>
          <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.submissionType') }}</label>
          <select v-model="form.submission_type"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
            <option value="embassy_direct">{{ $t('countryDetail.embassyDirect') }}</option>
            <option value="visa_center">{{ $t('countryDetail.visaCenter') }}</option>
            <option value="both">{{ $t('countryDetail.both') }}</option>
            <option value="online">{{ $t('countryDetail.online') }}</option>
          </select>
        </div>

        <div v-if="form.submission_type === 'visa_center' || form.submission_type === 'both'"
          class="border-t border-gray-100 pt-4">
          <h5 class="text-xs text-gray-400 uppercase mb-3">{{ $t('countryDetail.visaCenterTitle') }}</h5>
          <div class="space-y-3">
            <FormField :label="$t('countryDetail.vcName')" v-model="form.visa_center_name" />
            <FormField :label="$t('countryDetail.address')" v-model="form.visa_center_address" />
            <div class="grid grid-cols-2 gap-4">
              <FormField :label="$t('countryDetail.phone')" v-model="form.visa_center_phone" />
              <FormField :label="$t('countryDetail.website')" v-model="form.visa_center_website" type="url" />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <FormField :label="$t('countryDetail.latitude')" v-model.number="form.latitude" type="number" step="0.0000001" />
          <FormField :label="$t('countryDetail.longitude')" v-model.number="form.longitude" type="number" step="0.0000001" />
        </div>

        <FormTextarea :label="$t('countryDetail.description')" v-model="form.embassy_description" :rows="3" />
        <FormTextarea :label="$t('countryDetail.rules')" v-model="form.embassy_rules" :rows="3" />

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
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';

const { t } = useI18n();
const props = defineProps({ country: Object });
const emit = defineEmits(['updated']);

const editing = ref(false);
const saving  = ref(false);
const form    = reactive({});

function startEdit() {
  Object.assign(form, {
    embassy_name:        props.country.embassy_name ?? '',
    embassy_address:     props.country.embassy_address ?? '',
    embassy_city:        props.country.embassy_city ?? '',
    embassy_phone:       props.country.embassy_phone ?? '',
    embassy_email:       props.country.embassy_email ?? '',
    embassy_website:     props.country.embassy_website ?? '',
    appointment_url:     props.country.appointment_url ?? '',
    embassy_description: props.country.embassy_description ?? '',
    embassy_rules:       props.country.embassy_rules ?? '',
    submission_type:     props.country.submission_type ?? 'visa_center',
    visa_center_name:    props.country.visa_center_name ?? '',
    visa_center_address: props.country.visa_center_address ?? '',
    visa_center_phone:   props.country.visa_center_phone ?? '',
    visa_center_website: props.country.visa_center_website ?? '',
    latitude:            props.country.latitude ?? null,
    longitude:           props.country.longitude ?? null,
  });
  editing.value = true;
}

async function save() {
  saving.value = true;
  try {
    await ownerCountriesApi.updateEmbassy(props.country.country_code, form);
    editing.value = false;
    emit('updated');
  } finally {
    saving.value = false;
  }
}

// Mini inline components
const InfoRow = {
  props: ['label', 'value', 'link'],
  template: `
    <div class="flex justify-between">
      <dt class="text-gray-500">{{ label }}</dt>
      <dd v-if="link && value" class="text-right">
        <a :href="value" target="_blank" class="text-blue-600 hover:underline text-xs break-all">{{ value }}</a>
      </dd>
      <dd v-else class="text-gray-800 text-right">{{ value || '---' }}</dd>
    </div>
  `,
};

const FormField = {
  props: ['label', 'modelValue', 'type', 'step'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="text-xs text-gray-500 mb-1 block">{{ label }}</label>
      <input :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        :type="type || 'text'" :step="step"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
    </div>
  `,
};

const FormTextarea = {
  props: ['label', 'modelValue', 'rows'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="text-xs text-gray-500 mb-1 block">{{ label }}</label>
      <textarea :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        :rows="rows || 3"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
    </div>
  `,
};
</script>

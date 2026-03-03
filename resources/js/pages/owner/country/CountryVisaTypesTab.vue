<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <p class="text-sm text-gray-500">{{ $t('countryDetail.visaTypesDesc') }}</p>
      <button @click="openAdd = true"
        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e]">
        + {{ $t('countryDetail.addVisaType') }}
      </button>
    </div>

    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <!-- Аккордеон типов виз -->
    <div v-else-if="settings.length" class="space-y-3">
      <div v-for="s in settings" :key="s.id"
        class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <!-- Header -->
        <button @click="toggle(s.id)"
          class="w-full flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition-colors">
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-gray-800">{{ s.visa_type }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full"
              :class="s.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'">
              {{ s.is_active ? $t('countryDetail.active') : $t('countryDetail.inactive') }}
            </span>
          </div>
          <div class="flex items-center gap-4 text-xs text-gray-500">
            <span>{{ s.processing_days_avg }} {{ $t('common.days') }} {{ $t('countryDetail.avg') }}</span>
            <span v-if="s.consular_fee_usd">${{ s.consular_fee_usd }}</span>
            <svg class="w-4 h-4 transition-transform" :class="expanded === s.id ? 'rotate-180' : ''"
              fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </div>
        </button>

        <!-- Body -->
        <div v-if="expanded === s.id" class="px-5 pb-5 border-t border-gray-100">
          <!-- Timeline visualization -->
          <div class="mt-4 mb-5">
            <h5 class="text-xs font-semibold text-gray-500 uppercase mb-3">{{ $t('countryDetail.timeline') }}</h5>
            <div class="flex gap-1 h-8 rounded-lg overflow-hidden text-[10px] font-medium text-white">
              <div class="flex items-center justify-center bg-blue-400" :style="{ flex: s.preparation_days }">
                {{ s.preparation_days }}d
              </div>
              <div class="flex items-center justify-center bg-orange-400" :style="{ flex: s.appointment_wait_days }">
                {{ s.appointment_wait_days }}d
              </div>
              <div class="flex items-center justify-center bg-purple-500" :style="{ flex: s.processing_days_avg }">
                {{ s.processing_days_avg }}d
              </div>
              <div class="flex items-center justify-center bg-gray-400" :style="{ flex: s.buffer_days }">
                {{ s.buffer_days }}d
              </div>
            </div>
            <div class="flex gap-1 mt-1 text-[10px] text-gray-500">
              <div :style="{ flex: s.preparation_days }">{{ $t('countryDetail.preparation') }}</div>
              <div :style="{ flex: s.appointment_wait_days }">{{ $t('countryDetail.appointment') }}</div>
              <div :style="{ flex: s.processing_days_avg }">{{ $t('countryDetail.processing') }}</div>
              <div :style="{ flex: s.buffer_days }">{{ $t('countryDetail.buffer') }}</div>
            </div>
          </div>

          <!-- Parameters grid -->
          <div class="grid grid-cols-3 gap-4 text-sm">
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.processingMin') }}:</span>
              <span class="ml-1 font-medium">{{ s.processing_days_min }} {{ $t('common.days') }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.processingMax') }}:</span>
              <span class="ml-1 font-medium">{{ s.processing_days_max }} {{ $t('common.days') }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.refusalRate') }}:</span>
              <span class="ml-1 font-medium">{{ s.avg_refusal_rate ?? '---' }}%</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.biometrics') }}:</span>
              <span class="ml-1 font-medium">{{ s.biometrics_required ? $t('common.yes') : $t('common.no') }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.personalVisit') }}:</span>
              <span class="ml-1 font-medium">{{ s.personal_visit_required ? $t('common.yes') : $t('common.no') }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.interview') }}:</span>
              <span class="ml-1 font-medium">{{ s.interview_required ? $t('common.yes') : $t('common.no') }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.consularFee') }}:</span>
              <span class="ml-1 font-medium">${{ s.consular_fee_usd ?? '---' }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.serviceFee') }}:</span>
              <span class="ml-1 font-medium">${{ s.service_fee_usd ?? '---' }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ $t('countryDetail.totalBefore') }}:</span>
              <span class="ml-1 font-bold text-blue-700">{{ s.recommended_days_before_departure }} {{ $t('common.days') }}</span>
            </div>
          </div>

          <!-- Documents -->
          <div class="mt-5 pt-4 border-t border-gray-100">
            <div class="flex items-center justify-between mb-3">
              <h5 class="text-xs font-semibold text-gray-500 uppercase">{{ $t('countryDetail.requiredDocs') }}</h5>
              <button @click="openDocAdd(s.visa_type)"
                class="text-xs px-2.5 py-1 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 font-medium">
                + {{ $t('countryDetail.addDoc') }}
              </button>
            </div>

            <div v-if="getDocsForVisa(s.visa_type).length" class="space-y-2">
              <div v-for="doc in getDocsForVisa(s.visa_type)" :key="doc.id"
                class="flex items-center justify-between bg-gray-50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-2.5">
                  <span class="w-2 h-2 rounded-full flex-shrink-0"
                    :class="{
                      'bg-red-500': doc.requirement_level === 'required',
                      'bg-yellow-500': doc.requirement_level === 'recommended',
                      'bg-gray-400': doc.requirement_level === 'confirmation_only',
                    }"></span>
                  <span class="text-sm text-gray-800">{{ doc.template_name || doc.document_template_id }}</span>
                  <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-200 text-gray-500">{{ doc.template_category }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <select :value="doc.requirement_level"
                    @change="updateReqLevel(doc.id, $event.target.value)"
                    class="text-xs border border-gray-200 rounded px-1.5 py-0.5 outline-none">
                    <option value="required">{{ $t('countryDetail.levelRequired') }}</option>
                    <option value="recommended">{{ $t('countryDetail.levelRecommended') }}</option>
                    <option value="confirmation_only">{{ $t('countryDetail.levelConfirmation') }}</option>
                  </select>
                  <button @click="removeDoc(doc.id)" class="text-red-400 hover:text-red-600 text-xs p-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            <p v-else class="text-xs text-gray-400">{{ $t('countryDetail.noDocs') }}</p>
          </div>

          <!-- Actions -->
          <div class="flex gap-2 mt-4 pt-3 border-t border-gray-100">
            <button @click="startEdit(s)"
              class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
              {{ $t('countryDetail.edit') }}
            </button>
            <button @click="destroy(s)"
              class="text-xs px-3 py-1.5 border border-red-200 rounded-lg hover:bg-red-50 text-red-600">
              {{ $t('countryDetail.delete') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
      {{ $t('countryDetail.noVisaSettings') }}
    </div>

    <!-- Modal: Add Document -->
    <div v-if="docAddVisa" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-4">
          {{ $t('countryDetail.addDocTitle') }} — {{ docAddVisa }}
        </h3>
        <div class="space-y-3">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.docTemplate') }}</label>
            <select v-model="docForm.document_template_id"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="">{{ $t('countryDetail.selectDoc') }}</option>
              <option v-for="t in availableTemplates" :key="t.id" :value="t.id">
                {{ t.name }} ({{ t.category }})
              </option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.requirementLevel') }}</label>
            <select v-model="docForm.requirement_level"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="required">{{ $t('countryDetail.levelRequired') }}</option>
              <option value="recommended">{{ $t('countryDetail.levelRecommended') }}</option>
              <option value="confirmation_only">{{ $t('countryDetail.levelConfirmation') }}</option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.docNotes') }}</label>
            <input v-model="docForm.notes" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
        </div>
        <div class="flex gap-3 mt-4">
          <button @click="saveDoc" :disabled="!docForm.document_template_id || docSaving"
            class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
            {{ docSaving ? $t('common.loading') : $t('common.add') }}
          </button>
          <button @click="docAddVisa = null"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ $t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Add / Edit -->
    <div v-if="openAdd || editingSetting" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-4">
          {{ editingSetting ? $t('countryDetail.editVisaSetting') : $t('countryDetail.addVisaSetting') }}
        </h3>

        <form @submit.prevent="saveForm" class="space-y-3">
          <div v-if="!editingSetting">
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.visaTypeSlug') }}</label>
            <select v-model="formData.visa_type" required
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="" disabled>{{ $t('countryDetail.selectVisaType') }}</option>
              <option v-for="vt in availableVisaTypes" :key="vt.slug" :value="vt.slug">
                {{ vt.name_ru }} ({{ vt.slug }})
              </option>
            </select>
          </div>

          <h4 class="text-xs font-semibold text-gray-400 uppercase pt-2">{{ $t('countryDetail.timeline') }}</h4>
          <div class="grid grid-cols-3 gap-3">
            <NumField :label="$t('countryDetail.preparation')" v-model.number="formData.preparation_days" />
            <NumField :label="$t('countryDetail.appointmentWait')" v-model.number="formData.appointment_wait_days" />
            <NumField :label="$t('countryDetail.buffer')" v-model.number="formData.buffer_days" />
            <NumField :label="$t('countryDetail.processingMin')" v-model.number="formData.processing_days_min" />
            <NumField :label="$t('countryDetail.processingMax')" v-model.number="formData.processing_days_max" />
            <NumField :label="$t('countryDetail.processingAvg')" v-model.number="formData.processing_days_avg" />
          </div>

          <h4 class="text-xs font-semibold text-gray-400 uppercase pt-2">{{ $t('countryDetail.parameters') }}</h4>
          <div class="grid grid-cols-2 gap-3">
            <NumField :label="$t('countryDetail.refusalRate') + ' (%)'" v-model.number="formData.avg_refusal_rate" step="0.1" />
            <NumField :label="$t('countryDetail.consularFee') + ' ($)'" v-model.number="formData.consular_fee_usd" step="0.01" />
            <NumField :label="$t('countryDetail.serviceFee') + ' ($)'" v-model.number="formData.service_fee_usd" step="0.01" />
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.appointmentPattern') }}</label>
              <select v-model="formData.appointment_pattern"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="daily_slots">{{ $t('countryDetail.dailySlots') }}</option>
                <option value="fixed_schedule">{{ $t('countryDetail.fixedSchedule') }}</option>
                <option value="random_wave">{{ $t('countryDetail.randomWave') }}</option>
                <option value="no_appointment">{{ $t('countryDetail.noAppointment') }}</option>
              </select>
            </div>
          </div>

          <div class="flex flex-wrap gap-4 pt-2">
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="formData.biometrics_required" class="rounded" />
              {{ $t('countryDetail.biometrics') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="formData.personal_visit_required" class="rounded" />
              {{ $t('countryDetail.personalVisit') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="formData.interview_required" class="rounded" />
              {{ $t('countryDetail.interview') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="formData.is_active" class="rounded" />
              {{ $t('countryDetail.active') }}
            </label>
          </div>

          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.notes') }}</label>
            <textarea v-model="formData.notes" rows="2"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="saving"
              class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
              {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
            <button type="button" @click="openAdd = false; editingSetting = null"
              class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
              {{ $t('common.cancel') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';
import api from '@/api/index';

const { t } = useI18n();
const props = defineProps({ countryCode: String });
const emit  = defineEmits(['updated']);

const settings       = ref([]);
const loading        = ref(true);
const saving         = ref(false);
const expanded       = ref(null);
const openAdd        = ref(false);
const editingSetting = ref(null);

// Visa types reference
const allVisaTypes = ref([]);

const availableVisaTypes = computed(() => {
  const usedSlugs = settings.value.map(s => s.visa_type);
  return allVisaTypes.value.filter(vt => vt.is_active && !usedSlugs.includes(vt.slug));
});

// Documents
const requirements   = ref([]);
const templates      = ref([]);
const docAddVisa     = ref(null);
const docSaving      = ref(false);
const docForm        = reactive({ document_template_id: '', requirement_level: 'required', notes: '' });

const defaultForm = {
  visa_type: '', preparation_days: 7, appointment_wait_days: 10,
  processing_days_min: 5, processing_days_max: 15, processing_days_avg: 10,
  buffer_days: 7, avg_refusal_rate: null, biometrics_required: true,
  personal_visit_required: true, interview_required: false,
  appointment_pattern: 'daily_slots', consular_fee_usd: null,
  service_fee_usd: null, is_active: true, notes: '',
};

const formData = reactive({ ...defaultForm });

function toggle(id) {
  expanded.value = expanded.value === id ? null : id;
}

function startEdit(s) {
  editingSetting.value = s;
  Object.assign(formData, {
    visa_type: s.visa_type,
    preparation_days: s.preparation_days,
    appointment_wait_days: s.appointment_wait_days,
    processing_days_min: s.processing_days_min,
    processing_days_max: s.processing_days_max,
    processing_days_avg: s.processing_days_avg,
    buffer_days: s.buffer_days,
    avg_refusal_rate: s.avg_refusal_rate,
    biometrics_required: s.biometrics_required,
    personal_visit_required: s.personal_visit_required,
    interview_required: s.interview_required,
    appointment_pattern: s.appointment_pattern,
    consular_fee_usd: s.consular_fee_usd,
    service_fee_usd: s.service_fee_usd,
    is_active: s.is_active,
    notes: s.notes ?? '',
  });
}

async function loadSettings() {
  loading.value = true;
  try {
    const [settingsRes, reqRes, vtRes] = await Promise.all([
      ownerCountriesApi.visaSettings(props.countryCode),
      ownerCountriesApi.requirements(props.countryCode),
      api.get('/owner/visa-types'),
    ]);
    settings.value = settingsRes.data.data;
    requirements.value = reqRes.data.data ?? [];
    allVisaTypes.value = vtRes.data.data ?? [];
  } finally {
    loading.value = false;
  }
}

async function loadTemplates() {
  try {
    const { data } = await ownerCountriesApi.documentTemplates();
    templates.value = data.data ?? [];
  } catch { /* ignore */ }
}

function getDocsForVisa(visaType) {
  return requirements.value.filter(r => r.visa_type === visaType);
}

const availableTemplates = computed(() => {
  if (!docAddVisa.value) return templates.value;
  const existing = getDocsForVisa(docAddVisa.value).map(r => r.document_template_id);
  return templates.value.filter(t => t.is_active && !existing.includes(t.id));
});

function openDocAdd(visaType) {
  docAddVisa.value = visaType;
  docForm.document_template_id = '';
  docForm.requirement_level = 'required';
  docForm.notes = '';
  if (!templates.value.length) loadTemplates();
}

async function saveDoc() {
  docSaving.value = true;
  try {
    await ownerCountriesApi.requirementStore({
      country_code: props.countryCode,
      visa_type: docAddVisa.value,
      document_template_id: docForm.document_template_id,
      requirement_level: docForm.requirement_level,
      notes: docForm.notes || null,
      is_active: true,
    });
    docAddVisa.value = null;
    await loadSettings();
    emit('updated');
  } finally {
    docSaving.value = false;
  }
}

async function updateReqLevel(reqId, level) {
  try {
    await ownerCountriesApi.requirementUpdate(reqId, { requirement_level: level });
    const req = requirements.value.find(r => r.id === reqId);
    if (req) req.requirement_level = level;
  } catch { /* ignore */ }
}

async function removeDoc(reqId) {
  try {
    await ownerCountriesApi.requirementDestroy(reqId);
    requirements.value = requirements.value.filter(r => r.id !== reqId);
    emit('updated');
  } catch { /* ignore */ }
}

async function saveForm() {
  saving.value = true;
  try {
    if (editingSetting.value) {
      await ownerCountriesApi.visaSettingUpdate(props.countryCode, editingSetting.value.id, formData);
    } else {
      await ownerCountriesApi.visaSettingStore(props.countryCode, formData);
    }
    editingSetting.value = null;
    openAdd.value = false;
    Object.assign(formData, defaultForm);
    await loadSettings();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

async function destroy(s) {
  saving.value = true;
  try {
    await ownerCountriesApi.visaSettingDestroy(props.countryCode, s.id);
    await loadSettings();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(loadSettings);

// Mini component
const NumField = {
  props: ['label', 'modelValue', 'step'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="text-xs text-gray-500 mb-1 block">{{ label }}</label>
      <input :value="modelValue" @input="$emit('update:modelValue', parseFloat($event.target.value) || 0)"
        type="number" :step="step || 1" min="0"
        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
    </div>
  `,
};
</script>

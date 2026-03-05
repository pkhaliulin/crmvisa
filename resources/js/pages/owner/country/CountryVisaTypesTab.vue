<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm text-gray-500">{{ $t('countryDetail.visaTypesDesc') }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $t('countryDetail.visaTypesHint') }}</p>
      </div>
      <button @click="openAdd"
        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e]">
        + {{ $t('countryDetail.addVisaType') }}
      </button>
    </div>

    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <!-- Таблица -->
    <div v-else-if="settings.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500">
          <tr>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.name') }}</th>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.processingTime') }}</th>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.minDaysBefore') }}</th>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.fees') }}</th>
            <th class="px-5 py-3 font-medium text-center">{{ $t('countryDetail.status') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in settings" :key="s.id"
            @click="startEdit(s)"
            class="hover:bg-blue-50/50 transition-colors cursor-pointer">
            <td class="px-5 py-3 font-medium text-gray-800">{{ s.visa_type }}</td>
            <td class="px-5 py-3 text-gray-600">
              <span v-if="s.processing_days_avg">{{ s.processing_days_avg }} {{ $t('countryDetail.days') }}</span>
              <span v-else class="text-gray-300">---</span>
            </td>
            <td class="px-5 py-3 text-gray-600">
              <span v-if="s.min_days_before_departure" class="font-semibold">{{ s.min_days_before_departure }} {{ $t('countryDetail.days') }}</span>
              <span v-else class="text-gray-300">---</span>
            </td>
            <td class="px-5 py-3 text-gray-600">
              <span v-if="s.consular_fee_usd">${{ s.consular_fee_usd }}</span>
              <span v-else class="text-gray-300">---</span>
            </td>
            <td class="px-5 py-3 text-center" @click.stop>
              <button @click="toggleActive(s)"
                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none"
                :class="s.is_active ? 'bg-[#1BA97F]' : 'bg-gray-300'">
                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200"
                  :class="s.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
      {{ $t('countryDetail.noVisaSettings') }}
    </div>

    <!-- Modal: Add / Edit (full form) -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/40 flex items-start justify-center p-4 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-6 my-8">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-bold text-[#0A1F44] text-lg">
            {{ editingSetting ? $t('countryDetail.editVisaSetting') : $t('countryDetail.addVisaSetting') }}
          </h3>
          <button type="button" @click="showModal = false; editingSetting = null"
            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <form @submit.prevent="saveForm" class="space-y-6">

          <!-- Тип визы + статус -->
          <div class="grid grid-cols-2 gap-4">
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
            <div v-else>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.visaTypeSlug') }}</label>
              <div class="text-sm font-semibold text-gray-800 py-2">{{ formData.visa_type }}</div>
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.status') }}</label>
              <label class="flex items-center gap-2 text-sm mt-2">
                <input type="checkbox" v-model="formData.is_active" class="rounded" />
                {{ $t('countryDetail.active') }}
              </label>
            </div>
          </div>

          <!-- Сроки обработки -->
          <fieldset class="border border-gray-200 rounded-xl p-4">
            <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.processingTimeline') }}</legend>
            <p class="text-[11px] text-gray-400 mb-3">{{ $t('countryDetail.processingTimelineHint') }}</p>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.preparationDays') }}</label>
                <input v-model.number="formData.preparation_days" type="number" min="0" max="365"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.preparationDaysHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.appointmentWaitDays') }}</label>
                <input v-model.number="formData.appointment_wait_days" type="number" min="0" max="365"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.appointmentWaitHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.bufferDays') }}</label>
                <input v-model.number="formData.buffer_days" type="number" min="0" max="365"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.bufferDaysHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.processingMin') }}</label>
                <input v-model.number="formData.processing_days_min" type="number" min="0" max="365"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.processingMinHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.processingAvg') }}</label>
                <input v-model.number="formData.processing_days_avg" type="number" min="0" max="365"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.processingAvgHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.processingMax') }}</label>
                <input v-model.number="formData.processing_days_max" type="number" min="0" max="365"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.processingMaxHint') }}</p>
              </div>
            </div>
            <div class="mt-3 p-3 bg-blue-50 rounded-lg text-xs text-blue-600">
              {{ $t('countryDetail.autoCalcNote') }}
              <span v-if="calcMinDays" class="font-semibold">{{ calcMinDays }} {{ $t('countryDetail.days') }}</span>
              <p class="text-[10px] text-blue-500 mt-1">{{ $t('countryDetail.autoCalcExplain') }}</p>
            </div>
          </fieldset>

          <!-- Требования к подаче -->
          <fieldset class="border border-gray-200 rounded-xl p-4">
            <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.submissionReqs') }}</legend>
            <p class="text-[11px] text-gray-400 mb-3">{{ $t('countryDetail.submissionReqsHint') }}</p>
            <div class="grid grid-cols-2 gap-3">
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="formData.biometrics_required" class="rounded" />
                {{ $t('countryDetail.biometricsRequired') }}
              </label>
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="formData.personal_visit_required" class="rounded" />
                {{ $t('countryDetail.personalVisitRequired') }}
              </label>
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="formData.interview_required" class="rounded" />
                {{ $t('countryDetail.interviewRequired') }}
              </label>
              <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="formData.parallel_docs_allowed" class="rounded" />
                {{ $t('countryDetail.parallelDocsAllowed') }}
              </label>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.appointmentPattern') }}</label>
                <select v-model="formData.appointment_pattern"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                  <option value="">---</option>
                  <option value="fixed_schedule">{{ $t('countryDetail.patternFixed') }}</option>
                  <option value="random_wave">{{ $t('countryDetail.patternRandom') }}</option>
                  <option value="daily_slots">{{ $t('countryDetail.patternDaily') }}</option>
                  <option value="no_appointment">{{ $t('countryDetail.patternNoAppt') }}</option>
                </select>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.appointmentPatternHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.avgRefusalRate') }} (%)</label>
                <input v-model.number="formData.avg_refusal_rate" type="number" min="0" max="100" step="0.1"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.avgRefusalRateHint') }}</p>
              </div>
            </div>
            <div class="mt-3">
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.appointmentNotes') }}</label>
              <textarea v-model="formData.appointment_notes" rows="2"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"
                :placeholder="$t('countryDetail.appointmentNotesPlaceholder')"></textarea>
            </div>
          </fieldset>

          <!-- Стоимость -->
          <fieldset class="border border-gray-200 rounded-xl p-4">
            <legend class="text-xs font-semibold text-gray-500 uppercase px-2">{{ $t('countryDetail.feesTitle') }}</legend>
            <p class="text-[11px] text-gray-400 mb-3">{{ $t('countryDetail.feesTitleHint') }}</p>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.consularFee') }} ($)</label>
                <input v-model.number="formData.consular_fee_usd" type="number" min="0" step="0.01"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.consularFeeHint') }}</p>
              </div>
              <div>
                <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.serviceFee') }} ($)</label>
                <input v-model.number="formData.service_fee_usd" type="number" min="0" step="0.01"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('countryDetail.serviceFeeHint') }}</p>
              </div>
            </div>
          </fieldset>

          <!-- Описание + Заметки -->
          <div class="grid grid-cols-1 gap-3">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.description') }}</label>
              <textarea v-model="formData.description" rows="2"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"
                :placeholder="$t('countryDetail.visaTypeDescPlaceholder')"></textarea>
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.notes') }}</label>
              <textarea v-model="formData.notes" rows="2"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"
                :placeholder="$t('countryDetail.notesPlaceholder')"></textarea>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="saving"
              class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
              {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
            <button type="button" @click="showModal = false; editingSetting = null"
              class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
              {{ $t('common.cancel') }}
            </button>
          </div>
          <div v-if="editingSetting" class="pt-3 border-t border-gray-100 mt-3">
            <button type="button" @click="deleteTarget = editingSetting; showModal = false"
              class="w-full py-2.5 text-sm font-medium text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">
              {{ $t('countryDetail.deleteVisaType') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Confirm Delete -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-red-600 text-lg mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-1">{{ $t('common.confirmDeleteMessage') }}</p>
        <p class="text-sm font-medium text-gray-800 mb-4">{{ deleteTarget.visa_type }}</p>
        <div class="flex gap-3">
          <button @click="confirmDestroy" :disabled="saving"
            class="flex-1 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? $t('common.loading') : $t('common.confirmDeleteBtn') }}
          </button>
          <button @click="deleteTarget = null"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ $t('common.cancel') }}
          </button>
        </div>
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
const props = defineProps({ countryCode: String, visaSettings: Array });
const emit  = defineEmits(['updated']);

const settings       = ref([]);
const loading        = ref(true);
const saving         = ref(false);
const showModal      = ref(false);
const editingSetting = ref(null);
const deleteTarget   = ref(null);
const allVisaTypes   = ref([]);

const availableVisaTypes = computed(() => {
  const usedSlugs = settings.value.map(s => s.visa_type);
  return allVisaTypes.value.filter(vt => vt.is_active && !usedSlugs.includes(vt.slug));
});

const defaultForm = {
  visa_type: '', is_active: true, description: '', notes: '',
  preparation_days: 5, appointment_wait_days: 10,
  processing_days_min: 5, processing_days_max: 30, processing_days_avg: 15,
  buffer_days: 7, parallel_docs_allowed: true,
  biometrics_required: false, personal_visit_required: false, interview_required: false,
  appointment_pattern: '', appointment_notes: '',
  consular_fee_usd: null, service_fee_usd: null, avg_refusal_rate: null,
};

const formData = reactive({ ...defaultForm });

const calcMinDays = computed(() => {
  const avg = formData.processing_days_avg || 0;
  const wait = formData.appointment_wait_days || 0;
  const buf = formData.buffer_days || 0;
  return avg + wait + buf;
});

function openAdd() {
  editingSetting.value = null;
  Object.assign(formData, { ...defaultForm });
  showModal.value = true;
}

function startEdit(s) {
  editingSetting.value = s;
  Object.assign(formData, {
    visa_type: s.visa_type,
    is_active: s.is_active ?? true,
    description: s.description ?? '',
    notes: s.notes ?? '',
    preparation_days: s.preparation_days ?? 5,
    appointment_wait_days: s.appointment_wait_days ?? 10,
    processing_days_min: s.processing_days_min ?? 5,
    processing_days_max: s.processing_days_max ?? 30,
    processing_days_avg: s.processing_days_avg ?? 15,
    buffer_days: s.buffer_days ?? 7,
    parallel_docs_allowed: s.parallel_docs_allowed ?? true,
    biometrics_required: s.biometrics_required ?? false,
    personal_visit_required: s.personal_visit_required ?? false,
    interview_required: s.interview_required ?? false,
    appointment_pattern: s.appointment_pattern ?? '',
    appointment_notes: s.appointment_notes ?? '',
    consular_fee_usd: s.consular_fee_usd ?? null,
    service_fee_usd: s.service_fee_usd ?? null,
    avg_refusal_rate: s.avg_refusal_rate ?? null,
  });
  showModal.value = true;
}

async function toggleActive(s) {
  try {
    await ownerCountriesApi.visaSettingUpdate(props.countryCode, s.id, { is_active: !s.is_active });
    s.is_active = !s.is_active;
  } catch { /* ignore */ }
}

async function loadSettings() {
  loading.value = true;
  try {
    const [settingsRes, vtRes] = await Promise.all([
      ownerCountriesApi.visaSettings(props.countryCode),
      api.get('/owner/visa-types'),
    ]);
    settings.value = settingsRes.data.data;
    allVisaTypes.value = vtRes.data.data ?? [];
  } finally {
    loading.value = false;
  }
}

async function saveForm() {
  saving.value = true;
  try {
    const payload = { ...formData };
    if (editingSetting.value) {
      delete payload.visa_type;
      await ownerCountriesApi.visaSettingUpdate(props.countryCode, editingSetting.value.id, payload);
    } else {
      await ownerCountriesApi.visaSettingStore(props.countryCode, payload);
    }
    editingSetting.value = null;
    showModal.value = false;
    await loadSettings();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

async function confirmDestroy() {
  saving.value = true;
  try {
    await ownerCountriesApi.visaSettingDestroy(props.countryCode, deleteTarget.value.id);
    deleteTarget.value = null;
    await loadSettings();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(loadSettings);
</script>

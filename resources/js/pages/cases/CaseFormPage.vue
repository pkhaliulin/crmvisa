<template>
  <div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">

      <!-- Header with back -->
      <div class="flex items-center gap-3 mb-6">
        <button @click="$router.back()"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <h2 class="text-lg font-bold text-gray-900">{{ t('crm.caseForm.title') }}</h2>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">

        <!-- Клиент -->
        <div class="relative">
          <label class="text-sm font-medium text-gray-700 block mb-1">
            {{ t('crm.caseForm.client') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="clientSearch"
              @input="onClientInput"
              @focus="onClientFocus"
              :placeholder="t('crm.caseForm.clientSearch')"
              :class="[
                'w-full border rounded-lg px-3 py-2 text-sm outline-none pr-8 transition-colors',
                form.client_id
                  ? 'border-green-500 bg-green-50 text-green-800 font-medium'
                  : errors.client_id ? 'border-red-400' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
              ]"
            />
            <button v-if="form.client_id" type="button" @click="clearClient"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">
              ×
            </button>
          </div>
          <!-- Дропдаун клиентов -->
          <div v-if="clientResults.length"
            class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg divide-y text-sm max-h-48 overflow-y-auto">
            <button v-for="c in clientResults" :key="c.id" type="button"
              class="w-full text-left px-3 py-2.5 hover:bg-blue-50 flex items-center gap-3"
              @click="selectClient(c)">
              <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                {{ c.name?.[0] }}
              </div>
              <div>
                <div class="font-medium text-gray-900">{{ c.name }}</div>
                <div class="text-xs text-gray-400">{{ formatPhone(c.phone) }}</div>
              </div>
            </button>
          </div>
          <!-- Нет результатов -->
          <div v-if="showNoClientResults"
            class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg px-3 py-2.5 text-sm text-gray-400">
            {{ t('crm.caseForm.clientNotFound') }}
            <RouterLink :to="{ name: 'clients.create' }" class="text-blue-600 hover:underline">{{ t('crm.caseForm.createNew') }}</RouterLink>
          </div>
          <p v-if="errors.client_id" class="text-xs text-red-600 mt-1">{{ errors.client_id }}</p>
        </div>

        <!-- Страна + Тип визы -->
        <div class="grid grid-cols-2 gap-4">

          <!-- Страна с автодроп-дауном -->
          <div class="relative">
            <label class="text-sm font-medium text-gray-700 block mb-1">
              {{ t('crm.caseForm.country') }} <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="countrySearch"
                @input="onCountryInput"
                @focus="onCountryFocus"
                @blur="onCountryBlur"
                :placeholder="t('crm.caseForm.countrySearch')"
                :class="[
                  'w-full border rounded-lg px-3 py-2 text-sm outline-none pr-7 transition-colors',
                  form.country_code
                    ? 'border-green-500 bg-green-50 text-green-800 font-medium'
                    : errors.country_code ? 'border-red-400' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
                ]"
              />
              <button v-if="form.country_code" type="button" @click="clearCountry"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">
                ×
              </button>
            </div>
            <!-- Дропдаун стран -->
            <div v-if="countryDropdownVisible && filteredCountries.length"
              class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg text-sm max-h-48 overflow-y-auto">
              <button v-for="c in filteredCountries" :key="c.country_code" type="button"
                class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center gap-2"
                @mousedown.prevent="selectCountry(c)">
                <span class="text-base">{{ c.flag_emoji }}</span>
                <span class="text-gray-900">{{ c.name }}</span>
                <span class="ml-auto font-mono text-xs text-gray-400">{{ c.country_code }}</span>
              </button>
            </div>
            <p v-if="errors.country_code" class="text-xs text-red-600 mt-1">{{ errors.country_code }}</p>
          </div>

          <!-- Тип визы -->
          <div class="flex flex-col gap-1">
            <SearchSelect
              v-model="form.visa_type"
              :items="visaTypeItems"
              :label="t('crm.caseForm.visaType')"
              :placeholder="form.country_code ? t('crm.caseForm.selectVisa') : t('crm.caseForm.selectCountryFirst')"
              :disabled="!form.country_code"
              required
              allow-all
              :all-label="form.country_code ? t('crm.caseForm.selectVisa') : t('crm.caseForm.selectCountryFirst')"
            />
            <p v-if="errors.visa_type" class="text-xs text-red-600">{{ errors.visa_type }}</p>
          </div>
        </div>

        <AppSelect v-model="form.priority" :label="t('crm.casesPage.priorityFilter')" :options="priorityOptions" />

        <!-- Менеджер -->
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('crm.caseForm.manager') }}</label>
          <template v-if="assignmentMode === 'manual'">
            <SearchSelect
              v-model="form.assigned_to"
              :items="managerItems"
              :placeholder="t('crm.caseForm.notAssigned')"
              allow-all
              :all-label="t('crm.caseForm.notAssigned')"
            />
          </template>
          <template v-else>
            <div class="flex items-center gap-3 px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg">
              <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              <span class="text-sm text-blue-700">{{ t('crm.caseForm.autoAssign', { mode: autoModeLabels[assignmentMode] }) }}</span>
            </div>
            <div class="mt-2">
              <SearchSelect
                v-model="form.assigned_to"
                :items="managerItemsManual"
                :placeholder="t('crm.caseForm.keepAuto')"
                allow-all
                :all-label="t('crm.caseForm.keepAuto')"
              />
            </div>
          </template>
        </div>
        <div>
          <AppInput v-model="form.travel_date" :label="t('crm.caseForm.travelDate')" type="date" :error="errors.travel_date" />
          <!-- Timeline preview (per-visa-type) -->
          <div v-if="currentVisaSetting && form.travel_date" class="mt-2 p-3 bg-blue-50 rounded-lg">
            <div class="flex gap-1 h-5 rounded overflow-hidden text-[9px] font-medium text-white mb-1.5">
              <div class="flex items-center justify-center bg-blue-400" :style="{ flex: currentVisaSetting.preparation_days }">
                {{ currentVisaSetting.preparation_days }}
              </div>
              <div class="flex items-center justify-center bg-orange-400" :style="{ flex: currentVisaSetting.appointment_wait_days }">
                {{ currentVisaSetting.appointment_wait_days }}
              </div>
              <div class="flex items-center justify-center bg-purple-500" :style="{ flex: currentVisaSetting.processing_days_avg }">
                {{ currentVisaSetting.processing_days_avg }}
              </div>
              <div class="flex items-center justify-center bg-gray-400" :style="{ flex: currentVisaSetting.buffer_days }">
                {{ currentVisaSetting.buffer_days }}
              </div>
            </div>
            <div class="flex gap-1 text-[9px] text-gray-500">
              <div :style="{ flex: currentVisaSetting.preparation_days }">{{ t('crm.caseForm.preparation') }}</div>
              <div :style="{ flex: currentVisaSetting.appointment_wait_days }">{{ t('crm.caseForm.appointmentWait') }}</div>
              <div :style="{ flex: currentVisaSetting.processing_days_avg }">{{ t('crm.caseForm.processing') }}</div>
              <div :style="{ flex: currentVisaSetting.buffer_days }">{{ t('crm.caseForm.buffer') }}</div>
            </div>
          </div>
          <p v-if="suggestedDeadline" class="mt-1 text-xs text-blue-600 flex items-center gap-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ t('crm.caseForm.recommendedDeadline') }} <strong>{{ suggestedDeadline }}</strong>
            {{ t('crm.caseForm.daysBeforeTrip', { n: selectedCountryDays }) }}
          </p>
          <!-- Late submission warning -->
          <p v-if="lateSubmissionWarning" class="mt-1 text-xs text-red-600 flex items-center gap-1 bg-red-50 px-2 py-1.5 rounded-lg">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            {{ t('crm.caseForm.tooLateWarning', { n: minDaysBeforeDeparture }) }}
          </p>
        </div>
        <AppInput v-model="form.critical_date" :label="t('crm.caseForm.deadlineOptional')" type="date" />

        <AppTextarea
          v-model="form.notes"
          :label="t('crm.caseForm.notes')"
          :placeholder="t('crm.caseForm.notesPlaceholder')"
          :maxlength="1000"
          :rows="3"
        />

        <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

        <div class="flex gap-3 pt-2">
          <AppButton type="submit" :loading="loading">{{ t('crm.caseForm.submit') }}</AppButton>
          <RouterLink :to="{ name: 'cases' }">
            <AppButton type="button" variant="outline">{{ t('crm.caseForm.cancel') }}</AppButton>
          </RouterLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { casesApi } from '@/api/cases';
import { clientsApi } from '@/api/clients';
import { countriesApi } from '@/api/countries';
import { usersApi } from '@/api/users';
import api from '@/api/index';
import AppInput from '@/components/AppInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';
import SearchSelect from '@/components/SearchSelect.vue';
import { formatPhone, titleCase } from '@/utils/format';

const { t } = useI18n();

const allVisaTypes = ref([]);
function visaTypeName(slug) {
  return allVisaTypes.value.find(t => t.slug === slug)?.name_ru ?? slug;
}

const router = useRouter();
const route  = useRoute();
const form   = reactive({
  client_id: '', country_code: '', visa_type: '', priority: 'normal',
  assigned_to: '', travel_date: '', critical_date: '', notes: '',
});

const managers          = ref([]);
const assignmentMode    = ref('manual'); // manual | round_robin | by_workload | by_country
const managerOptions    = computed(() => [
  { value: '', label: t('crm.caseForm.notAssigned') },
  ...managers.value.map(u => ({ value: u.id, label: u.name })),
]);
const managerItems = computed(() =>
  managers.value.map(u => ({ value: u.id, label: u.name }))
);
const managerItemsManual = computed(() =>
  managers.value.map(u => ({ value: u.id, label: `${u.name} ${t('crm.caseForm.manualSuffix')}` }))
);
const visaTypeItems = computed(() =>
  selectedCountryVisaTypes.value.map(slug => ({ value: slug, label: visaTypeName(slug) }))
);
const autoModeLabels = computed(() => ({
  round_robin: t('crm.caseForm.autoModes.round_robin'),
  by_workload: t('crm.caseForm.autoModes.least_busy'),
  by_country:  t('crm.caseForm.autoModes.by_country'),
}));
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

// ── Клиент ──────────────────────────────────────────────────────────────────
const clientSearch   = ref('');
const clientResults  = ref([]);
const clientSearched = ref(false);

const showNoClientResults = computed(() =>
  clientSearched.value && !clientResults.value.length && clientSearch.value.length >= 2 && !form.client_id
);

let clientDebounce;

onBeforeUnmount(() => {
  clearTimeout(clientDebounce);
});

function onClientInput() {
  if (form.client_id) {
    form.client_id = '';
    clientResults.value = [];
    clientSearched.value = false;
    return;
  }
  clearTimeout(clientDebounce);
  if (clientSearch.value.length < 2) {
    clientResults.value = [];
    clientSearched.value = false;
    return;
  }
  clientDebounce = setTimeout(async () => {
    const { data } = await clientsApi.list({ q: clientSearch.value });
    clientResults.value = data.data?.data ?? data.data ?? [];
    clientSearched.value = true;
  }, 300);
}

function onClientFocus() {
  if (!form.client_id && clientSearch.value.length >= 2 && !clientResults.value.length) {
    onClientInput();
  }
}

function selectClient(c) {
  form.client_id       = c.id;
  clientSearch.value   = `${c.name} — ${formatPhone(c.phone)}`;
  clientResults.value  = [];
  clientSearched.value = false;
  if (errors.value.client_id) delete errors.value.client_id;
}

function clearClient() {
  form.client_id       = '';
  clientSearch.value   = '';
  clientResults.value  = [];
  clientSearched.value = false;
}

// ── Страна ──────────────────────────────────────────────────────────────────
const allCountries = ref([]);
const countrySearch = ref('');
const countryDropdownVisible = ref(false);

const selectedCountryVisaTypes = computed(() => {
  if (!form.country_code) return [];
  const c = allCountries.value.find(c => c.country_code === form.country_code);
  return c?.visa_types ?? ['tourist', 'student', 'business'];
});

onMounted(async () => {
  // Предзаполнение клиента из query (переход из карточки клиента)
  if (route.query.client_id) {
    form.client_id     = route.query.client_id;
    clientSearch.value = route.query.client_label ?? '';
  }

  try {
    const [cRes, vtRes, uRes, sRes] = await Promise.all([
      countriesApi.list(),
      countriesApi.visaTypes(),
      usersApi.list(),
      api.get('/agency/settings'),
    ]);
    allCountries.value  = cRes.data.data ?? [];
    allVisaTypes.value  = vtRes.data.data ?? [];
    managers.value      = (uRes.data.data ?? []).filter(u => ['manager','owner'].includes(u.role));
    assignmentMode.value = sRes.data.data?.lead_assignment_mode ?? 'manual';
  } catch {
    // fallback
  }
});

// Per-visa-type настройки
const visaTypeSettings = ref([]);
const currentVisaSetting = computed(() => {
  if (!form.country_code || !form.visa_type) return null;
  return visaTypeSettings.value.find(s => s.visa_type === form.visa_type) ?? null;
});

// Подсказка: рекомендуемый дедлайн — per-visa-type → fallback на portal_countries
const selectedCountryDays = computed(() => {
  if (currentVisaSetting.value) {
    return currentVisaSetting.value.recommended_days_before_departure;
  }
  if (!form.country_code) return 0;
  const c = allCountries.value.find(c => c.country_code === form.country_code);
  return (c?.processing_days_standard ?? 0) + (c?.appointment_wait_days ?? 0) + (c?.buffer_days_recommended ?? 0);
});

const minDaysBeforeDeparture = computed(() => {
  if (currentVisaSetting.value) {
    return currentVisaSetting.value.min_days_before_departure;
  }
  return 0;
});

const lateSubmissionWarning = computed(() => {
  if (!form.travel_date || !minDaysBeforeDeparture.value) return false;
  const daysLeft = Math.ceil((new Date(form.travel_date) - new Date()) / 86400000);
  return daysLeft < minDaysBeforeDeparture.value;
});

const suggestedDeadline = computed(() => {
  if (!form.travel_date || !form.country_code || !selectedCountryDays.value) return null;
  const d = new Date(form.travel_date);
  d.setDate(d.getDate() - selectedCountryDays.value);
  return d.toLocaleDateString('uz-UZ', { day: 'numeric', month: 'long', year: 'numeric' });
});

// Загрузка per-visa-type данных при выборе страны
async function loadVisaSettings(countryCode) {
  try {
    const { data } = await countriesApi.visaSettingsPublic(countryCode);
    visaTypeSettings.value = data.data ?? [];
  } catch {
    visaTypeSettings.value = [];
  }
}

const filteredCountries = computed(() => {
  const q = countrySearch.value.trim().toLowerCase();
  if (!q) return allCountries.value;
  return allCountries.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.country_code.toLowerCase().startsWith(q)
  );
});

function onCountryInput() {
  if (form.country_code) {
    form.country_code = '';
    if (errors.value.country_code) delete errors.value.country_code;
  }
  countryDropdownVisible.value = true;
}

function onCountryFocus() {
  countryDropdownVisible.value = true;
}

function onCountryBlur() {
  setTimeout(() => { countryDropdownVisible.value = false; }, 150);
}

function selectCountry(c) {
  form.country_code    = c.country_code;
  form.visa_type       = '';
  countrySearch.value  = c.name;
  countryDropdownVisible.value = false;
  if (errors.value.country_code) delete errors.value.country_code;
  loadVisaSettings(c.country_code);
}

function clearCountry() {
  form.country_code    = '';
  form.visa_type       = '';
  countrySearch.value  = '';
  countryDropdownVisible.value = false;
}

// ── Форма ────────────────────────────────────────────────────────────────────
const priorityOptions = computed(() => [
  { value: 'low',    label: t('crm.priority.low') },
  { value: 'normal', label: t('crm.priority.normal') },
  { value: 'high',   label: t('crm.priority.high') },
  { value: 'urgent', label: t('crm.priority.urgent') },
]);

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  if (!form.client_id) {
    errors.value.client_id = t('crm.caseForm.selectClient');
    return;
  }
  if (!form.country_code) {
    errors.value.country_code = t('crm.caseForm.selectCountry');
    return;
  }
  if (!form.visa_type) {
    errors.value.visa_type = t('crm.caseForm.selectVisaType');
    return;
  }

  loading.value = true;

  const payload = { ...form };
  if (!payload.critical_date) delete payload.critical_date;
  if (!payload.travel_date)   delete payload.travel_date;
  if (!payload.notes)         delete payload.notes;
  if (!payload.assigned_to)   delete payload.assigned_to;

  try {
    const { data } = await casesApi.create(payload);
    router.push({ name: 'cases.show', params: { id: data.data.id } });
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = d?.message || t('crm.caseForm.createError');
    }
  } finally {
    loading.value = false;
  }
}
</script>

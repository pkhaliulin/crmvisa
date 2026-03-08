<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <button @click="router.push({ name: 'services' })"
        class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-gray-600 hover:border-gray-300 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span v-if="pkg.country_code" class="text-2xl">{{ codeToFlag(pkg.country_code) }}</span>
          <h1 class="text-xl font-bold text-gray-900 truncate">{{ pkg.name || t('crm.serviceDetail.title') }}</h1>
          <span v-if="!pkg.is_active" class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full">{{ t('crm.serviceDetail.inactive') }}</span>
        </div>
        <p v-if="pkg.name_uz" class="text-sm text-gray-400 mt-0.5">{{ pkg.name_uz }}</p>
      </div>
      <div class="flex gap-2 shrink-0">
        <button @click="editing = !editing"
          class="px-3 py-2 text-sm font-medium rounded-lg border transition-colors"
          :class="editing ? 'border-amber-300 bg-amber-50 text-amber-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50'">
          {{ editing ? t('common.cancel') : t('crm.serviceDetail.edit') }}
        </button>
        <button @click="confirmDelete"
          class="px-3 py-2 text-sm font-medium rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors">
          {{ t('crm.serviceDetail.delete') }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Просмотр -->
      <template v-if="!editing">
        <!-- Основная информация -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-gray-100">
            <div class="px-4 py-4 text-center">
              <div class="text-xs text-gray-400 mb-1">{{ t('crm.serviceDetail.cost') }}</div>
              <div class="font-bold text-lg text-gray-900">{{ formatPrice(pkg.price) }}</div>
              <div class="text-xs text-gray-400">{{ pkg.currency || 'UZS' }}</div>
            </div>
            <div class="px-4 py-4 text-center">
              <div class="text-xs text-gray-400 mb-1">{{ t('crm.serviceDetail.term') }}</div>
              <div class="font-bold text-lg text-gray-900">{{ pkg.processing_days }}</div>
              <div class="text-xs text-gray-400">{{ t('crm.serviceDetail.termDays') }}</div>
            </div>
            <div class="px-4 py-4 text-center">
              <div class="text-xs text-gray-400 mb-1">{{ t('crm.serviceDetail.country') }}</div>
              <div class="font-bold text-sm text-gray-900">{{ countryName(pkg.country_code) }}</div>
            </div>
            <div class="px-4 py-4 text-center">
              <div class="text-xs text-gray-400 mb-1">{{ t('crm.serviceDetail.visaType') }}</div>
              <div class="font-bold text-sm text-gray-900">{{ visaTypeName(pkg.visa_type) }}</div>
            </div>
          </div>
        </div>

        <!-- Описание -->
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3">
          <h3 class="text-sm font-semibold text-gray-600">{{ t('crm.serviceDetail.description') }}</h3>
          <div v-if="pkg.description" class="text-sm text-gray-700 leading-relaxed">
            <span class="text-xs text-gray-400 mr-1">{{ t('crm.serviceDetail.ruLabel') }}</span>{{ pkg.description }}
          </div>
          <div v-if="pkg.description_uz" class="text-sm text-gray-700 leading-relaxed">
            <span class="text-xs text-gray-400 mr-1">{{ t('crm.serviceDetail.uzLabel') }}</span>{{ pkg.description_uz }}
          </div>
        </div>

        <!-- Включённые услуги -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-600">{{ t('crm.serviceDetail.includedServices') }}</h3>
            <span class="text-xs text-gray-400">{{ viewServices.length }}</span>
          </div>

          <!-- Обязательные услуги -->
          <div v-if="viewRequiredServices.length">
            <div class="bg-red-50/50 px-5 py-1.5 border-b border-red-100/50">
              <span class="text-[10px] font-bold text-red-600 uppercase tracking-wide">{{ t('crm.serviceDetail.requiredServices') }}</span>
            </div>
            <div v-for="svc in viewRequiredServices" :key="svc.id" class="px-5 py-3 bg-red-50/20 border-b border-red-100/30">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-800">{{ svc.name }}</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold">{{ t('crm.serviceDetail.requiredBadge') }}</span>
                <span class="text-xs text-gray-400 ml-auto">{{ categoryLabel(svc.category) }}</span>
              </div>
              <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-1 leading-relaxed">{{ svc.agency_hint }}</p>
              <p v-else-if="svc.description" class="text-xs text-gray-400 mt-1">{{ svc.description }}</p>
            </div>
          </div>

          <!-- Дополнительные услуги из пакета -->
          <div v-if="viewOptionalItems.length">
            <div class="bg-gray-50 px-5 py-1.5 border-b border-gray-100">
              <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">{{ t('crm.serviceDetail.optionalServices') }}</span>
            </div>
            <div v-for="item in viewOptionalItems" :key="item.id" class="px-5 py-3 border-b border-gray-50">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-800">{{ item.service?.name }}</span>
                <span class="text-xs text-gray-400 ml-auto">{{ categoryLabel(item.service?.category) }}</span>
              </div>
              <p v-if="item.service?.agency_hint" class="text-xs text-gray-500 mt-1 leading-relaxed">{{ item.service.agency_hint }}</p>
              <p v-else-if="item.service?.description" class="text-xs text-gray-400 mt-1">{{ item.service.description }}</p>
            </div>
          </div>

          <div v-if="!viewServices.length" class="px-5 py-6 text-center text-sm text-gray-400">{{ t('crm.serviceDetail.noServices') }}</div>
        </div>

        <!-- Статус -->
        <div class="flex items-center gap-3">
          <span class="text-sm text-gray-500">{{ t('crm.serviceDetail.statusLabel') }}</span>
          <span class="text-sm font-medium" :class="pkg.is_active ? 'text-green-600' : 'text-gray-400'">
            {{ pkg.is_active ? t('crm.serviceDetail.statusActive') : t('crm.serviceDetail.statusInactive') }}
          </span>
        </div>
      </template>

      <!-- Редактирование -->
      <template v-else>
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
          <!-- Ошибки -->
          <div v-if="formErrors.length" class="bg-red-50 border border-red-200 rounded-lg p-3">
            <ul class="text-xs text-red-600 space-y-0.5">
              <li v-for="err in formErrors" :key="err">{{ err }}</li>
            </ul>
          </div>

          <!-- Автоназвание -->
          <div v-if="autoName" class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
            <div class="text-xs text-blue-500 font-medium mb-1">{{ t('crm.services.packageName') }}</div>
            <div class="font-semibold text-gray-900 text-sm">{{ autoName }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ autoNameUz }}</div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.serviceDetail.country') }}</label>
              <div class="relative">
                <input v-model="countrySearch" @input="onCountryInput" @focus="countryDropdown = true"
                  @blur="() => setTimeout(() => countryDropdown = false, 200)"
                  :placeholder="t('crm.services.countrySearch')"
                  :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none pr-6',
                    form.country_code ? 'border-green-500 bg-green-50 text-green-800 font-medium' : 'border-gray-200 focus:border-blue-400']" />
                <button v-if="form.country_code" type="button" @click="clearCountry"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-sm">x</button>
              </div>
              <div v-if="countryDropdown && filteredCountries.length"
                class="absolute left-0 right-0 z-30 mt-1 border border-gray-200 rounded-lg bg-white shadow-lg text-sm max-h-48 overflow-y-auto">
                <button v-for="c in filteredCountries.slice(0, 20)" :key="c.country_code" type="button"
                  class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center gap-2"
                  @mousedown.prevent="selectCountry(c)">
                  <span class="text-base">{{ c.flag_emoji }}</span>
                  <span class="text-gray-900">{{ c.name }}</span>
                  <span class="ml-auto text-xs text-gray-400 font-mono">{{ c.country_code }}</span>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.serviceDetail.visaType') }}</label>
              <select v-model="form.visa_type" :disabled="!form.country_code"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 disabled:bg-gray-50 disabled:text-gray-400">
                <option value="">{{ form.country_code ? t('crm.services.selectDefault') : t('crm.services.selectCountryFirst') }}</option>
                <option v-for="slug in selectedCountryVisaTypes" :key="slug" :value="slug">{{ visaTypeName(slug) }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.priceLabel') }}</label>
              <input v-model.number="form.price" type="number" min="0" placeholder="2000000"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.daysLabel') }}</label>
              <input v-model.number="form.processing_days" type="number" min="1" placeholder="14"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">{{ t('crm.services.descRu') }}</label>
              <span class="text-xs" :class="(form.description?.length || 0) > 450 ? 'text-red-500' : 'text-gray-400'">
                {{ form.description?.length || 0 }}/500
              </span>
            </div>
            <textarea v-model="form.description" rows="2" maxlength="500" :placeholder="t('crm.services.descRuPlaceholder')"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400"></textarea>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">{{ t('crm.services.descUz') }}</label>
              <span class="text-xs" :class="(form.description_uz?.length || 0) > 450 ? 'text-red-500' : 'text-gray-400'">
                {{ form.description_uz?.length || 0 }}/500
              </span>
            </div>
            <textarea v-model="form.description_uz" rows="2" maxlength="500" :placeholder="t('crm.services.descUzPlaceholder')"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400"></textarea>
          </div>

          <!-- Услуги -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.serviceDetail.includedServices') }}</label>
            <div class="space-y-0.5 border border-gray-200 rounded-lg overflow-hidden">
              <div v-if="requiredServices.length" class="bg-red-50/50 px-3 py-1.5">
                <span class="text-[10px] font-bold text-red-600 uppercase tracking-wide">{{ t('crm.serviceDetail.requiredServices') }}</span>
              </div>
              <div v-for="svc in requiredServices" :key="svc.id"
                class="px-3 py-2 bg-red-50/30 border-b border-red-100/50">
                <label class="flex items-start gap-2 cursor-not-allowed">
                  <input type="checkbox" :checked="true" disabled class="w-4 h-4 text-red-500 rounded border-gray-300 mt-0.5" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-medium text-gray-900">{{ svc.name }}</span>
                      <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold">{{ t('crm.serviceDetail.requiredBadge') }}</span>
                    </div>
                    <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-0.5">{{ svc.agency_hint }}</p>
                  </div>
                </label>
              </div>
              <div v-if="optionalServices.length" class="bg-gray-50 px-3 py-1.5 border-t border-gray-100">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">{{ t('crm.serviceDetail.optionalServices') }}</span>
              </div>
              <div v-for="svc in optionalServices" :key="svc.id"
                class="px-3 py-2 border-b border-gray-50 hover:bg-blue-50/30 transition-colors">
                <label class="flex items-start gap-2 cursor-pointer">
                  <input type="checkbox" :value="svc.id" v-model="form.service_ids"
                    class="w-4 h-4 text-blue-600 rounded border-gray-300 mt-0.5" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-gray-800">{{ svc.name }}</span>
                      <span class="text-xs text-gray-400">{{ categoryLabel(svc.category) }}</span>
                    </div>
                    <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-0.5">{{ svc.agency_hint }}</p>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="form.is_active" id="pkg-active"
              class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <label for="pkg-active" class="text-sm text-gray-700">{{ t('crm.serviceDetail.activePackage') }}</label>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button @click="editing = false"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">{{ t('common.cancel') }}</button>
            <button @click="save" :disabled="saving || !canSave"
              class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? t('crm.services.saving') : t('crm.services.save') }}
            </button>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { countriesApi } from '@/api/countries';
import { codeToFlag } from '@/utils/countries';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const editing = ref(false);
const pkg = ref({});
const allPackages = ref([]);
const globalServices = ref([]);
const allCountries = ref([]);
const allVisaTypes = ref([]);
const formErrors = ref([]);

const CATEGORY_ORDER = ['consultation', 'documents', 'translation', 'visa_center', 'financial', 'other'];
const categoryLabels = computed(() => ({
  consultation: t('crm.services.categories.consultation'),
  documents: t('crm.services.categories.documents'),
  translation: t('crm.services.categories.translation'),
  visa_center: t('crm.services.categories.visa_center'),
  financial: t('crm.services.categories.finance'),
  other: t('crm.services.categories.other'),
}));
function categoryLabel(cat) { return categoryLabels.value[cat] || cat; }

const VISA_TYPE_RU = {
  tourist: 'Туристическая виза', business: 'Бизнес-виза', student: 'Студенческая виза',
  work: 'Рабочая виза', transit: 'Транзитная виза', medical: 'Медицинская виза',
  family: 'Семейная виза',
};
const VISA_TYPE_UZ = {
  tourist: 'Turistik viza', business: 'Biznes viza', student: 'Talaba vizasi',
  work: 'Ish vizasi', transit: 'Tranzit viza', medical: 'Tibbiy viza',
  family: 'Oilaviy viza',
};

function countryName(code) {
  return allCountries.value.find(c => c.country_code === code)?.name ?? code;
}
function countryNameUz(code) {
  return allCountries.value.find(c => c.country_code === code)?.name_uz ?? countryName(code);
}
function visaTypeName(slug) {
  return allVisaTypes.value.find(t => t.slug === slug)?.name_ru ?? VISA_TYPE_RU[slug] ?? slug;
}
function formatPrice(val) {
  if (!val) return '0';
  return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

const requiredServices = computed(() => globalServices.value.filter(s => s.is_required && s.is_active));
const optionalServices = computed(() => globalServices.value.filter(s => !s.is_required && s.is_active));

// Для режима просмотра: обязательные всегда показываем, плюс дополнительные из pkg.items
const viewRequiredServices = computed(() => requiredServices.value);
const viewOptionalItems = computed(() =>
  (pkg.value.items || []).filter(item => !item.service?.is_required)
);
const viewServices = computed(() => [
  ...viewRequiredServices.value,
  ...viewOptionalItems.value,
]);

// Форма редактирования
const form = ref({
  country_code: '', visa_type: '',
  description: '', description_uz: '',
  price: null, currency: 'UZS', processing_days: null,
  is_active: true, service_ids: [],
});
const countrySearch = ref('');
const countryDropdown = ref(false);

const autoName = computed(() => {
  if (!form.value.country_code || !form.value.visa_type) return '';
  return `${VISA_TYPE_RU[form.value.visa_type] || form.value.visa_type} — ${countryName(form.value.country_code)}`;
});
const autoNameUz = computed(() => {
  if (!form.value.country_code || !form.value.visa_type) return '';
  return `${countryNameUz(form.value.country_code)} — ${VISA_TYPE_UZ[form.value.visa_type] || form.value.visa_type}`;
});

const canSave = computed(() =>
  form.value.country_code &&
  form.value.visa_type &&
  form.value.price > 0 &&
  form.value.processing_days >= 1 &&
  form.value.description?.trim() &&
  form.value.description_uz?.trim()
);

const filteredCountries = computed(() => {
  const q = countrySearch.value.trim().toLowerCase();
  if (!q) return allCountries.value;
  return allCountries.value.filter(c =>
    c.name.toLowerCase().includes(q) || c.country_code.toLowerCase().startsWith(q)
  );
});

const selectedCountryVisaTypes = computed(() => {
  if (!form.value.country_code) return [];
  const c = allCountries.value.find(c => c.country_code === form.value.country_code);
  return c?.visa_types ?? ['tourist', 'business'];
});

function onCountryInput() {
  if (form.value.country_code) {
    form.value.country_code = '';
    form.value.visa_type = '';
  }
  countryDropdown.value = true;
}

function selectCountry(c) {
  form.value.country_code = c.country_code;
  form.value.visa_type = '';
  countrySearch.value = c.name;
  countryDropdown.value = false;
}

function clearCountry() {
  form.value.country_code = '';
  form.value.visa_type = '';
  countrySearch.value = '';
}

function fillForm() {
  form.value = {
    country_code: pkg.value.country_code || '',
    visa_type: pkg.value.visa_type || '',
    description: pkg.value.description || '',
    description_uz: pkg.value.description_uz || '',
    price: pkg.value.price,
    currency: pkg.value.currency || 'UZS',
    processing_days: pkg.value.processing_days,
    is_active: pkg.value.is_active,
    service_ids: (pkg.value.items || []).map(i => i.service_id),
  };
  const c = allCountries.value.find(c => c.country_code === pkg.value.country_code);
  countrySearch.value = c?.name ?? pkg.value.country_code ?? '';
  formErrors.value = [];
}

async function save() {
  const errors = [];
  if (!form.value.country_code) errors.push(t('crm.caseForm.selectCountry'));
  if (!form.value.visa_type) errors.push(t('crm.caseForm.selectVisaType'));
  if (!form.value.price || form.value.price <= 0) errors.push(t('crm.services.priceRequired'));
  if (!form.value.processing_days || form.value.processing_days < 1) errors.push(t('crm.services.daysRequired'));
  if (!form.value.description?.trim()) errors.push(t('crm.services.descRuRequired'));
  if (!form.value.description_uz?.trim()) errors.push(t('crm.services.descUzRequired'));

  // Проверка дубликата
  const dup = allPackages.value.find(p =>
    p.country_code === form.value.country_code &&
    p.visa_type === form.value.visa_type &&
    p.id !== pkg.value.id
  );
  if (dup) errors.push(t('crm.services.duplicateError', { name: dup.name }));

  if (errors.length) { formErrors.value = errors; return; }

  saving.value = true;
  try {
    const payload = { ...form.value };
    payload.name = autoName.value;
    payload.name_uz = autoNameUz.value;
    const reqIds = requiredServices.value.map(s => s.id);
    payload.service_ids = [...new Set([...payload.service_ids, ...reqIds])];

    const res = await api.patch(`/agency/packages/${pkg.value.id}`, payload);
    pkg.value = res.data.data;
    editing.value = false;
  } catch (e) {
    const msg = e?.response?.data?.message || e?.response?.data?.error || t('crm.services.saveError');
    formErrors.value = [msg];
    const errs = e?.response?.data?.errors;
    if (errs) {
      Object.values(errs).forEach(arr => arr.forEach(m => formErrors.value.push(m)));
    }
  } finally {
    saving.value = false;
  }
}

async function confirmDelete() {
  if (!confirm(t('common.confirmDeleteMessage'))) return;
  await api.delete(`/agency/packages/${pkg.value.id}`);
  router.push({ name: 'services' });
}

onMounted(async () => {
  const id = route.params.id;
  const [pkgRes, pkgsRes, svcRes, cRes, vtRes] = await Promise.all([
    api.get(`/agency/packages/${id}`).catch(() => null),
    api.get('/agency/packages').catch(() => ({ data: { data: [] } })),
    api.get('/services').catch(() => ({ data: { data: [] } })),
    countriesApi.list().catch(() => ({ data: { data: [] } })),
    countriesApi.visaTypes().catch(() => ({ data: { data: [] } })),
  ]);

  if (!pkgRes?.data?.data) {
    router.push({ name: 'services' });
    return;
  }

  pkg.value = pkgRes.data.data;
  allPackages.value = pkgsRes.data.data || [];
  globalServices.value = svcRes.data.data || [];
  allCountries.value = cRes.data.data || [];
  allVisaTypes.value = vtRes.data.data || [];
  fillForm();
  loading.value = false;
});
</script>

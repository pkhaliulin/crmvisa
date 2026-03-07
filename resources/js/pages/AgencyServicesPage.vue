<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Услуги агентства</h1>
        <p class="text-sm text-gray-500 mt-1">Каталог услуг и пакеты по направлениям</p>
      </div>
      <button @click="openCreate"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        + Новый пакет
      </button>
    </div>

    <!-- Каталог услуг — полный список по категориям -->
    <div v-if="loadingServices" class="flex items-center justify-center py-8">
      <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>
    <div v-else>
      <div class="flex items-center gap-3 mb-3">
        <h2 class="text-sm font-semibold text-gray-600">Каталог услуг</h2>
        <span class="text-xs text-gray-400">{{ globalServices.length }} услуг</span>
      </div>
      <div class="space-y-3">
        <div v-for="cat in groupedServices" :key="cat.key"
          class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full shrink-0" :class="catDot(cat.key)"></span>
            <h3 class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ categoryLabel(cat.key) }}</h3>
            <span class="text-xs text-gray-400 ml-auto">{{ cat.items.length }}</span>
          </div>
          <div class="divide-y divide-gray-50">
            <div v-for="svc in cat.items" :key="svc.id"
              class="px-4 py-2.5 flex items-center gap-3 text-sm hover:bg-blue-50/50 transition-colors">
              <span class="text-gray-800 flex-1">{{ svc.name }}</span>
              <span v-if="svc.is_mandatory"
                class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-red-50 text-red-600">Обязательная</span>
              <span class="text-xs text-gray-400 shrink-0">{{ categoryLabel(svc.category) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Мои пакеты -->
    <div>
      <div class="flex items-center gap-3 mb-3">
        <h2 class="text-sm font-semibold text-gray-600">Пакеты услуг</h2>
        <span class="text-xs text-gray-400">{{ packages.length }}</span>
      </div>

      <div v-if="loadingPackages" class="flex items-center justify-center py-8">
        <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <div v-else-if="packages.length === 0" class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
        <p class="text-sm">Пакетов ещё нет</p>
        <p class="text-xs text-gray-300 mt-1">Создайте первый пакет услуг для клиентов</p>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div v-for="pkg in packages" :key="pkg.id"
          class="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-md transition-all"
          :class="pkg.is_active ? '' : 'opacity-60'">

          <!-- Header -->
          <div class="flex items-start justify-between mb-3">
            <div class="min-w-0 flex-1">
              <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-900 truncate">{{ pkg.name }}</h3>
                <span v-if="!pkg.is_active"
                  class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded-full shrink-0">Неактивен</span>
              </div>
              <p v-if="pkg.description" class="text-xs text-gray-500 mt-1 line-clamp-2">{{ pkg.description }}</p>
            </div>
            <div class="flex gap-1.5 shrink-0 ml-2">
              <button @click="openEdit(pkg)"
                class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-blue-600 hover:border-blue-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </button>
              <button @click="deletePackage(pkg.id)"
                class="w-7 h-7 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 hover:text-red-600 hover:border-red-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Meta -->
          <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 mb-3">
            <span v-if="pkg.country_code" class="flex items-center gap-1">
              <span class="text-base">{{ codeToFlag(pkg.country_code) }}</span>
              {{ countryName(pkg.country_code) }}
            </span>
            <span v-if="pkg.visa_type" class="text-gray-400">{{ visaTypeName(pkg.visa_type) }}</span>
            <span v-if="pkg.price" class="font-semibold text-gray-700">{{ pkg.price }} {{ pkg.currency }}</span>
            <span v-if="pkg.processing_days">{{ pkg.processing_days }} дн.</span>
          </div>

          <!-- Items tags -->
          <div v-if="pkg.items?.length" class="flex flex-wrap gap-1">
            <span v-for="item in pkg.items" :key="item.id"
              class="text-[10px] px-2 py-0.5 rounded-full"
              :class="item.service?.is_mandatory ? 'bg-red-50 text-red-600 font-semibold' : 'bg-blue-50 text-blue-700'">
              {{ item.service?.name }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Модал создания/редактирования -->
    <div v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">
            {{ editingId ? 'Редактировать пакет' : 'Новый пакет' }}
          </h2>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название (RU) *</label>
            <input v-model="modalForm.name" type="text"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomi (UZ)</label>
            <input v-model="modalForm.name_uz" type="text"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Страна</label>
              <div class="relative">
                <input v-model="countrySearch" @input="onCountryInput" @focus="countryDropdown = true"
                  @blur="() => setTimeout(() => countryDropdown = false, 150)"
                  placeholder="Испания, DE..."
                  :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none pr-6',
                    modalForm.country_code ? 'border-green-500 bg-green-50 text-green-800 font-medium' : 'border-gray-200 focus:border-blue-400']" />
                <button v-if="modalForm.country_code" type="button" @click="clearCountry"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-sm leading-none">x</button>
              </div>
              <div v-if="countryDropdown && filteredCountries.length"
                class="absolute z-30 w-56 mt-1 border border-gray-200 rounded-lg bg-white shadow-lg text-sm max-h-48 overflow-y-auto">
                <button v-for="c in filteredCountries" :key="c.country_code" type="button"
                  class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center gap-2"
                  @mousedown.prevent="selectCountry(c)">
                  <span class="text-base">{{ c.flag_emoji }}</span>
                  <span class="text-gray-900">{{ c.name }}</span>
                  <span class="ml-auto text-xs text-gray-400 font-mono">{{ c.country_code }}</span>
                </button>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Тип визы</label>
              <select v-model="modalForm.visa_type" :disabled="!modalForm.country_code"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 disabled:bg-gray-50 disabled:text-gray-400">
                <option value="">{{ modalForm.country_code ? '-- выберите --' : '-- сначала страну --' }}</option>
                <option v-for="slug in selectedCountryVisaTypes" :key="slug" :value="slug">{{ visaTypeName(slug) }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
              <input v-model.number="modalForm.price" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Срок (дней)</label>
              <input v-model.number="modalForm.processing_days" type="number" min="1"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Описание (RU)</label>
            <textarea v-model="modalForm.description" rows="2"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif (UZ)</label>
            <textarea v-model="modalForm.description_uz" rows="2"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Включённые услуги</label>
            <div class="max-h-48 overflow-y-auto space-y-1 border border-gray-200 rounded-lg p-2">
              <label v-for="svc in globalServices" :key="svc.id"
                class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 p-1 rounded">
                <input type="checkbox" :value="svc.id" v-model="modalForm.service_ids"
                  class="w-4 h-4 text-blue-600 rounded border-gray-300" />
                <span>{{ svc.name }}</span>
                <span v-if="svc.is_mandatory" class="text-[10px] text-red-500 font-medium">*</span>
                <span class="text-xs text-gray-400 ml-auto">{{ categoryLabel(svc.category) }}</span>
              </label>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="modalForm.is_active" id="pkg-active"
              class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <label for="pkg-active" class="text-sm text-gray-700">Активный пакет</label>
          </div>
        </div>

        <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
          <button @click="showModal = false"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Отмена</button>
          <button @click="savePackage" :disabled="saving"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/index';
import { countriesApi } from '@/api/countries';
import { codeToFlag } from '@/utils/countries';

const loadingPackages = ref(true);
const loadingServices = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);

const packages = ref([]);
const globalServices = ref([]);
const allCountries = ref([]);
const allVisaTypes = ref([]);

const CATEGORY_ORDER = ['consultation', 'documents', 'translation', 'visa_center', 'financial', 'other'];
const categoryLabels = {
  consultation: 'Консультация', documents: 'Документы',
  translation: 'Перевод', visa_center: 'Визовый центр',
  financial: 'Финансы', other: 'Прочее',
};
const catDots = {
  consultation: 'bg-blue-400', documents: 'bg-purple-400',
  translation: 'bg-yellow-400', visa_center: 'bg-orange-400',
  financial: 'bg-green-400', other: 'bg-gray-400',
};
function categoryLabel(cat) { return categoryLabels[cat] || cat; }
function catDot(cat) { return catDots[cat] || 'bg-gray-400'; }

const groupedServices = computed(() => {
  const groups = {};
  for (const svc of globalServices.value) {
    const cat = svc.category || 'other';
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(svc);
  }
  return CATEGORY_ORDER.filter(c => groups[c]).map(c => ({ key: c, items: groups[c] }));
});

const defaultForm = () => ({
  name: '', name_uz: '', country_code: '', visa_type: '',
  description: '', description_uz: '',
  price: null, currency: 'USD', processing_days: null,
  is_active: true, service_ids: [],
});

const modalForm = ref(defaultForm());
const countrySearch = ref('');
const countryDropdown = ref(false);

const filteredCountries = computed(() => {
  const q = countrySearch.value.trim().toLowerCase();
  if (!q) return allCountries.value;
  return allCountries.value.filter(c =>
    c.name.toLowerCase().includes(q) || c.country_code.toLowerCase().startsWith(q)
  );
});

const selectedCountryVisaTypes = computed(() => {
  if (!modalForm.value.country_code) return [];
  const c = allCountries.value.find(c => c.country_code === modalForm.value.country_code);
  return c?.visa_types ?? ['tourist', 'student', 'business'];
});

function onCountryInput() {
  if (modalForm.value.country_code) {
    modalForm.value.country_code = '';
    modalForm.value.visa_type = '';
  }
  countryDropdown.value = true;
}

function selectCountry(c) {
  modalForm.value.country_code = c.country_code;
  modalForm.value.visa_type = '';
  countrySearch.value = c.name;
  countryDropdown.value = false;
}

function clearCountry() {
  modalForm.value.country_code = '';
  modalForm.value.visa_type = '';
  countrySearch.value = '';
}

function countryName(code) {
  return allCountries.value.find(c => c.country_code === code)?.name ?? code;
}

function visaTypeName(slug) {
  return allVisaTypes.value.find(t => t.slug === slug)?.name_ru ?? slug;
}

onMounted(async () => {
  const [pkgRes, svcRes, cRes, vtRes] = await Promise.all([
    api.get('/agency/packages').catch(() => ({ data: { data: [] } })),
    api.get('/services').catch(() => ({ data: { data: [] } })),
    countriesApi.list().catch(() => ({ data: { data: [] } })),
    countriesApi.visaTypes().catch(() => ({ data: { data: [] } })),
  ]);
  packages.value = pkgRes.data.data || [];
  globalServices.value = svcRes.data.data || [];
  allCountries.value = cRes.data.data || [];
  allVisaTypes.value = vtRes.data.data || [];
  loadingPackages.value = false;
  loadingServices.value = false;
});

function openCreate() {
  editingId.value = null;
  modalForm.value = defaultForm();
  countrySearch.value = '';
  showModal.value = true;
}

function openEdit(pkg) {
  editingId.value = pkg.id;
  modalForm.value = {
    name: pkg.name || '', name_uz: pkg.name_uz || '',
    country_code: pkg.country_code || '', visa_type: pkg.visa_type || '',
    description: pkg.description || '', description_uz: pkg.description_uz || '',
    price: pkg.price, currency: pkg.currency || 'USD',
    processing_days: pkg.processing_days, is_active: pkg.is_active,
    service_ids: (pkg.items || []).map(i => i.service_id),
  };
  const c = allCountries.value.find(c => c.country_code === pkg.country_code);
  countrySearch.value = c?.name ?? pkg.country_code ?? '';
  showModal.value = true;
}

async function savePackage() {
  saving.value = true;
  try {
    const payload = { ...modalForm.value };
    if (!payload.country_code) delete payload.country_code;
    if (!payload.visa_type) delete payload.visa_type;
    if (editingId.value) {
      const res = await api.patch(`/agency/packages/${editingId.value}`, payload);
      const idx = packages.value.findIndex(p => p.id === editingId.value);
      if (idx !== -1) packages.value[idx] = res.data.data;
    } else {
      const res = await api.post('/agency/packages', payload);
      packages.value.unshift(res.data.data);
    }
    showModal.value = false;
  } catch { /* ignore */ } finally {
    saving.value = false;
  }
}

async function deletePackage(id) {
  if (!confirm('Удалить пакет?')) return;
  await api.delete(`/agency/packages/${id}`);
  packages.value = packages.value.filter(p => p.id !== id);
}
</script>

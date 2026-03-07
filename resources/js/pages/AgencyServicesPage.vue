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
              class="px-4 py-2.5 flex items-center gap-3 text-sm hover:bg-blue-50/50 transition-colors group">
              <span class="text-gray-800 flex-1">{{ svc.name }}</span>
              <span v-if="svc.is_required"
                class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-red-50 text-red-600">Обязательная</span>
              <!-- Tooltip с описанием -->
              <div v-if="svc.description" class="relative">
                <svg class="w-4 h-4 text-gray-300 hover:text-blue-500 cursor-help transition-colors peer"
                  fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/>
                </svg>
                <div class="absolute right-0 bottom-full mb-2 w-64 p-3 bg-gray-800 text-white text-xs rounded-lg shadow-lg
                            opacity-0 invisible peer-hover:opacity-100 peer-hover:visible transition-all z-30 pointer-events-none">
                  {{ svc.description }}
                </div>
              </div>
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
              :class="item.service?.is_required ? 'bg-red-50 text-red-600 font-semibold' : 'bg-blue-50 text-blue-700'">
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
          <!-- Ошибки валидации -->
          <div v-if="formErrors.length" class="bg-red-50 border border-red-200 rounded-lg p-3">
            <ul class="text-xs text-red-600 space-y-0.5">
              <li v-for="err in formErrors" :key="err">{{ err }}</li>
            </ul>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название (RU) *</label>
            <input v-model="modalForm.name" type="text" placeholder="Туристическая виза в Испанию"
              :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400',
                fieldError('name') ? 'border-red-300 bg-red-50' : 'border-gray-200']" />
            <p v-if="fieldError('name')" class="text-xs text-red-500 mt-0.5">{{ fieldError('name') }}</p>
            <p v-else class="text-xs text-gray-400 mt-0.5">Только кириллица, пробелы, цифры и знаки препинания</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nomi (UZ) *</label>
            <input v-model="modalForm.name_uz" type="text" placeholder="Ispaniyaga turistik viza"
              :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400',
                fieldError('name_uz') ? 'border-red-300 bg-red-50' : 'border-gray-200']" />
            <p v-if="fieldError('name_uz')" class="text-xs text-red-500 mt-0.5">{{ fieldError('name_uz') }}</p>
            <p v-else class="text-xs text-gray-400 mt-0.5">Только латиница, пробелы, цифры и знаки препинания</p>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 mb-1">Страна *</label>
              <div class="relative">
                <input v-model="countrySearch" @input="onCountryInput" @focus="countryDropdown = true"
                  @blur="() => setTimeout(() => countryDropdown = false, 200)"
                  placeholder="Начните вводить..."
                  :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none pr-6',
                    modalForm.country_code ? 'border-green-500 bg-green-50 text-green-800 font-medium'
                      : fieldError('country') ? 'border-red-300 bg-red-50' : 'border-gray-200 focus:border-blue-400']" />
                <button v-if="modalForm.country_code" type="button" @click="clearCountry"
                  class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-sm leading-none">x</button>
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
              <p v-if="fieldError('country')" class="text-xs text-red-500 mt-0.5">{{ fieldError('country') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Тип визы *</label>
              <select v-model="modalForm.visa_type" :disabled="!modalForm.country_code"
                @change="onVisaTypeChange"
                :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 disabled:bg-gray-50 disabled:text-gray-400',
                  fieldError('visa_type') ? 'border-red-300 bg-red-50' : 'border-gray-200']">
                <option value="">{{ modalForm.country_code ? '-- выберите --' : '-- сначала страну --' }}</option>
                <option v-for="slug in selectedCountryVisaTypes" :key="slug" :value="slug">{{ visaTypeName(slug) }}</option>
              </select>
              <p v-if="fieldError('visa_type')" class="text-xs text-red-500 mt-0.5">{{ fieldError('visa_type') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Цена (UZS) *</label>
              <input v-model.number="modalForm.price" type="number" min="0" placeholder="2000000"
                :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                  fieldError('price') ? 'border-red-300 bg-red-50' : 'border-gray-200']" />
              <p v-if="fieldError('price')" class="text-xs text-red-500 mt-0.5">{{ fieldError('price') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Срок (дней) *</label>
              <input v-model.number="modalForm.processing_days" type="number" min="1" placeholder="14"
                :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                  fieldError('processing_days') ? 'border-red-300 bg-red-50' : 'border-gray-200']" />
              <p v-if="fieldError('processing_days')" class="text-xs text-red-500 mt-0.5">{{ fieldError('processing_days') }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Описание (RU) *</label>
            <textarea v-model="modalForm.description" rows="2" placeholder="Полное сопровождение оформления визы..."
              :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                fieldError('description') ? 'border-red-300 bg-red-50' : 'border-gray-200']"></textarea>
            <p v-if="fieldError('description')" class="text-xs text-red-500 mt-0.5">{{ fieldError('description') }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif (UZ) *</label>
            <textarea v-model="modalForm.description_uz" rows="2" placeholder="Viza rasmiylashtirish bo'yicha to'liq xizmat..."
              :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                fieldError('description_uz') ? 'border-red-300 bg-red-50' : 'border-gray-200']"></textarea>
            <p v-if="fieldError('description_uz')" class="text-xs text-red-500 mt-0.5">{{ fieldError('description_uz') }}</p>
          </div>

          <!-- Услуги с подсказками -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Включённые услуги</label>
            <p v-if="!modalForm.country_code || !modalForm.visa_type" class="text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-lg">
              Выберите страну и тип визы, чтобы увидеть обязательные и рекомендуемые услуги
            </p>
            <div v-else class="space-y-0.5 border border-gray-200 rounded-lg overflow-hidden">
              <!-- Обязательные (всегда отмечены, нельзя снять) -->
              <div v-if="requiredServices.length" class="bg-red-50/50 px-3 py-1.5">
                <span class="text-[10px] font-bold text-red-600 uppercase tracking-wide">Обязательные услуги</span>
              </div>
              <div v-for="svc in requiredServices" :key="svc.id"
                class="px-3 py-2 bg-red-50/30 border-b border-red-100/50">
                <label class="flex items-start gap-2 cursor-not-allowed">
                  <input type="checkbox" :checked="true" disabled
                    class="w-4 h-4 text-red-500 rounded border-gray-300 mt-0.5" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-medium text-gray-900">{{ svc.name }}</span>
                      <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold shrink-0">Обязательная</span>
                    </div>
                    <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ svc.agency_hint }}</p>
                    <p v-else-if="svc.description" class="text-xs text-gray-400 mt-0.5">{{ svc.description }}</p>
                  </div>
                </label>
              </div>

              <!-- Опциональные (можно отмечать) -->
              <div v-if="optionalServices.length" class="bg-gray-50 px-3 py-1.5 border-t border-gray-100">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Дополнительные услуги (на выбор)</span>
              </div>
              <div v-for="svc in optionalServices" :key="svc.id"
                class="px-3 py-2 border-b border-gray-50 hover:bg-blue-50/30 transition-colors">
                <label class="flex items-start gap-2 cursor-pointer">
                  <input type="checkbox" :value="svc.id" v-model="modalForm.service_ids"
                    class="w-4 h-4 text-blue-600 rounded border-gray-300 mt-0.5" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-sm text-gray-800">{{ svc.name }}</span>
                      <span class="text-xs text-gray-400 shrink-0">{{ categoryLabel(svc.category) }}</span>
                    </div>
                    <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ svc.agency_hint }}</p>
                    <p v-else-if="svc.description" class="text-xs text-gray-400 mt-0.5">{{ svc.description }}</p>
                  </div>
                </label>
              </div>

              <div v-if="!requiredServices.length && !optionalServices.length"
                class="px-3 py-4 text-center text-sm text-gray-400">
                Нет доступных услуг
              </div>
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
          <button @click="savePackage" :disabled="saving || !canSave"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
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

// Обязательные и опциональные активные услуги
const requiredServices = computed(() =>
  globalServices.value.filter(s => s.is_required && s.is_active)
);
const optionalServices = computed(() =>
  globalServices.value.filter(s => !s.is_required && s.is_active)
);

const canSave = computed(() =>
  modalForm.value.name?.trim() &&
  modalForm.value.name_uz?.trim() &&
  modalForm.value.country_code &&
  modalForm.value.visa_type &&
  modalForm.value.price > 0 &&
  modalForm.value.processing_days >= 1 &&
  modalForm.value.description?.trim() &&
  modalForm.value.description_uz?.trim()
);

const defaultForm = () => ({
  name: '', name_uz: '', country_code: '', visa_type: '',
  description: '', description_uz: '',
  price: null, currency: 'USD', processing_days: null,
  is_active: true, service_ids: [],
});

const modalForm = ref(defaultForm());
const countrySearch = ref('');
const countryDropdown = ref(false);
const formErrors = ref([]);
const fieldErrors = ref({});

function fieldError(field) { return fieldErrors.value[field] || ''; }

const CYRILLIC_RE = /^[\u0400-\u04FFёЁ0-9\s.,\-—()«»"'!?:;\/+&№%]+$/;
const LATIN_RE = /^[a-zA-Z0-9\s.,\-—()«»"'!?:;\/+&№%\u02BB\u02BC]+$/;

function validateForm() {
  const errors = [];
  const fe = {};

  // Название RU — обязательное, кириллица
  if (!modalForm.value.name?.trim()) {
    fe.name = 'Обязательное поле';
    errors.push('Заполните название на русском');
  } else if (!CYRILLIC_RE.test(modalForm.value.name.trim())) {
    fe.name = 'Используйте кириллицу';
    errors.push('Название (RU) должно быть на кириллице');
  }

  // Название UZ — обязательное, латиница
  if (!modalForm.value.name_uz?.trim()) {
    fe.name_uz = 'Обязательное поле';
    errors.push('Заполните название на узбекском');
  } else if (!LATIN_RE.test(modalForm.value.name_uz.trim())) {
    fe.name_uz = 'Используйте латиницу';
    errors.push('Nomi (UZ) должно быть на латинице');
  }

  // Страна
  if (!modalForm.value.country_code) {
    fe.country = 'Выберите страну из списка';
    errors.push('Выберите страну');
  }

  // Тип визы
  if (!modalForm.value.visa_type) {
    fe.visa_type = 'Выберите тип визы';
    errors.push('Выберите тип визы');
  }

  // Цена
  if (!modalForm.value.price || modalForm.value.price <= 0) {
    fe.price = 'Укажите стоимость';
    errors.push('Укажите стоимость услуги');
  }

  // Срок
  if (!modalForm.value.processing_days || modalForm.value.processing_days < 1) {
    fe.processing_days = 'Укажите срок';
    errors.push('Укажите срок обработки в днях');
  }

  // Описание RU
  if (!modalForm.value.description?.trim()) {
    fe.description = 'Обязательное поле';
    errors.push('Заполните описание на русском');
  }

  // Описание UZ
  if (!modalForm.value.description_uz?.trim()) {
    fe.description_uz = 'Обязательное поле';
    errors.push('Заполните описание на узбекском');
  }

  formErrors.value = errors;
  fieldErrors.value = fe;
  return errors.length === 0;
}

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

// Авто-добавить обязательные услуги при выборе типа визы
function onVisaTypeChange() {
  if (!modalForm.value.visa_type) return;
  // При создании нового пакета авто-добавляем обязательные
  if (!editingId.value) {
    const reqIds = requiredServices.value.map(s => s.id);
    const merged = new Set([...modalForm.value.service_ids, ...reqIds]);
    modalForm.value.service_ids = [...merged];
  }
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
  formErrors.value = [];
  fieldErrors.value = {};
  showModal.value = true;
}

function openEdit(pkg) {
  editingId.value = pkg.id;
  formErrors.value = [];
  fieldErrors.value = {};
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
  if (!validateForm()) return;
  saving.value = true;
  try {
    const payload = { ...modalForm.value };

    // Всегда включать обязательные услуги
    const reqIds = requiredServices.value.map(s => s.id);
    const merged = new Set([...payload.service_ids, ...reqIds]);
    payload.service_ids = [...merged];

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

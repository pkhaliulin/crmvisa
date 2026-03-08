<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('crm.services.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('crm.services.subtitle') }}</p>
      </div>
      <button @click="openCreate"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        {{ t('crm.services.newPackage') }}
      </button>
    </div>

    <!-- Каталог услуг — сворачиваемый блок -->
    <div v-if="loadingServices" class="flex items-center justify-center py-8">
      <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>
    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <button @click="catalogOpen = !catalogOpen"
        class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400 transition-transform" :class="catalogOpen ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
          <h2 class="text-sm font-semibold text-gray-700">{{ t('crm.services.catalog') }}</h2>
          <span class="text-xs text-gray-400">{{ t('crm.services.servicesCount', { n: globalServices.length }) }}</span>
        </div>
        <span class="text-[10px] text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ t('crm.services.referenceInfo') }}</span>
      </button>

      <div v-if="catalogOpen" class="border-t border-gray-100">
        <div v-for="cat in groupedServices" :key="cat.key">
          <button @click="toggleCat(cat.key)"
            class="w-full px-4 py-2 flex items-center gap-2 bg-gray-50 hover:bg-gray-100 transition-colors border-b border-gray-100">
            <svg class="w-3 h-3 text-gray-400 transition-transform" :class="openCats[cat.key] ? 'rotate-90' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="w-2 h-2 rounded-full shrink-0" :class="catDot(cat.key)"></span>
            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">{{ categoryLabel(cat.key) }}</span>
            <span class="text-xs text-gray-400 ml-auto">{{ cat.items.length }}</span>
          </button>
          <div v-if="openCats[cat.key]" class="divide-y divide-gray-50">
            <div v-for="svc in cat.items" :key="svc.id"
              class="px-4 py-2.5 hover:bg-blue-50/30 transition-colors pl-10">
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-gray-800">{{ svc.name }}</span>
                <span v-if="svc.is_required"
                  class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-red-50 text-red-600 shrink-0">{{ t('crm.services.required') }}</span>
              </div>
              <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ svc.agency_hint }}</p>
              <p v-else-if="svc.description" class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ svc.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Мои пакеты -->
    <div>
      <div class="flex items-center gap-3 mb-3">
        <h2 class="text-sm font-semibold text-gray-600">{{ t('crm.services.packages') }}</h2>
        <span class="text-xs text-gray-400">{{ t('crm.services.countOf', { n: filteredPackages.length, total: packages.length }) }}</span>
      </div>

      <!-- Средняя стоимость -->
      <div v-if="packages.length" class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-3 flex items-center gap-4 flex-wrap">
        <div class="flex items-center gap-2 text-sm">
          <span class="text-blue-600 font-medium">{{ t('crm.services.avgCost') }}</span>
          <span class="font-bold text-gray-900">{{ formatPrice(avgPrice) }} UZS</span>
          <span class="text-xs text-gray-400">({{ packages.length }})</span>
        </div>
        <div v-if="avgPriceFiltered !== null && filteredPackages.length !== packages.length" class="flex items-center gap-2 text-sm">
          <span class="text-blue-500">{{ t('crm.services.byFilter') }}</span>
          <span class="font-semibold text-gray-800">{{ formatPrice(avgPriceFiltered) }} UZS</span>
          <span class="text-xs text-gray-400">({{ filteredPackages.length }})</span>
        </div>
      </div>

      <!-- Фильтры -->
      <div v-if="packages.length" class="flex flex-wrap gap-2 mb-3">
        <select v-model="filter.country" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-blue-400">
          <option value="">{{ t('crm.services.allCountries') }}</option>
          <option v-for="cc in uniqueCountries" :key="cc" :value="cc">{{ codeToFlag(cc) }} {{ countryName(cc) }}</option>
        </select>
        <select v-model="filter.visa_type" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-blue-400">
          <option value="">{{ t('crm.services.allVisaTypes') }}</option>
          <option v-for="vt in uniqueVisaTypes" :key="vt" :value="vt">{{ visaTypeName(vt) }}</option>
        </select>
        <select v-model="filter.status" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-blue-400">
          <option value="">{{ t('crm.services.allStatuses') }}</option>
          <option value="active">{{ t('crm.services.activeOnly') }}</option>
          <option value="inactive">{{ t('crm.services.inactiveOnly') }}</option>
        </select>
        <select v-model="filter.sort" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:border-blue-400">
          <option value="name">{{ t('crm.services.sortName') }}</option>
          <option value="price_asc">{{ t('crm.services.sortPriceAsc') }}</option>
          <option value="price_desc">{{ t('crm.services.sortPriceDesc') }}</option>
          <option value="days_asc">{{ t('crm.services.sortDaysAsc') }}</option>
          <option value="days_desc">{{ t('crm.services.sortDaysDesc') }}</option>
        </select>
        <button v-if="hasActiveFilters" @click="resetFilters"
          class="text-xs text-red-500 hover:text-red-700 px-2 py-1 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
          {{ t('crm.services.reset') }}
        </button>
      </div>

      <div v-if="loadingPackages" class="flex items-center justify-center py-8">
        <div class="animate-spin w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <div v-else-if="packages.length === 0" class="bg-white rounded-xl border border-gray-200 py-12 text-center text-gray-400">
        <svg class="w-10 h-10 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
        <p class="text-sm">{{ t('crm.services.emptyTitle') }}</p>
        <p class="text-xs text-gray-300 mt-1">{{ t('crm.services.emptyHint') }}</p>
      </div>

      <div v-else-if="filteredPackages.length === 0" class="bg-white rounded-xl border border-gray-200 py-8 text-center text-gray-400">
        <p class="text-sm">{{ t('crm.services.emptyFiltered') }}</p>
      </div>

      <!-- Список пакетов (одна колонка) -->
      <div v-else class="space-y-2">
        <div v-for="pkg in filteredPackages" :key="pkg.id"
          @click="router.push({ name: 'service.detail', params: { id: pkg.id } })"
          class="bg-white rounded-xl border border-gray-200 hover:shadow-md hover:border-blue-200 transition-all cursor-pointer"
          :class="pkg.is_active ? '' : 'opacity-60'">

          <div class="px-4 py-3 flex items-center gap-4">
            <!-- Флаг и название -->
            <div class="flex items-center gap-3 min-w-0 flex-1">
              <span v-if="pkg.country_code" class="text-2xl shrink-0">{{ codeToFlag(pkg.country_code) }}</span>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="font-semibold text-gray-900 text-sm truncate">{{ pkg.name }}</h3>
                  <span v-if="!pkg.is_active"
                    class="text-[10px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded-full shrink-0">{{ t('crm.services.inactive') }}</span>
                </div>
                <!-- Теги услуг сразу после названия -->
                <div class="flex items-center gap-1 mt-1 flex-wrap">
                  <span v-for="item in (pkg.items || []).slice(0, 4)" :key="item.id"
                    class="text-[9px] px-1.5 py-0.5 rounded-full whitespace-nowrap"
                    :class="item.service?.is_required ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'">
                    {{ item.service?.name }}
                  </span>
                  <span v-if="(pkg.items || []).length > 4" class="text-[9px] text-gray-400">
                    +{{ pkg.items.length - 4 }}
                  </span>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-400 mt-0.5 flex-wrap">
                  <span v-if="pkg.visa_type">{{ visaTypeName(pkg.visa_type) }}</span>
                  <span v-if="pkg.processing_days">{{ t('crm.services.days', { n: pkg.processing_days }) }}</span>
                </div>
              </div>
            </div>

            <!-- Стоимость в конце -->
            <div v-if="pkg.price" class="text-right shrink-0">
              <div class="font-bold text-gray-900 text-sm">{{ formatPrice(pkg.price) }}</div>
              <div class="text-[10px] text-gray-400">{{ pkg.currency || 'UZS' }}</div>
            </div>

            <!-- Стрелка -->
            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Модал создания/редактирования -->
    <div v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">{{ t('crm.services.newPackageTitle') }}</h2>
        </div>

        <div class="p-5 space-y-4">
          <!-- Ошибки валидации -->
          <div v-if="formErrors.length" class="bg-red-50 border border-red-200 rounded-lg p-3">
            <ul class="text-xs text-red-600 space-y-0.5">
              <li v-for="err in formErrors" :key="err">{{ err }}</li>
            </ul>
          </div>

          <!-- Предупреждение о дубликате -->
          <div v-if="duplicateWarning" class="bg-amber-50 border border-amber-300 rounded-lg px-4 py-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span class="text-sm text-amber-700">{{ duplicateWarning }}</span>
          </div>

          <!-- Автоназвание -->
          <div v-if="autoName && !duplicateWarning" class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
            <div class="text-xs text-blue-500 font-medium mb-1">{{ t('crm.services.packageName') }}</div>
            <div class="font-semibold text-gray-900 text-sm">{{ autoName }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ autoNameUz }}</div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.country') }}</label>
              <div class="relative">
                <input v-model="countrySearch" @input="onCountryInput" @focus="countryDropdown = true"
                  @blur="() => setTimeout(() => countryDropdown = false, 200)"
                  :placeholder="t('crm.services.countrySearch')"
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
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.visaTypeLabel') }}</label>
              <select v-model="modalForm.visa_type" :disabled="!modalForm.country_code"
                @change="onVisaTypeChange"
                :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 disabled:bg-gray-50 disabled:text-gray-400',
                  fieldError('visa_type') ? 'border-red-300 bg-red-50' : 'border-gray-200']">
                <option value="">{{ modalForm.country_code ? t('crm.services.selectDefault') : t('crm.services.selectCountryFirst') }}</option>
                <option v-for="slug in selectedCountryVisaTypes" :key="slug" :value="slug">{{ visaTypeName(slug) }}</option>
              </select>
              <p v-if="fieldError('visa_type')" class="text-xs text-red-500 mt-0.5">{{ fieldError('visa_type') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.priceLabel') }}</label>
              <input v-model.number="modalForm.price" type="number" min="0" placeholder="2000000"
                :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                  fieldError('price') ? 'border-red-300 bg-red-50' : 'border-gray-200']" />
              <p v-if="fieldError('price')" class="text-xs text-red-500 mt-0.5">{{ fieldError('price') }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.daysLabel') }}</label>
              <input v-model.number="modalForm.processing_days" type="number" min="1" placeholder="14"
                :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                  fieldError('processing_days') ? 'border-red-300 bg-red-50' : 'border-gray-200']" />
              <p v-if="fieldError('processing_days')" class="text-xs text-red-500 mt-0.5">{{ fieldError('processing_days') }}</p>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">{{ t('crm.services.descRu') }}</label>
              <span class="text-xs" :class="(modalForm.description?.length || 0) > 450 ? 'text-red-500' : 'text-gray-400'">
                {{ modalForm.description?.length || 0 }}/500
              </span>
            </div>
            <textarea v-model="modalForm.description" rows="2" maxlength="500" :placeholder="t('crm.services.descRuPlaceholder')"
              :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                fieldError('description') ? 'border-red-300 bg-red-50' : 'border-gray-200']"></textarea>
            <p v-if="fieldError('description')" class="text-xs text-red-500 mt-0.5">{{ fieldError('description') }}</p>
          </div>
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-sm font-medium text-gray-700">{{ t('crm.services.descUz') }}</label>
              <span class="text-xs" :class="(modalForm.description_uz?.length || 0) > 450 ? 'text-red-500' : 'text-gray-400'">
                {{ modalForm.description_uz?.length || 0 }}/500
              </span>
            </div>
            <textarea v-model="modalForm.description_uz" rows="2" maxlength="500" :placeholder="t('crm.services.descUzPlaceholder')"
              :class="['w-full border rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400',
                fieldError('description_uz') ? 'border-red-300 bg-red-50' : 'border-gray-200']"></textarea>
            <p v-if="fieldError('description_uz')" class="text-xs text-red-500 mt-0.5">{{ fieldError('description_uz') }}</p>
          </div>

          <!-- Услуги с подсказками -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.services.includedServices') }}</label>
            <p v-if="!modalForm.country_code || !modalForm.visa_type" class="text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded-lg">
              {{ t('crm.services.selectCountryAndType') }}
            </p>
            <div v-else class="space-y-0.5 border border-gray-200 rounded-lg overflow-hidden">
              <div v-if="requiredServices.length" class="bg-red-50/50 px-3 py-1.5">
                <span class="text-[10px] font-bold text-red-600 uppercase tracking-wide">{{ t('crm.services.requiredServices') }}</span>
              </div>
              <div v-for="svc in requiredServices" :key="svc.id"
                class="px-3 py-2 bg-red-50/30 border-b border-red-100/50">
                <label class="flex items-start gap-2 cursor-not-allowed">
                  <input type="checkbox" :checked="true" disabled
                    class="w-4 h-4 text-red-500 rounded border-gray-300 mt-0.5" />
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-medium text-gray-900">{{ svc.name }}</span>
                      <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-red-100 text-red-600 font-semibold shrink-0">{{ t('crm.services.required') }}</span>
                    </div>
                    <p v-if="svc.agency_hint" class="text-xs text-gray-500 mt-0.5 leading-relaxed">{{ svc.agency_hint }}</p>
                    <p v-else-if="svc.description" class="text-xs text-gray-400 mt-0.5">{{ svc.description }}</p>
                  </div>
                </label>
              </div>

              <div v-if="optionalServices.length" class="bg-gray-50 px-3 py-1.5 border-t border-gray-100">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">{{ t('crm.services.optionalServices') }}</span>
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
                {{ t('crm.services.noServices') }}
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="modalForm.is_active" id="pkg-active"
              class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <label for="pkg-active" class="text-sm text-gray-700">{{ t('crm.services.activePackage') }}</label>
          </div>
        </div>

        <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
          <button @click="showModal = false"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">{{ t('common.cancel') }}</button>
          <button @click="savePackage" :disabled="saving || !canSave"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? t('crm.services.saving') : t('crm.services.save') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { countriesApi } from '@/api/countries';
import { codeToFlag } from '@/utils/countries';

const { t } = useI18n();
const router = useRouter();

const loadingPackages = ref(true);
const loadingServices = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const catalogOpen = ref(false);
const openCats = reactive({});

const packages = ref([]);
const globalServices = ref([]);
const allCountries = ref([]);
const allVisaTypes = ref([]);

const filter = reactive({
  country: '',
  visa_type: '',
  status: '',
  sort: 'name',
});

const CATEGORY_ORDER = ['consultation', 'documents', 'translation', 'visa_center', 'financial', 'other'];
const categoryLabels = computed(() => ({
  consultation: t('crm.services.categories.consultation'),
  documents: t('crm.services.categories.documents'),
  translation: t('crm.services.categories.translation'),
  visa_center: t('crm.services.categories.visa_center'),
  financial: t('crm.services.categories.finance'),
  other: t('crm.services.categories.other'),
}));
const catDots = {
  consultation: 'bg-blue-400', documents: 'bg-purple-400',
  translation: 'bg-yellow-400', visa_center: 'bg-orange-400',
  financial: 'bg-green-400', other: 'bg-gray-400',
};
function categoryLabel(cat) { return categoryLabels.value[cat] || cat; }
function catDot(cat) { return catDots[cat] || 'bg-gray-400'; }
function toggleCat(key) {
  const wasOpen = openCats[key];
  // Закрываем все категории
  for (const k of CATEGORY_ORDER) openCats[k] = false;
  // Открываем только нажатую (если была закрыта)
  if (!wasOpen) openCats[key] = true;
}

const groupedServices = computed(() => {
  const groups = {};
  for (const svc of globalServices.value) {
    const cat = svc.category || 'other';
    if (!groups[cat]) groups[cat] = [];
    groups[cat].push(svc);
  }
  return CATEGORY_ORDER.filter(c => groups[c]).map(c => ({ key: c, items: groups[c] }));
});

const requiredServices = computed(() =>
  globalServices.value.filter(s => s.is_required && s.is_active)
);
const optionalServices = computed(() =>
  globalServices.value.filter(s => !s.is_required && s.is_active)
);

// Фильтрация и сортировка пакетов
const uniqueCountries = computed(() => [...new Set(packages.value.map(p => p.country_code).filter(Boolean))].sort());
const uniqueVisaTypes = computed(() => [...new Set(packages.value.map(p => p.visa_type).filter(Boolean))].sort());

const hasActiveFilters = computed(() => filter.country || filter.visa_type || filter.status);

function resetFilters() {
  filter.country = '';
  filter.visa_type = '';
  filter.status = '';
  filter.sort = 'name';
}

const filteredPackages = computed(() => {
  let list = [...packages.value];

  if (filter.country) list = list.filter(p => p.country_code === filter.country);
  if (filter.visa_type) list = list.filter(p => p.visa_type === filter.visa_type);
  if (filter.status === 'active') list = list.filter(p => p.is_active);
  if (filter.status === 'inactive') list = list.filter(p => !p.is_active);

  switch (filter.sort) {
    case 'price_asc':  list.sort((a, b) => (a.price || 0) - (b.price || 0)); break;
    case 'price_desc': list.sort((a, b) => (b.price || 0) - (a.price || 0)); break;
    case 'days_asc':   list.sort((a, b) => (a.processing_days || 0) - (b.processing_days || 0)); break;
    case 'days_desc':  list.sort((a, b) => (b.processing_days || 0) - (a.processing_days || 0)); break;
    default:           list.sort((a, b) => (a.name || '').localeCompare(b.name || ''));
  }

  return list;
});

// Средние цены
const avgPrice = computed(() => {
  const prices = packages.value.map(p => p.price).filter(p => p > 0);
  return prices.length ? Math.round(prices.reduce((s, p) => s + p, 0) / prices.length) : 0;
});

const avgPriceFiltered = computed(() => {
  const prices = filteredPackages.value.map(p => p.price).filter(p => p > 0);
  if (!prices.length) return null;
  return Math.round(prices.reduce((s, p) => s + p, 0) / prices.length);
});

function formatPrice(val) {
  if (!val) return '0';
  return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

// Маппинг типов виз (используется для генерации названия пакета, сохраняется в БД)
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

function countryNameUz(code) {
  return allCountries.value.find(c => c.country_code === code)?.name_uz ?? countryName(code);
}

const autoName = computed(() => {
  if (!modalForm.value.country_code || !modalForm.value.visa_type) return '';
  const visa = VISA_TYPE_RU[modalForm.value.visa_type] || modalForm.value.visa_type;
  const country = countryName(modalForm.value.country_code);
  return `${visa} — ${country}`;
});

const autoNameUz = computed(() => {
  if (!modalForm.value.country_code || !modalForm.value.visa_type) return '';
  const visa = VISA_TYPE_UZ[modalForm.value.visa_type] || modalForm.value.visa_type;
  const country = countryNameUz(modalForm.value.country_code);
  return `${country} — ${visa}`;
});

const duplicateWarning = computed(() => {
  if (!modalForm.value.country_code || !modalForm.value.visa_type) return '';
  const dup = packages.value.find(p =>
    p.country_code === modalForm.value.country_code &&
    p.visa_type === modalForm.value.visa_type &&
    p.id !== editingId.value
  );
  if (dup) return t('crm.services.duplicateError', { name: dup.name });
  return '';
});

const canSave = computed(() =>
  modalForm.value.country_code &&
  modalForm.value.visa_type &&
  !duplicateWarning.value &&
  modalForm.value.price > 0 &&
  modalForm.value.processing_days >= 1 &&
  modalForm.value.description?.trim() &&
  modalForm.value.description_uz?.trim()
);

const defaultForm = () => ({
  country_code: '', visa_type: '',
  description: '', description_uz: '',
  price: null, currency: 'UZS', processing_days: null,
  is_active: true, service_ids: [],
});

const modalForm = ref(defaultForm());
const countrySearch = ref('');
const countryDropdown = ref(false);
const formErrors = ref([]);
const fieldErrors = ref({});

function fieldError(field) { return fieldErrors.value[field] || ''; }

function validateForm() {
  const errors = [];
  const fe = {};

  if (!modalForm.value.country_code) {
    fe.country = t('crm.caseForm.selectCountry');
    errors.push(t('crm.caseForm.selectCountry'));
  }
  if (!modalForm.value.visa_type) {
    fe.visa_type = t('crm.caseForm.selectVisaType');
    errors.push(t('crm.caseForm.selectVisaType'));
  }

  // Проверка дубликата: страна + тип визы
  if (modalForm.value.country_code && modalForm.value.visa_type) {
    const dup = packages.value.find(p =>
      p.country_code === modalForm.value.country_code &&
      p.visa_type === modalForm.value.visa_type &&
      p.id !== editingId.value
    );
    if (dup) {
      const msg = t('crm.services.duplicateError', { name: dup.name });
      fe.visa_type = msg;
      errors.push(msg);
    }
  }
  if (!modalForm.value.price || modalForm.value.price <= 0) {
    fe.price = t('crm.services.priceRequired');
    errors.push(t('crm.services.priceRequired'));
  }
  if (!modalForm.value.processing_days || modalForm.value.processing_days < 1) {
    fe.processing_days = t('crm.services.daysRequired');
    errors.push(t('crm.services.daysRequired'));
  }
  if (!modalForm.value.description?.trim()) {
    fe.description = t('common.required');
    errors.push(t('crm.services.descRuRequired'));
  }
  if (!modalForm.value.description_uz?.trim()) {
    fe.description_uz = t('common.required');
    errors.push(t('crm.services.descUzRequired'));
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

function onVisaTypeChange() {
  if (!modalForm.value.visa_type) return;
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
  return allVisaTypes.value.find(t => t.slug === slug)?.name_ru ?? VISA_TYPE_RU[slug] ?? slug;
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

async function savePackage() {
  if (!validateForm()) return;
  saving.value = true;
  try {
    const payload = { ...modalForm.value };
    payload.name = autoName.value;
    payload.name_uz = autoNameUz.value;

    const reqIds = requiredServices.value.map(s => s.id);
    const merged = new Set([...payload.service_ids, ...reqIds]);
    payload.service_ids = [...merged];

    const res = await api.post('/agency/packages', payload);
    const created = res.data.data;
    showModal.value = false;
    router.push({ name: 'service.detail', params: { id: created.id } });
  } catch (e) {
    const msg = e?.response?.data?.message || e?.response?.data?.error || t('crm.services.saveError');
    formErrors.value = [msg];
    // Показать ошибки валидации из Laravel
    const errs = e?.response?.data?.errors;
    if (errs) {
      Object.values(errs).forEach(arr => arr.forEach(m => formErrors.value.push(m)));
    }
  } finally {
    saving.value = false;
  }
}

</script>

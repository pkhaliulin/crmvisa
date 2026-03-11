<template>
  <div class="space-y-5">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.knowledge.title') }}</h1>
      <p class="text-sm text-gray-500">{{ t('crm.knowledge.subtitle') }}</p>
    </div>

    <!-- Access denied -->
    <div v-if="accessDenied" class="text-center py-16">
      <div class="text-gray-300 text-4xl mb-3">---</div>
      <div class="text-gray-500 font-medium mb-1">{{ t('crm.knowledge.enterpriseOnly') }}</div>
      <p class="text-sm text-gray-400">{{ t('crm.knowledge.upgradeHint') }}</p>
    </div>

    <template v-else>
      <!-- Filters -->
      <div class="flex items-center gap-3 flex-wrap">
        <div class="relative flex-1 min-w-[200px] max-w-xs">
          <input v-model="search" @input="debouncedLoad"
            :placeholder="t('crm.knowledge.searchArticles')"
            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
          <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <select v-model="filterCategory" @change="loadArticles"
          class="text-sm border border-gray-200 rounded-lg px-3 py-2">
          <option value="">{{ t('crm.knowledge.allCategories') }}</option>
          <option v-for="c in articleCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
        </select>
        <CountrySelect
          v-model="filterCountry"
          :countries="countries"
          :placeholder="t('crm.knowledge.allCountries')"
          allow-all
          :all-label="t('crm.knowledge.allCountries')"
          compact
          @change="loadArticles"
        />
      </div>

      <!-- Articles -->
      <div v-if="loading" class="text-center py-10 text-gray-400">{{ t('crm.knowledge.loading') }}</div>
      <div v-else-if="articles.length === 0" class="text-center py-16">
        <div class="text-gray-300 text-4xl mb-3">---</div>
        <div class="text-gray-400">{{ t('crm.knowledge.noArticles') }}</div>
      </div>
      <div v-else class="space-y-3">
        <div v-for="a in articles" :key="a.id"
          @click="openArticle(a)"
          class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition cursor-pointer">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <h3 class="font-medium text-gray-900">{{ a.title }}</h3>
              <p v-if="a.summary" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ a.summary }}</p>
              <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                <span v-if="a.country_code" class="uppercase font-medium">{{ a.country_code }}</span>
                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded">{{ articleCategoryLabel(a.category) }}</span>
                <span v-if="a.visa_type">{{ a.visa_type }}</span>
                <span>{{ a.view_count }} {{ t('crm.knowledge.views') }}</span>
              </div>
            </div>
            <svg class="w-5 h-5 text-gray-300 shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
        <button v-for="p in pagination.last_page" :key="p"
          @click="page = p; loadArticles()"
          :class="[
            'w-8 h-8 rounded-lg text-sm',
            p === page ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
          ]">
          {{ p }}
        </button>
      </div>
    </template>

    <!-- Modal: Article Detail -->
    <div v-if="selectedArticle" class="fixed inset-0 z-50 bg-black/40 flex items-start justify-center pt-8 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl p-6 mb-10" @click.stop>
        <div class="flex items-center justify-between mb-5">
          <div>
            <div class="flex items-center gap-2">
              <span v-if="selectedArticle.country_code"
                class="text-xs uppercase font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                {{ selectedArticle.country_code }}
              </span>
              <span class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded">
                {{ articleCategoryLabel(selectedArticle.category) }}
              </span>
            </div>
            <h2 class="text-lg font-bold text-gray-900 mt-2">{{ selectedArticle.title }}</h2>
          </div>
          <button @click="selectedArticle = null" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="selectedArticle.summary"
          class="text-sm text-gray-500 italic bg-gray-50 rounded-lg p-3 mb-4">
          {{ selectedArticle.summary }}
        </div>

        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
          {{ selectedArticle.content }}
        </div>

        <div v-if="selectedArticle.tags && selectedArticle.tags.length > 0"
          class="flex items-center gap-2 mt-5 pt-4 border-t border-gray-100">
          <span class="text-xs text-gray-400">{{ t('crm.knowledge.tags') }}:</span>
          <span v-for="tag in selectedArticle.tags" :key="tag"
            class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-xs">
            {{ tag }}
          </span>
        </div>

        <div class="flex items-center gap-4 mt-4 text-xs text-gray-400">
          <span>{{ selectedArticle.view_count }} {{ t('crm.knowledge.views') }}</span>
          <span v-if="selectedArticle.published_at">{{ formatDate(selectedArticle.published_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import CountrySelect from '@/components/CountrySelect.vue';

const { t } = useI18n();

const articles = ref([]);
const countries = ref([]);
const loading = ref(true);
const accessDenied = ref(false);
const search = ref('');
const filterCategory = ref('');
const filterCountry = ref('');
const page = ref(1);
const pagination = ref({ last_page: 1 });
const selectedArticle = ref(null);

const articleCategories = computed(() => [
  { value: 'country_guide', label: t('crm.knowledge.cat_country_guide') },
  { value: 'visa_process', label: t('crm.knowledge.cat_visa_process') },
  { value: 'documents', label: t('crm.knowledge.cat_documents') },
  { value: 'requirements', label: t('crm.knowledge.cat_requirements') },
  { value: 'faq', label: t('crm.knowledge.cat_faq') },
  { value: 'tips', label: t('crm.knowledge.cat_tips') },
  { value: 'common_mistakes', label: t('crm.knowledge.cat_common_mistakes') },
  { value: 'finance', label: t('crm.knowledge.cat_finance') },
  { value: 'changes', label: t('crm.knowledge.cat_changes') },
]);

function articleCategoryLabel(val) {
  return articleCategories.value.find(c => c.value === val)?.label ?? val;
}

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('ru-RU') : '';
}

let debounceTimer = null;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { page.value = 1; loadArticles(); }, 300);
}

async function loadArticles() {
  loading.value = true;
  try {
    const params = { page: page.value, per_page: 20 };
    if (search.value) params.search = search.value;
    if (filterCategory.value) params.category = filterCategory.value;
    if (filterCountry.value) params.country_code = filterCountry.value;
    const { data } = await api.get('/knowledge', { params });
    articles.value = data.data;
    pagination.value = data.meta || { last_page: 1, total: data.data.length };
    accessDenied.value = false;
  } catch (err) {
    if (err.response?.status === 403) {
      accessDenied.value = true;
    }
  } finally {
    loading.value = false;
  }
}

async function loadCountries() {
  try {
    const { data } = await api.get('/countries');
    countries.value = data.data || [];
  } catch { /* silent */ }
}

async function openArticle(article) {
  try {
    const { data } = await api.get(`/knowledge/${article.id}`);
    selectedArticle.value = data.data;
  } catch {
    selectedArticle.value = article;
  }
}

onMounted(() => {
  loadArticles();
  loadCountries();
});
</script>

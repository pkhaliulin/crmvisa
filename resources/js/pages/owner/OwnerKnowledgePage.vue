<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-lg font-bold text-[#0A1F44]">{{ t('owner.knowledge.title') }}</h1>
        <p class="text-sm text-gray-500">{{ t('owner.knowledge.subtitle') }}</p>
      </div>
      <button @click="openForm(null)"
        class="px-4 py-2 bg-[#0A1F44] text-white text-sm rounded-lg hover:bg-[#0A1F44]/90 transition">
        {{ t('owner.knowledge.newArticle') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-6">
        <button v-for="tab in tabs" :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'pb-3 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.id
              ? 'border-[#0A1F44] text-[#0A1F44]'
              : 'border-transparent text-gray-400 hover:text-gray-600'
          ]">
          {{ tab.label }}
          <span v-if="tab.count !== undefined"
            class="ml-1.5 text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full">
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Tab: Articles -->
    <template v-if="activeTab === 'articles'">
      <!-- Filters -->
      <div class="flex items-center gap-3 flex-wrap">
        <div class="relative flex-1 min-w-[200px] max-w-xs">
          <input v-model="search" @input="debouncedLoad"
            :placeholder="t('owner.knowledge.searchPlaceholder')"
            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
          <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <SearchSelect v-model="filterCategory" @change="loadArticles" compact
          :items="categories" allow-all :all-label="t('owner.knowledge.allCategories')" />
        <SearchSelect v-model="filterPublished" @change="loadArticles" compact
          :items="publishedOptions" allow-all :all-label="t('owner.knowledge.allStatuses')" />
      </div>

      <!-- Table -->
      <div v-if="loading" class="text-center py-10 text-gray-400">{{ t('common.loading') }}</div>
      <div v-else-if="articles.length === 0" class="text-center py-16 text-gray-400">
        {{ t('owner.knowledge.articlesNotFound') }}
      </div>
      <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-5 py-3 text-left font-medium text-gray-600">{{ t('owner.knowledge.tableTitle') }}</th>
              <th class="px-5 py-3 text-left font-medium text-gray-600">{{ t('owner.knowledge.tableCategory') }}</th>
              <th class="px-5 py-3 text-left font-medium text-gray-600">{{ t('owner.knowledge.tableCountry') }}</th>
              <th class="px-5 py-3 text-left font-medium text-gray-600">{{ t('owner.knowledge.tableStatus') }}</th>
              <th class="px-5 py-3 text-left font-medium text-gray-600">{{ t('owner.knowledge.tableViews') }}</th>
              <th class="px-5 py-3 text-right font-medium text-gray-600">{{ t('owner.knowledge.tableActions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="a in articles" :key="a.id" class="border-t border-gray-50 hover:bg-gray-50/50">
              <td class="px-5 py-3">
                <div class="font-medium text-gray-900">{{ a.title }}</div>
                <div v-if="a.title_uz" class="text-xs text-gray-400 mt-0.5">{{ a.title_uz }}</div>
              </td>
              <td class="px-5 py-3">
                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs">{{ categoryLabel(a.category) }}</span>
              </td>
              <td class="px-5 py-3 text-gray-500">{{ a.country_code || '---' }}</td>
              <td class="px-5 py-3">
                <span :class="a.is_published ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'"
                  class="px-2 py-0.5 rounded text-xs font-medium">
                  {{ a.is_published ? t('owner.knowledge.statusPublished') : t('owner.knowledge.statusDraft') }}
                </span>
              </td>
              <td class="px-5 py-3 text-gray-500">{{ a.view_count }}</td>
              <td class="px-5 py-3 text-right space-x-2">
                <button @click="togglePublish(a)" class="text-xs text-blue-600 hover:underline">
                  {{ a.is_published ? t('owner.knowledge.unpublish') : t('owner.knowledge.publish') }}
                </button>
                <button @click="openForm(a)" class="text-xs text-gray-600 hover:underline">{{ t('owner.knowledge.editShort') }}</button>
                <button @click="deleteArticle(a)" class="text-xs text-red-500 hover:underline">{{ t('common.delete') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-center gap-2">
        <button v-for="p in pagination.last_page" :key="p"
          @click="page = p; loadArticles()"
          :class="[
            'w-8 h-8 rounded-lg text-sm',
            p === page ? 'bg-[#0A1F44] text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
          ]">
          {{ p }}
        </button>
      </div>
    </template>

    <!-- Tab: Pending Notes (moderation) -->
    <template v-if="activeTab === 'pending'">
      <div v-if="loadingPending" class="text-center py-10 text-gray-400">{{ t('common.loading') }}</div>
      <div v-else-if="pendingNotes.length === 0" class="text-center py-16 text-gray-400">
        {{ t('owner.knowledge.noPendingNotes') }}
      </div>
      <div v-else class="space-y-4">
        <div v-for="note in pendingNotes" :key="note.id"
          class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
          <div class="flex items-start justify-between mb-3">
            <div>
              <div class="font-medium text-gray-900">{{ note.title }}</div>
              <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                <span>{{ note.agency?.name }}</span>
                <span>{{ note.author?.name }}</span>
                <span>{{ note.country_code }}</span>
                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded">{{ categoryLabel(note.category) }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button @click="moderateNote(note.id, 'approve')"
                class="px-3 py-1.5 bg-green-50 text-green-700 text-xs rounded-lg hover:bg-green-100 transition">
                {{ t('owner.knowledge.approve') }}
              </button>
              <button @click="moderateNote(note.id, 'merge')"
                class="px-3 py-1.5 bg-blue-50 text-blue-700 text-xs rounded-lg hover:bg-blue-100 transition">
                {{ t('owner.knowledge.mergeToKb') }}
              </button>
              <button @click="moderateNote(note.id, 'reject')"
                class="px-3 py-1.5 bg-red-50 text-red-600 text-xs rounded-lg hover:bg-red-100 transition">
                {{ t('owner.knowledge.reject') }}
              </button>
            </div>
          </div>
          <div class="text-sm text-gray-600 whitespace-pre-wrap bg-gray-50 rounded-lg p-3 max-h-40 overflow-y-auto">
            {{ note.content }}
          </div>
        </div>
      </div>
    </template>

    <!-- Tab: Stats -->
    <template v-if="activeTab === 'stats'">
      <div v-if="loadingStats" class="text-center py-10 text-gray-400">{{ t('common.loading') }}</div>
      <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div v-for="(value, key) in stats" :key="key"
          class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm">
          <div class="text-2xl font-bold text-[#0A1F44]">{{ value }}</div>
          <div class="text-xs text-gray-400 mt-1">{{ statsLabelsComputed[key] || key }}</div>
        </div>
      </div>
    </template>

    <!-- Modal: Article Form -->
    <div v-if="showForm" class="fixed inset-0 z-50 bg-black/40 flex items-start justify-center pt-10 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl p-6 mb-10" @click.stop>
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-lg font-bold text-[#0A1F44]">{{ editId ? t('owner.knowledge.editArticle') : t('owner.knowledge.newArticleTitle') }}</h2>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.titleRu') }} *</label>
            <input v-model="form.title" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.titleUz') }}</label>
            <input v-model="form.title_uz" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100" />
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.categoryLabel') }} *</label>
              <SearchSelect v-model="form.category" :items="categories" />
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.countryCode') }}</label>
              <input v-model="form.country_code" maxlength="2" placeholder="UZ, US..."
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" />
            </div>
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.visaType') }}</label>
              <input v-model="form.visa_type" placeholder="tourist, work..."
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" />
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.contentRu') }} *</label>
            <textarea v-model="form.content" rows="8"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg font-mono"></textarea>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.contentUz') }}</label>
            <textarea v-model="form.content_uz" rows="6"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg font-mono"></textarea>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.summaryRu') }}</label>
            <textarea v-model="form.summary" rows="2"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg"></textarea>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.tags') }}</label>
            <input v-model="form.tags_input" placeholder="visa, documents, deadline"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" />
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('owner.knowledge.sortOrder') }}</label>
            <input v-model.number="form.sort_order" type="number"
              class="w-32 px-3 py-2 text-sm border border-gray-200 rounded-lg" />
          </div>

          <div v-if="formError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>

          <div class="flex justify-end gap-3 pt-2">
            <button @click="showForm = false"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
              {{ t('common.cancel') }}
            </button>
            <button @click="saveArticle" :disabled="saving"
              class="px-4 py-2 text-sm bg-[#0A1F44] text-white rounded-lg hover:bg-[#0A1F44]/90 disabled:opacity-50">
              {{ saving ? t('owner.knowledge.saving') : (editId ? t('common.save') : t('owner.knowledge.newArticle')) }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const activeTab = ref('articles');
const articles = ref([]);
const loading = ref(true);
const search = ref('');
const filterCategory = ref('');
const filterPublished = ref('');
const page = ref(1);
const pagination = ref({ last_page: 1 });

const pendingNotes = ref([]);
const loadingPending = ref(true);

const stats = ref({});
const loadingStats = ref(true);

const showForm = ref(false);
const editId = ref(null);
const saving = ref(false);
const formError = ref('');
const form = ref(defaultForm());

const publishedOptions = computed(() => [
  { value: '1', label: t('owner.knowledge.published') },
  { value: '0', label: t('owner.knowledge.drafts') },
]);

const categories = computed(() => [
  { value: 'country_guide', label: t('owner.knowledge.categories.country_guide') },
  { value: 'visa_process', label: t('owner.knowledge.categories.visa_process') },
  { value: 'documents', label: t('owner.knowledge.categories.documents') },
  { value: 'requirements', label: t('owner.knowledge.categories.requirements') },
  { value: 'faq', label: t('owner.knowledge.categories.faq') },
  { value: 'tips', label: t('owner.knowledge.categories.tips') },
  { value: 'common_mistakes', label: t('owner.knowledge.categories.common_mistakes') },
  { value: 'finance', label: t('owner.knowledge.categories.finance') },
  { value: 'changes', label: t('owner.knowledge.categories.changes') },
]);

const statsLabelsComputed = computed(() => ({
  total: t('owner.knowledge.statsLabels.total'),
  published: t('owner.knowledge.statsLabels.published'),
  drafts: t('owner.knowledge.statsLabels.drafts'),
  total_views: t('owner.knowledge.statsLabels.total_views'),
  countries: t('owner.knowledge.statsLabels.countries'),
  pending_notes: t('owner.knowledge.statsLabels.pending_notes'),
}));

const tabs = computed(() => [
  { id: 'articles', label: t('owner.knowledge.tabArticles'), count: pagination.value.total },
  { id: 'pending', label: t('owner.knowledge.tabPending'), count: pendingNotes.value.length },
  { id: 'stats', label: t('owner.knowledge.tabStats') },
]);

function categoryLabel(val) {
  return categories.value.find(c => c.value === val)?.label ?? val;
}

function defaultForm() {
  return {
    title: '', title_uz: '', category: 'country_guide', country_code: '',
    visa_type: '', content: '', content_uz: '', summary: '', summary_uz: '',
    tags_input: '', sort_order: 0,
  };
}

function openForm(article) {
  if (article) {
    editId.value = article.id;
    form.value = {
      title: article.title || '',
      title_uz: article.title_uz || '',
      category: article.category || 'country_guide',
      country_code: article.country_code || '',
      visa_type: article.visa_type || '',
      content: article.content || '',
      content_uz: article.content_uz || '',
      summary: article.summary || '',
      summary_uz: article.summary_uz || '',
      tags_input: (article.tags || []).join(', '),
      sort_order: article.sort_order || 0,
    };
  } else {
    editId.value = null;
    form.value = defaultForm();
  }
  formError.value = '';
  showForm.value = true;
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
    if (filterPublished.value !== '') params.is_published = filterPublished.value;
    const { data } = await api.get('/owner/knowledge', { params });
    articles.value = data.data;
    pagination.value = data.meta || { last_page: 1, total: data.data.length };
  } finally {
    loading.value = false;
  }
}

async function loadPending() {
  loadingPending.value = true;
  try {
    const { data } = await api.get('/owner/knowledge/pending');
    pendingNotes.value = data.data;
  } finally {
    loadingPending.value = false;
  }
}

async function loadStats() {
  loadingStats.value = true;
  try {
    const { data } = await api.get('/owner/knowledge/stats');
    stats.value = data.data;
  } finally {
    loadingStats.value = false;
  }
}

async function saveArticle() {
  if (!form.value.title || !form.value.content || !form.value.category) {
    formError.value = t('owner.knowledge.requiredFields');
    return;
  }
  saving.value = true;
  formError.value = '';
  try {
    const payload = { ...form.value };
    payload.tags = payload.tags_input ? payload.tags_input.split(',').map(s => s.trim()).filter(Boolean) : [];
    delete payload.tags_input;
    if (!payload.country_code) payload.country_code = null;
    if (!payload.visa_type) payload.visa_type = null;

    if (editId.value) {
      await api.patch(`/owner/knowledge/${editId.value}`, payload);
    } else {
      await api.post('/owner/knowledge', payload);
    }
    showForm.value = false;
    loadArticles();
  } catch (err) {
    formError.value = err.response?.data?.message ?? t('owner.knowledge.saveError');
  } finally {
    saving.value = false;
  }
}

async function togglePublish(article) {
  try {
    await api.post(`/owner/knowledge/${article.id}/publish`);
    loadArticles();
  } catch { /* silent */ }
}

async function deleteArticle(article) {
  if (!confirm(t('owner.knowledge.deleteConfirm'))) return;
  try {
    await api.delete(`/owner/knowledge/${article.id}`);
    loadArticles();
  } catch { /* silent */ }
}

async function moderateNote(noteId, action) {
  try {
    await api.post(`/owner/knowledge/notes/${noteId}/moderate`, { action });
    loadPending();
    if (action === 'merge') loadArticles();
  } catch { /* silent */ }
}

onMounted(() => {
  loadArticles();
  loadPending();
  loadStats();
});
</script>

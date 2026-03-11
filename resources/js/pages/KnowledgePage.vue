<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('crm.knowledge.title') }}</h1>
        <p class="text-sm text-gray-500">{{ t('crm.knowledge.pageSubtitle') }}</p>
      </div>
      <button v-if="activeTab === 'notes'" @click="openForm(null)"
        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
        + {{ t('crm.knowledge.newNote') }}
      </button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
      <button @click="activeTab = 'notes'"
        :class="['px-4 py-1.5 text-sm rounded-lg transition-all font-medium',
          activeTab === 'notes' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
        {{ t('crm.knowledge.tabNotes') }}
      </button>
      <button @click="activeTab = 'articles'"
        :class="['px-4 py-1.5 text-sm rounded-lg transition-all font-medium',
          activeTab === 'articles' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
        {{ t('crm.knowledge.tabArticles') }}
      </button>
    </div>

    <!-- ==================== TAB: Notes ==================== -->
    <template v-if="activeTab === 'notes'">
      <!-- Filters -->
      <div class="flex items-center gap-3 flex-wrap">
        <div class="relative flex-1 min-w-[200px] max-w-xs">
          <input v-model="notesSearch" @input="debouncedLoadNotes"
            :placeholder="t('crm.knowledge.searchNotes')"
            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
          <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
        </div>
        <select v-model="notesFilterCategory" @change="loadNotes"
          class="text-sm border border-gray-200 rounded-lg px-3 py-2">
          <option value="">{{ t('crm.knowledge.allCategories') }}</option>
          <option v-for="c in noteCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
        </select>
        <CountrySelect
          v-model="notesFilterCountry"
          :countries="countries"
          :placeholder="t('crm.knowledge.allCountries')"
          allow-all
          :all-label="t('crm.knowledge.allCountries')"
          compact
          @change="loadNotes"
        />
      </div>

      <!-- Notes List -->
      <div v-if="notesLoading" class="text-center py-10 text-gray-400">{{ t('crm.knowledge.loading') }}</div>
      <div v-else-if="notes.length === 0" class="text-center py-16">
        <div class="text-gray-300 text-4xl mb-3">---</div>
        <div class="text-gray-400">{{ t('crm.knowledge.noNotes') }}</div>
      </div>
      <div v-else class="space-y-3">
        <div v-for="note in notes" :key="note.id"
          class="bg-white rounded-xl border border-gray-100 p-5 shadow-sm hover:shadow-md transition group">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <span v-if="note.is_pinned" class="text-amber-500 text-xs font-medium">PIN</span>
                <h3 class="font-medium text-gray-900 truncate">{{ note.title }}</h3>
              </div>
              <div class="flex items-center gap-3 mt-1.5 text-xs text-gray-400">
                <span v-if="note.country_code" class="uppercase">{{ note.country_code }}</span>
                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded">{{ noteCategoryLabel(note.category) }}</span>
                <span v-if="note.is_shared" :class="moderationBadge(note.moderation_status).class"
                  class="px-1.5 py-0.5 rounded text-xs">
                  {{ moderationBadge(note.moderation_status).label }}
                </span>
                <span>{{ note.author?.name }}</span>
                <span>{{ formatDate(note.created_at) }}</span>
              </div>
            </div>
            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
              <button @click="togglePin(note)" :title="note.is_pinned ? t('crm.knowledge.unpin') : t('crm.knowledge.pin')"
                class="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-amber-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
              </button>
              <button v-if="!note.is_shared" @click="shareNote(note)" :title="t('crm.knowledge.shareToGlobal')"
                class="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-blue-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
              </button>
              <button @click="openForm(note)"
                class="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button @click="deleteNote(note)"
                class="p-1.5 rounded hover:bg-gray-100 text-gray-400 hover:text-red-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-600 whitespace-pre-wrap line-clamp-3">{{ note.content }}</div>
        </div>
      </div>
    </template>

    <!-- ==================== TAB: Articles (Global KB) ==================== -->
    <template v-if="activeTab === 'articles'">
      <!-- Access denied -->
      <div v-if="articlesAccessDenied" class="text-center py-16">
        <div class="text-gray-300 text-4xl mb-3">---</div>
        <div class="text-gray-500 font-medium mb-1">{{ t('crm.knowledge.enterpriseOnly') }}</div>
        <p class="text-sm text-gray-400">{{ t('crm.knowledge.upgradeHint') }}</p>
      </div>

      <template v-else>
        <!-- Filters -->
        <div class="flex items-center gap-3 flex-wrap">
          <div class="relative flex-1 min-w-[200px] max-w-xs">
            <input v-model="articlesSearch" @input="debouncedLoadArticles"
              :placeholder="t('crm.knowledge.searchArticles')"
              class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
            <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
          <select v-model="articlesFilterCategory" @change="loadArticles"
            class="text-sm border border-gray-200 rounded-lg px-3 py-2">
            <option value="">{{ t('crm.knowledge.allCategories') }}</option>
            <option v-for="c in articleCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
          <CountrySelect
            v-model="articlesFilterCountry"
            :countries="countries"
            :placeholder="t('crm.knowledge.allCountries')"
            allow-all
            :all-label="t('crm.knowledge.allCountries')"
            compact
            @change="loadArticles"
          />
        </div>

        <!-- Articles -->
        <div v-if="articlesLoading" class="text-center py-10 text-gray-400">{{ t('crm.knowledge.loading') }}</div>
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
                <h3 class="font-medium text-gray-900">{{ loc(a, 'title') }}</h3>
                <p v-if="a.summary || a.summary_uz" class="text-sm text-gray-500 mt-1 line-clamp-2">{{ loc(a, 'summary') }}</p>
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
        <div v-if="articlesPagination.last_page > 1" class="flex items-center justify-center gap-2">
          <button v-for="p in articlesPagination.last_page" :key="p"
            @click="articlesPage = p; loadArticles()"
            :class="[
              'w-8 h-8 rounded-lg text-sm',
              p === articlesPage ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
            ]">
            {{ p }}
          </button>
        </div>
      </template>
    </template>

    <!-- Modal: Note Form -->
    <div v-if="showForm" class="fixed inset-0 z-50 bg-black/40 flex items-start justify-center pt-10 overflow-y-auto">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 mb-10" @click.stop>
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-lg font-bold text-gray-900">
            {{ editId ? t('crm.knowledge.editNote') : t('crm.knowledge.newNote') }}
          </h2>
          <button @click="showForm = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('crm.knowledge.noteTitle') }} *</label>
            <input v-model="form.title"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('crm.knowledge.category') }} *</label>
              <select v-model="form.category" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                <option v-for="c in noteCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
              </select>
            </div>
            <div>
              <CountrySelect
                v-model="form.country_code"
                :countries="countries"
                :label="t('crm.knowledge.country')"
                :placeholder="t('crm.knowledge.allCountries')"
              />
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('crm.knowledge.noteContent') }} *</label>
            <textarea v-model="form.content" rows="8"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg"></textarea>
          </div>
          <div v-if="formError" class="text-sm text-red-600 bg-red-50 rounded-lg px-3 py-2">{{ formError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button @click="showForm = false"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
              {{ t('crm.knowledge.cancel') }}
            </button>
            <button @click="saveNote" :disabled="saving"
              class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? t('crm.knowledge.saving') : t('crm.knowledge.save') }}
            </button>
          </div>
        </div>
      </div>
    </div>

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
            <h2 class="text-lg font-bold text-gray-900 mt-2">{{ loc(selectedArticle, 'title') }}</h2>
          </div>
          <button @click="selectedArticle = null" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div v-if="selectedArticle.summary || selectedArticle.summary_uz"
          class="text-sm text-gray-500 italic bg-gray-50 rounded-lg p-3 mb-4">
          {{ loc(selectedArticle, 'summary') }}
        </div>
        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-wrap">
          {{ loc(selectedArticle, 'content') }}
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
import { currentLocale } from '@/i18n';
import api from '@/api/index';
import CountrySelect from '@/components/CountrySelect.vue';
import { formatDate } from '@/utils/format';

const { t } = useI18n();

// --- State ---
const activeTab = ref('notes');
const countries = ref([]);

// Notes
const notes = ref([]);
const notesLoading = ref(true);
const notesSearch = ref('');
const notesFilterCategory = ref('');
const notesFilterCountry = ref('');
const showForm = ref(false);
const editId = ref(null);
const saving = ref(false);
const formError = ref('');
const form = ref(defaultForm());

// Articles
const articles = ref([]);
const articlesLoading = ref(true);
const articlesAccessDenied = ref(false);
const articlesSearch = ref('');
const articlesFilterCategory = ref('');
const articlesFilterCountry = ref('');
const articlesPage = ref(1);
const articlesPagination = ref({ last_page: 1 });
const selectedArticle = ref(null);

// --- Categories ---
const noteCategories = computed(() => [
  { value: 'process', label: t('crm.knowledge.cat_process') },
  { value: 'tips', label: t('crm.knowledge.cat_tips') },
  { value: 'contacts', label: t('crm.knowledge.cat_contacts') },
  { value: 'prices', label: t('crm.knowledge.cat_prices') },
  { value: 'timing', label: t('crm.knowledge.cat_timing') },
  { value: 'other', label: t('crm.knowledge.cat_other') },
]);

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

// --- Helpers ---
function noteCategoryLabel(val) {
  return noteCategories.value.find(c => c.value === val)?.label ?? val;
}
function articleCategoryLabel(val) {
  return articleCategories.value.find(c => c.value === val)?.label ?? val;
}
function moderationBadge(status) {
  const map = {
    pending:  { label: t('crm.knowledge.mod_pending'),  class: 'bg-amber-50 text-amber-600' },
    approved: { label: t('crm.knowledge.mod_approved'), class: 'bg-green-50 text-green-600' },
    rejected: { label: t('crm.knowledge.mod_rejected'), class: 'bg-red-50 text-red-600' },
    merged:   { label: t('crm.knowledge.mod_merged'),   class: 'bg-blue-50 text-blue-600' },
  };
  return map[status] || { label: status, class: 'bg-gray-50 text-gray-500' };
}
function defaultForm() {
  return { title: '', category: 'process', country_code: '', content: '' };
}

// Локализованное поле: если uz и есть _uz версия — вернуть её
function loc(obj, field) {
  if (currentLocale() === 'uz') {
    const uzVal = obj[field + '_uz'];
    if (uzVal) return uzVal;
  }
  return obj[field] || '';
}

// --- Notes ---
let notesDebounce = null;
function debouncedLoadNotes() {
  clearTimeout(notesDebounce);
  notesDebounce = setTimeout(loadNotes, 300);
}

async function loadNotes() {
  notesLoading.value = true;
  try {
    const params = {};
    if (notesSearch.value) params.search = notesSearch.value;
    if (notesFilterCategory.value) params.category = notesFilterCategory.value;
    if (notesFilterCountry.value) params.country_code = notesFilterCountry.value;
    const { data } = await api.get('/knowledge/notes', { params });
    notes.value = data.data;
  } finally {
    notesLoading.value = false;
  }
}

function openForm(note) {
  if (note) {
    editId.value = note.id;
    form.value = {
      title: note.title || '',
      category: note.category || 'process',
      country_code: note.country_code || '',
      content: note.content || '',
    };
  } else {
    editId.value = null;
    form.value = defaultForm();
  }
  formError.value = '';
  showForm.value = true;
}

async function saveNote() {
  if (!form.value.title || !form.value.content || !form.value.category) {
    formError.value = t('crm.knowledge.fillRequired');
    return;
  }
  saving.value = true;
  formError.value = '';
  try {
    const payload = { ...form.value };
    if (!payload.country_code) payload.country_code = null;
    if (editId.value) {
      await api.patch(`/knowledge/notes/${editId.value}`, payload);
    } else {
      await api.post('/knowledge/notes', payload);
    }
    showForm.value = false;
    loadNotes();
  } catch (err) {
    formError.value = err.response?.data?.message ?? t('crm.knowledge.saveError');
  } finally {
    saving.value = false;
  }
}

async function deleteNote(note) {
  if (!confirm(t('crm.knowledge.confirmDelete'))) return;
  try {
    await api.delete(`/knowledge/notes/${note.id}`);
    loadNotes();
  } catch { /* silent */ }
}

async function togglePin(note) {
  try {
    await api.post(`/knowledge/notes/${note.id}/pin`);
    loadNotes();
  } catch { /* silent */ }
}

async function shareNote(note) {
  if (!confirm(t('crm.knowledge.confirmShare'))) return;
  try {
    await api.post(`/knowledge/notes/${note.id}/share`);
    loadNotes();
  } catch { /* silent */ }
}

// --- Articles ---
let articlesDebounce = null;
function debouncedLoadArticles() {
  clearTimeout(articlesDebounce);
  articlesDebounce = setTimeout(() => { articlesPage.value = 1; loadArticles(); }, 300);
}

async function loadArticles() {
  articlesLoading.value = true;
  try {
    const params = { page: articlesPage.value, per_page: 20 };
    if (articlesSearch.value) params.search = articlesSearch.value;
    if (articlesFilterCategory.value) params.category = articlesFilterCategory.value;
    if (articlesFilterCountry.value) params.country_code = articlesFilterCountry.value;
    const { data } = await api.get('/knowledge', { params });
    articles.value = data.data;
    articlesPagination.value = data.meta || { last_page: 1, total: data.data.length };
    articlesAccessDenied.value = false;
  } catch (err) {
    if (err.response?.status === 403) {
      articlesAccessDenied.value = true;
    }
  } finally {
    articlesLoading.value = false;
  }
}

async function openArticle(article) {
  try {
    const { data } = await api.get(`/knowledge/${article.id}`);
    selectedArticle.value = data.data;
  } catch {
    selectedArticle.value = article;
  }
}

// --- Init ---
async function loadCountries() {
  try {
    const { data } = await api.get('/countries');
    countries.value = data.data || [];
  } catch { /* silent */ }
}

onMounted(() => {
  loadNotes();
  loadArticles();
  loadCountries();
});
</script>

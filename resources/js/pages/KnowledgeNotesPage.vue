<template>
  <div class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('crm.knowledge.notesTitle') }}</h1>
        <p class="text-sm text-gray-500">{{ t('crm.knowledge.notesSubtitle') }}</p>
      </div>
      <button @click="openForm(null)"
        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
        + {{ t('crm.knowledge.newNote') }}
      </button>
    </div>

    <!-- Filters -->
    <div class="flex items-center gap-3 flex-wrap">
      <div class="relative flex-1 min-w-[200px] max-w-xs">
        <input v-model="search" @input="debouncedLoad"
          :placeholder="t('crm.knowledge.searchNotes')"
          class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-100 focus:border-blue-400" />
        <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
      </div>
      <select v-model="filterCategory" @change="loadNotes"
        class="text-sm border border-gray-200 rounded-lg px-3 py-2">
        <option value="">{{ t('crm.knowledge.allCategories') }}</option>
        <option v-for="c in noteCategories" :key="c.value" :value="c.value">{{ c.label }}</option>
      </select>
      <select v-model="filterCountry" @change="loadNotes"
        class="text-sm border border-gray-200 rounded-lg px-3 py-2">
        <option value="">{{ t('crm.knowledge.allCountries') }}</option>
        <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
      </select>
    </div>

    <!-- Notes List -->
    <div v-if="loading" class="text-center py-10 text-gray-400">{{ t('crm.knowledge.loading') }}</div>
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
              <span v-if="note.is_pinned" class="text-amber-500 text-xs" title="Закреплено">PIN</span>
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
              <label class="text-sm font-medium text-gray-700 block mb-1">{{ t('crm.knowledge.country') }}</label>
              <select v-model="form.country_code" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                <option value="">---</option>
                <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }}</option>
              </select>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

const notes = ref([]);
const countries = ref([]);
const loading = ref(true);
const search = ref('');
const filterCategory = ref('');
const filterCountry = ref('');

const showForm = ref(false);
const editId = ref(null);
const saving = ref(false);
const formError = ref('');
const form = ref(defaultForm());

const noteCategories = computed(() => [
  { value: 'process', label: t('crm.knowledge.cat_process') },
  { value: 'tips', label: t('crm.knowledge.cat_tips') },
  { value: 'contacts', label: t('crm.knowledge.cat_contacts') },
  { value: 'prices', label: t('crm.knowledge.cat_prices') },
  { value: 'timing', label: t('crm.knowledge.cat_timing') },
  { value: 'other', label: t('crm.knowledge.cat_other') },
]);

function noteCategoryLabel(val) {
  return noteCategories.value.find(c => c.value === val)?.label ?? val;
}

function moderationBadge(status) {
  const map = {
    pending: { label: t('crm.knowledge.mod_pending'), class: 'bg-amber-50 text-amber-600' },
    approved: { label: t('crm.knowledge.mod_approved'), class: 'bg-green-50 text-green-600' },
    rejected: { label: t('crm.knowledge.mod_rejected'), class: 'bg-red-50 text-red-600' },
    merged: { label: t('crm.knowledge.mod_merged'), class: 'bg-blue-50 text-blue-600' },
  };
  return map[status] || { label: status, class: 'bg-gray-50 text-gray-500' };
}

function defaultForm() {
  return { title: '', category: 'process', country_code: '', content: '' };
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

function formatDate(d) {
  return d ? new Date(d).toLocaleDateString('ru-RU') : '';
}

let debounceTimer = null;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(loadNotes, 300);
}

async function loadNotes() {
  loading.value = true;
  try {
    const params = {};
    if (search.value) params.search = search.value;
    if (filterCategory.value) params.category = filterCategory.value;
    if (filterCountry.value) params.country_code = filterCountry.value;
    const { data } = await api.get('/knowledge/notes', { params });
    notes.value = data.data;
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

onMounted(() => {
  loadNotes();
  loadCountries();
});
</script>

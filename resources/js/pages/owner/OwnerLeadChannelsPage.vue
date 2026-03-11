<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('owner.leadChannels.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('owner.leadChannels.subtitle') }}</p>
      </div>
      <button @click="openCreate"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        {{ t('owner.leadChannels.addChannel') }}
      </button>
    </div>

    <!-- Статистика -->
    <div class="grid grid-cols-3 gap-4">
      <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ t('owner.leadChannels.totalChannels') }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
        <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ t('owner.leadChannels.activeChannels') }}</div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
        <div class="text-2xl font-bold text-amber-600">{{ stats.total - stats.active }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ t('owner.leadChannels.disabledSoon') }}</div>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="bg-white rounded-xl border border-gray-200 p-4">
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[200px]">
          <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <input v-model="search" :placeholder="t('owner.leadChannels.searchPlaceholder')"
            class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
        </div>

        <select v-model="filterCategory"
          class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-blue-400">
          <option value="">{{ t('owner.leadChannels.allCategories') }}</option>
          <option v-for="cat in categoryOptions" :key="cat.key" :value="cat.key">{{ cat.label }}</option>
        </select>

        <select v-model="filterActive"
          class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-blue-400">
          <option value="">{{ t('owner.leadChannels.allStatuses') }}</option>
          <option value="1">{{ t('owner.leadChannels.activeStatus') }}</option>
          <option value="0">{{ t('owner.leadChannels.disabledStatus') }}</option>
        </select>
      </div>
    </div>

    <!-- Таблица -->
    <div v-if="loading" class="text-center py-12 text-gray-400">{{ t('common.loading') }}</div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-500 w-10">#</th>
            <th class="px-4 py-3 font-medium text-gray-500">{{ t('owner.leadChannels.channel') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500">{{ t('owner.leadChannels.category') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 text-center w-28">{{ t('owner.leadChannels.complexity') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 text-center w-28">{{ t('owner.leadChannels.minPlan') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 text-center w-24">{{ t('owner.leadChannels.isActive') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 w-32">{{ t('owner.leadChannels.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(ch, idx) in filteredChannels" :key="ch.id"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 text-gray-400 text-xs">{{ idx + 1 }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="text-lg">{{ ch.icon }}</span>
                <div>
                  <div class="font-medium text-gray-900">{{ ch.name }}</div>
                  <div class="text-[10px] text-gray-400 font-mono">{{ ch.code }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 rounded-full" :class="categoryBadgeClass(ch.category)">
                {{ categoryLabel(ch.category) }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span class="text-xs px-2 py-0.5 rounded-full" :class="complexityBadgeClass(ch.complexity)">
                {{ complexityLabel(ch.complexity) }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span class="text-xs font-medium" :class="planColor(ch.min_plan)">{{ planLabel(ch.min_plan) }}</span>
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleActive(ch)"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                :class="ch.is_active ? 'bg-green-500' : 'bg-gray-200'">
                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                  :class="ch.is_active ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <router-link :to="{ name: 'owner.lead-channels.detail', params: { id: ch.id } }"
                  class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                  {{ t('common.edit') }}
                </router-link>
                <button @click="deleteChannel(ch)"
                  class="text-xs text-red-500 hover:text-red-700 font-medium">
                  {{ t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!filteredChannels.length" class="py-12 text-center text-gray-400 text-sm">
        {{ t('owner.leadChannels.notFound') }}
      </div>
    </div>

    <!-- Модал создания -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
      @click.self="showCreateModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <h2 class="text-lg font-bold text-gray-900 mb-4">{{ t('owner.leadChannels.newChannel') }}</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.codeSlug') }}</label>
              <input v-model="createForm.code" maxlength="50"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" placeholder="telegram_bot" />
              <span class="text-xs text-gray-400">{{ createForm.code.length }}/50</span>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.nameRu') }}</label>
              <input v-model="createForm.name" maxlength="255"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
              <span class="text-xs text-gray-400">{{ createForm.name.length }}/255</span>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.nameUz') }}</label>
              <input v-model="createForm.name_uz" maxlength="255"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.category') }}</label>
              <select v-model="createForm.category"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
                <option v-for="cat in categoryOptions" :key="cat.key" :value="cat.key">{{ cat.label }}</option>
              </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.complexity') }}</label>
                <select v-model="createForm.complexity"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
                  <option value="easy">{{ t('owner.leadChannels.complexities.easy') }}</option>
                  <option value="medium">{{ t('owner.leadChannels.complexities.medium') }}</option>
                  <option value="hard">{{ t('owner.leadChannels.complexities.hard') }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.minPlan') }}</label>
                <select v-model="createForm.min_plan"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
                  <option value="starter">Starter</option>
                  <option value="pro">Pro</option>
                  <option value="enterprise">Enterprise</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.iconEmoji') }}</label>
              <input v-model="createForm.icon" maxlength="10"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannels.shortDescriptionRu') }}</label>
              <textarea v-model="createForm.short_description" rows="2" maxlength="500"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-none"></textarea>
              <span class="text-xs text-gray-400">{{ (createForm.short_description || '').length }}/500</span>
            </div>
          </div>

          <div class="flex justify-end gap-3 mt-6">
            <button @click="showCreateModal = false"
              class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
              {{ t('common.cancel') }}
            </button>
            <button @click="handleCreate" :disabled="saving"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
              {{ saving ? t('owner.leadChannels.saving') : t('owner.leadChannels.create') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Модал удаления -->
    <div v-if="deleteTarget" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4"
      @click.self="deleteTarget = null">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ t('owner.leadChannels.deleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ deleteTarget.name }} ({{ deleteTarget.code }})</p>
        <div class="flex justify-end gap-3">
          <button @click="deleteTarget = null"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
          <button @click="confirmDelete" :disabled="saving"
            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50">
            {{ t('common.delete') }}
          </button>
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

const loading = ref(true);
const saving = ref(false);
const channels = ref([]);
const stats = ref({ total: 0, active: 0 });
const search = ref('');
const filterCategory = ref('');
const filterActive = ref('');

const showCreateModal = ref(false);
const deleteTarget = ref(null);

const createForm = ref({
  code: '',
  name: '',
  name_uz: '',
  category: 'messenger',
  complexity: 'easy',
  min_plan: 'starter',
  icon: '',
  short_description: '',
});

const categoryOptions = computed(() => [
  { key: 'messenger', label: t('owner.leadChannels.categories.messenger') },
  { key: 'advertising', label: t('owner.leadChannels.categories.advertising') },
  { key: 'web', label: t('owner.leadChannels.categories.web') },
  { key: 'content_seo', label: t('owner.leadChannels.categories.content_seo') },
  { key: 'partnership', label: t('owner.leadChannels.categories.partnership') },
  { key: 'api_automation', label: t('owner.leadChannels.categories.api_automation') },
]);

const filteredChannels = computed(() => {
  let list = [...channels.value];

  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase();
    list = list.filter(c =>
      c.name.toLowerCase().includes(q) ||
      (c.name_uz && c.name_uz.toLowerCase().includes(q)) ||
      c.code.toLowerCase().includes(q)
    );
  }

  if (filterCategory.value) {
    list = list.filter(c => c.category === filterCategory.value);
  }

  if (filterActive.value !== '') {
    const active = filterActive.value === '1';
    list = list.filter(c => c.is_active === active);
  }

  return list;
});

function categoryLabel(cat) {
  const map = {
    messenger: t('owner.leadChannels.categories.messenger'),
    advertising: t('owner.leadChannels.categories.advertising'),
    web: t('owner.leadChannels.categories.web'),
    content_seo: t('owner.leadChannels.categories.content_seo_short'),
    partnership: t('owner.leadChannels.categories.partnership'),
    api_automation: t('owner.leadChannels.categories.api_automation_short'),
  };
  return map[cat] || cat;
}

function categoryBadgeClass(cat) {
  const map = {
    messenger: 'bg-blue-50 text-blue-700',
    advertising: 'bg-red-50 text-red-700',
    web: 'bg-green-50 text-green-700',
    content_seo: 'bg-orange-50 text-orange-700',
    partnership: 'bg-purple-50 text-purple-700',
    api_automation: 'bg-indigo-50 text-indigo-700',
  };
  return map[cat] || 'bg-gray-50 text-gray-700';
}

function complexityLabel(c) {
  return {
    easy: t('owner.leadChannels.complexities.easy'),
    medium: t('owner.leadChannels.complexities.medium'),
    hard: t('owner.leadChannels.complexities.hard'),
  }[c] || c;
}

function complexityBadgeClass(c) {
  return { easy: 'bg-green-50 text-green-700', medium: 'bg-amber-50 text-amber-700', hard: 'bg-red-50 text-red-700' }[c] || 'bg-gray-50 text-gray-700';
}

function planLabel(p) {
  return { trial: 'Trial', starter: 'Starter', pro: 'Pro', enterprise: 'Enterprise' }[p] || p;
}

function planColor(p) {
  return { trial: 'text-gray-500', starter: 'text-blue-600', pro: 'text-purple-600', enterprise: 'text-amber-600' }[p] || 'text-gray-600';
}

async function fetchChannels() {
  try {
    loading.value = true;
    const { data } = await api.get('/owner/lead-channels');
    const payload = data?.data || data;
    channels.value = payload.channels || [];
    stats.value = { total: payload.total || 0, active: payload.active || 0 };
  } catch (e) {
    alert(e?.response?.data?.message || t('owner.leadChannels.loadError'));
  } finally {
    loading.value = false;
  }
}

async function toggleActive(ch) {
  try {
    const { data } = await api.post(`/owner/lead-channels/${ch.id}/toggle`);
    const togglePayload = data?.data || data;
    ch.is_active = togglePayload.is_active;
    stats.value.active += togglePayload.is_active ? 1 : -1;
  } catch (e) {
    alert(e?.response?.data?.message || t('owner.leadChannels.error'));
  }
}

function openCreate() {
  createForm.value = {
    code: '', name: '', name_uz: '', category: 'messenger',
    complexity: 'easy', min_plan: 'starter', icon: '', short_description: '',
  };
  showCreateModal.value = true;
}

async function handleCreate() {
  try {
    saving.value = true;
    await api.post('/owner/lead-channels', createForm.value);
    showCreateModal.value = false;
    await fetchChannels();
  } catch (e) {
    alert(e?.response?.data?.message || t('owner.leadChannels.createError'));
  } finally {
    saving.value = false;
  }
}

function deleteChannel(ch) {
  deleteTarget.value = ch;
}

async function confirmDelete() {
  try {
    saving.value = true;
    await api.delete(`/owner/lead-channels/${deleteTarget.value.id}`);
    deleteTarget.value = null;
    await fetchChannels();
  } catch (e) {
    alert(e?.response?.data?.message || t('owner.leadChannels.deleteError'));
  } finally {
    saving.value = false;
  }
}

onMounted(fetchChannels);
</script>

<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <p class="text-sm text-gray-500">{{ $t('references.description') }}</p>
    </div>

    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <template v-else>
      <!-- Category tabs -->
      <div class="border-b border-gray-200 overflow-x-auto">
        <nav class="flex gap-1">
          <button v-for="cat in categories" :key="cat.key"
            @click="activeCategory = cat.key"
            class="px-3 py-2.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
            :class="activeCategory === cat.key
              ? 'border-[#0A1F44] text-[#0A1F44]'
              : 'border-transparent text-gray-400 hover:text-gray-600'">
            {{ cat.label }}
            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">
              {{ getItems(cat.key).length }}
            </span>
          </button>
        </nav>
      </div>

      <!-- Active category content -->
      <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
          <span class="text-sm font-semibold text-gray-700">{{ activeCategoryLabel }}</span>
          <button @click="openCreate"
            class="px-3 py-1.5 bg-[#0A1F44] text-white text-xs font-semibold rounded-lg hover:bg-[#0d2a5e]">
            + {{ $t('references.addItem') }}
          </button>
        </div>

        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50/50 text-gray-500 text-xs uppercase">
              <th class="text-left px-5 py-2.5 font-semibold">{{ $t('references.code') }}</th>
              <th class="text-left px-5 py-2.5 font-semibold">{{ $t('references.labelRu') }}</th>
              <th class="text-left px-5 py-2.5 font-semibold">{{ $t('references.labelUz') }}</th>
              <th class="text-center px-5 py-2.5 font-semibold">{{ $t('references.order') }}</th>
              <th class="text-center px-5 py-2.5 font-semibold">{{ $t('references.status') }}</th>
              <th class="text-right px-5 py-2.5 font-semibold">{{ $t('references.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="item in activeItems" :key="item.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="px-5 py-2.5">
                <span class="font-mono text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ item.code }}</span>
              </td>
              <td class="px-5 py-2.5 text-gray-800 font-medium">{{ item.label_ru }}</td>
              <td class="px-5 py-2.5 text-gray-600">{{ item.label_uz || '—' }}</td>
              <td class="px-5 py-2.5 text-center text-gray-500">{{ item.sort_order }}</td>
              <td class="px-5 py-2.5 text-center">
                <button @click="toggleActive(item)"
                  class="text-xs px-2 py-0.5 rounded-full font-medium"
                  :class="item.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'">
                  {{ item.is_active ? $t('references.active') : $t('references.inactive') }}
                </button>
              </td>
              <td class="px-5 py-2.5 text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button @click="openEdit(item)"
                    class="text-xs px-2 py-1 border border-gray-200 rounded hover:bg-gray-50 text-gray-600">
                    {{ $t('common.edit') }}
                  </button>
                  <button @click="doDelete(item)"
                    class="text-xs px-2 py-1 border border-red-200 rounded hover:bg-red-50 text-red-600">
                    {{ $t('common.delete') }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="!activeItems.length" class="p-6 text-center text-gray-400 text-sm">
          {{ $t('references.empty') }}
        </div>
      </div>
    </template>

    <!-- Modal: Confirm Delete -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-red-600 text-lg mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-1">
          {{ $t('common.confirmDeleteMessage') }}
        </p>
        <p class="text-sm font-medium text-gray-800 mb-4">
          {{ deleteTarget.label_ru }} <span class="text-gray-400">({{ deleteTarget.code }})</span>
        </p>
        <div class="flex gap-3">
          <button @click="confirmDeleteAction" :disabled="deleting"
            class="flex-1 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ deleting ? $t('common.loading') : $t('common.confirmDeleteBtn') }}
          </button>
          <button @click="deleteTarget = null"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ $t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Create / Edit -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-4">
          {{ editTarget ? $t('references.editTitle') : $t('references.addTitle') }}
        </h3>

        <form @submit.prevent="save" class="space-y-3">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('references.code') }}</label>
            <input v-model="form.code" :disabled="!!editTarget" required pattern="[a-z_]+"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] disabled:bg-gray-50 disabled:text-gray-400 font-mono" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('references.labelRu') }}</label>
            <input v-model="form.label_ru" required
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('references.labelUz') }}</label>
            <input v-model="form.label_uz"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('references.order') }}</label>
            <input v-model.number="form.sort_order" type="number" min="0"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>

          <p v-if="error" class="text-xs text-red-600">{{ error }}</p>

          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="saving"
              class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
              {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
            <button type="button" @click="showModal = false"
              class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
              {{ $t('common.cancel') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

const allItems       = ref({});
const loading        = ref(true);
const saving         = ref(false);
const showModal      = ref(false);
const editTarget     = ref(null);
const error          = ref('');
const activeCategory = ref('');

const form = ref({ code: '', label_ru: '', label_uz: '', sort_order: 0 });

const categories = computed(() => [
  { key: 'lead_source',       label: t('references.cat_lead_source') },
  { key: 'employment_type',   label: t('references.cat_employment_type') },
  { key: 'marital_status',    label: t('references.cat_marital_status') },
  { key: 'income_type',       label: t('references.cat_income_type') },
  { key: 'travel_purpose',    label: t('references.cat_travel_purpose') },
  { key: 'education_level',   label: t('references.cat_education_level') },
  { key: 'document_category', label: t('references.cat_document_category') },
  { key: 'payment_method',    label: t('references.cat_payment_method') },
]);

const activeCategoryLabel = computed(() =>
  categories.value.find(c => c.key === activeCategory.value)?.label ?? activeCategory.value
);

function getItems(cat) {
  return allItems.value[cat] ?? [];
}

const activeItems = computed(() => getItems(activeCategory.value));

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/owner/references/all');
    allItems.value = data.data ?? {};
    if (!activeCategory.value && categories.value.length) {
      activeCategory.value = categories.value[0].key;
    }
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editTarget.value = null;
  form.value = { code: '', label_ru: '', label_uz: '', sort_order: activeItems.value.length };
  error.value = '';
  showModal.value = true;
}

function openEdit(item) {
  editTarget.value = item;
  form.value = {
    code: item.code,
    label_ru: item.label_ru,
    label_uz: item.label_uz || '',
    sort_order: item.sort_order,
  };
  error.value = '';
  showModal.value = true;
}

async function save() {
  saving.value = true;
  error.value = '';
  try {
    if (editTarget.value) {
      await api.patch(`/owner/references/${activeCategory.value}/${editTarget.value.id}`, {
        label_ru: form.value.label_ru,
        label_uz: form.value.label_uz,
        sort_order: form.value.sort_order,
      });
    } else {
      await api.post(`/owner/references/${activeCategory.value}`, form.value);
    }
    showModal.value = false;
    await load();
  } catch (err) {
    error.value = err.response?.data?.message || t('common.error');
  } finally {
    saving.value = false;
  }
}

async function toggleActive(item) {
  try {
    await api.patch(`/owner/references/${activeCategory.value}/${item.id}`, { is_active: !item.is_active });
    item.is_active = !item.is_active;
  } catch { /* ignore */ }
}

const deleteTarget = ref(null);
const deleting     = ref(false);

function doDelete(item) {
  deleteTarget.value = item;
}

async function confirmDeleteAction() {
  deleting.value = true;
  try {
    await api.delete(`/owner/references/${activeCategory.value}/${deleteTarget.value.id}`);
    deleteTarget.value = null;
    await load();
  } catch (err) {
    alert(err.response?.data?.message || t('common.error'));
  } finally {
    deleting.value = false;
  }
}

onMounted(load);
</script>

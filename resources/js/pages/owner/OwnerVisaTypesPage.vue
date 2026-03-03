<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <p class="text-sm text-gray-500">
        {{ $t('visaTypes.description') }}
      </p>
      <button @click="openCreate"
        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e]">
        + {{ $t('visaTypes.add') }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <!-- Table -->
    <div v-else class="bg-white rounded-xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 text-gray-500 text-xs uppercase">
            <th class="text-left px-5 py-3 font-semibold">{{ $t('visaTypes.slug') }}</th>
            <th class="text-left px-5 py-3 font-semibold">{{ $t('visaTypes.nameRu') }}</th>
            <th class="text-left px-5 py-3 font-semibold">{{ $t('visaTypes.nameUz') }}</th>
            <th class="text-center px-5 py-3 font-semibold">{{ $t('visaTypes.sortOrder') }}</th>
            <th class="text-center px-5 py-3 font-semibold">{{ $t('visaTypes.status') }}</th>
            <th class="text-center px-5 py-3 font-semibold">{{ $t('visaTypes.usage') }}</th>
            <th class="text-right px-5 py-3 font-semibold">{{ $t('visaTypes.actions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="vt in types" :key="vt.slug" class="hover:bg-gray-50/50 transition-colors">
            <td class="px-5 py-3">
              <span class="font-mono text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-600">{{ vt.slug }}</span>
            </td>
            <td class="px-5 py-3 text-gray-800 font-medium">{{ vt.name_ru }}</td>
            <td class="px-5 py-3 text-gray-600">{{ vt.name_uz || '—' }}</td>
            <td class="px-5 py-3 text-center text-gray-500">{{ vt.sort_order }}</td>
            <td class="px-5 py-3 text-center">
              <button @click="toggleActive(vt)"
                class="text-xs px-2 py-0.5 rounded-full font-medium"
                :class="vt.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'">
                {{ vt.is_active ? $t('visaTypes.active') : $t('visaTypes.inactive') }}
              </button>
            </td>
            <td class="px-5 py-3 text-center text-gray-400 text-xs">
              {{ vt.settings_count ?? 0 }} {{ $t('visaTypes.countries') }}
            </td>
            <td class="px-5 py-3 text-right">
              <div class="flex items-center justify-end gap-2">
                <button @click="openEdit(vt)"
                  class="text-xs px-2.5 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
                  {{ $t('common.edit') }}
                </button>
                <button @click="doDelete(vt)"
                  class="text-xs px-2.5 py-1 border border-red-200 rounded-lg hover:bg-red-50 text-red-600">
                  {{ $t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!types.length" class="p-8 text-center text-gray-400 text-sm">
        {{ $t('visaTypes.empty') }}
      </div>
    </div>

    <!-- Modal: Confirm Delete -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-red-600 text-lg mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-1">{{ $t('common.confirmDeleteMessage') }}</p>
        <p class="text-sm font-medium text-gray-800 mb-4">
          {{ deleteTarget.name_ru }} <span class="text-gray-400">({{ deleteTarget.slug }})</span>
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
          {{ editTarget ? $t('visaTypes.editTitle') : $t('visaTypes.addTitle') }}
        </h3>

        <form @submit.prevent="save" class="space-y-3">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('visaTypes.slug') }}</label>
            <input v-model="form.slug" :disabled="!!editTarget" required
              pattern="[a-z_]+" :placeholder="$t('visaTypes.slugHint')"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] disabled:bg-gray-50 disabled:text-gray-400 font-mono" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('visaTypes.nameRu') }}</label>
            <input v-model="form.name_ru" required
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('visaTypes.nameUz') }}</label>
            <input v-model="form.name_uz"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('visaTypes.sortOrder') }}</label>
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
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

const types     = ref([]);
const loading   = ref(true);
const saving    = ref(false);
const showModal = ref(false);
const editTarget = ref(null);
const error     = ref('');

const form = ref({ slug: '', name_ru: '', name_uz: '', sort_order: 0 });

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/owner/visa-types');
    types.value = data.data ?? [];
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  editTarget.value = null;
  form.value = { slug: '', name_ru: '', name_uz: '', sort_order: types.value.length };
  error.value = '';
  showModal.value = true;
}

function openEdit(vt) {
  editTarget.value = vt;
  form.value = { slug: vt.slug, name_ru: vt.name_ru, name_uz: vt.name_uz || '', sort_order: vt.sort_order };
  error.value = '';
  showModal.value = true;
}

async function save() {
  saving.value = true;
  error.value = '';
  try {
    if (editTarget.value) {
      await api.patch(`/owner/visa-types/${editTarget.value.slug}`, {
        name_ru: form.value.name_ru,
        name_uz: form.value.name_uz,
        sort_order: form.value.sort_order,
      });
    } else {
      await api.post('/owner/visa-types', form.value);
    }
    showModal.value = false;
    await load();
  } catch (err) {
    error.value = err.response?.data?.message || t('common.error');
  } finally {
    saving.value = false;
  }
}

async function toggleActive(vt) {
  try {
    await api.patch(`/owner/visa-types/${vt.slug}`, { is_active: !vt.is_active });
    vt.is_active = !vt.is_active;
  } catch { /* ignore */ }
}

const deleteTarget = ref(null);
const deleting     = ref(false);

function doDelete(vt) {
  deleteTarget.value = vt;
}

async function confirmDeleteAction() {
  deleting.value = true;
  try {
    await api.delete(`/owner/visa-types/${deleteTarget.value.slug}`);
    types.value = types.value.filter(t => t.slug !== deleteTarget.value.slug);
    deleteTarget.value = null;
  } catch (err) {
    alert(err.response?.data?.message || t('common.error'));
  } finally {
    deleting.value = false;
  }
}

onMounted(load);
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('owner.services.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('owner.services.subtitle') }}</p>
      </div>
      <button @click="openCreate"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        {{ t('owner.services.addService') }}
      </button>
    </div>

    <!-- Фильтр по категории -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="cat in ['all', ...categories]" :key="cat"
        @click="filterCat = cat"
        :class="['px-3 py-1 text-xs rounded-full border transition-colors',
          filterCat === cat
            ? 'bg-blue-600 text-white border-blue-600'
            : 'text-gray-600 border-gray-300 hover:bg-gray-50']">
        {{ cat === 'all' ? t('common.all') : categoryLabel(cat) }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">{{ t('common.loading') }}</div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-500">{{ t('owner.services.name') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500">{{ t('owner.services.category') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 text-center w-28">{{ t('owner.services.isRequired') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 text-center w-24">{{ t('owner.services.isActive') }}</th>
            <th class="px-4 py-3 font-medium text-gray-500 w-24">{{ t('owner.users.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="svc in filteredServices" :key="svc.id"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ svc.name }}</div>
              <div v-if="svc.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ svc.description }}</div>
              <div class="text-[10px] text-gray-300 font-mono mt-0.5">{{ svc.slug }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full">
                {{ categoryLabel(svc.category) }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleRequired(svc)"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                :class="svc.is_required ? 'bg-red-500' : 'bg-gray-200'">
                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                  :class="svc.is_required ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
              </button>
            </td>
            <td class="px-4 py-3 text-center">
              <button @click="toggleActive(svc)"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none"
                :class="svc.is_active ? 'bg-green-500' : 'bg-gray-200'">
                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                  :class="svc.is_active ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="openEdit(svc)"
                  class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                  {{ t('common.edit') }}
                </button>
                <button @click="deleteService(svc.id)"
                  class="text-xs text-red-500 hover:text-red-700 font-medium">
                  {{ t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredServices.length === 0">
            <td colspan="5" class="px-4 py-8 text-center text-gray-400">{{ t('owner.services.noServices') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Модал -->
    <div v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">
            {{ editingId ? t('owner.services.editService') : t('owner.services.newService') }}
          </h2>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.services.slug') }} *</label>
            <input v-model="form.slug" type="text" :disabled="!!editingId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50"
              placeholder="consultation-initial" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.services.name') }} *</label>
            <input v-model="form.name" type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.services.category') }} *</label>
            <SearchSelect v-model="form.category" :items="categoryItems" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.services.descriptionLabel') }}</label>
            <textarea v-model="form.description" rows="3"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :placeholder="t('owner.services.descriptionPlaceholder')"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.services.agencyHint') }}</label>
            <textarea v-model="form.agency_hint" rows="3"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              :placeholder="t('owner.services.agencyHintPlaceholder')"></textarea>
            <p class="text-xs text-gray-400 mt-1">{{ t('owner.services.agencyHintNote') }}</p>
          </div>
          <div class="flex gap-6">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <button type="button" @click="form.is_required = !form.is_required"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                :class="form.is_required ? 'bg-red-500' : 'bg-gray-200'">
                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                  :class="form.is_required ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
              </button>
              {{ t('owner.services.isRequired') }}
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <button type="button" @click="form.is_active = !form.is_active"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
                :class="form.is_active ? 'bg-green-500' : 'bg-gray-200'">
                <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform"
                  :class="form.is_active ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
              </button>
              {{ t('owner.services.isActive') }}
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_combinable"
                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
              {{ t('owner.services.isCombined') }}
            </label>
          </div>
        </div>

        <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
          <button @click="showModal = false"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
          <button @click="saveService" :disabled="saving"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? t('owner.services.saving') : t('common.save') }}
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
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const filterCat = ref('all');

const services = ref([]);

const categories = ['consultation', 'documents', 'translation', 'visa_center', 'financial', 'other'];

const categoryLabels = computed(() => ({
  consultation: t('owner.services.catConsultation'),
  documents: t('owner.services.catDocuments'),
  translation: t('owner.services.catTranslation'),
  visa_center: t('owner.services.catVisaCenter'),
  financial: t('owner.services.catFinancial'),
  other: t('owner.services.catOther'),
}));

function categoryLabel(cat) { return categoryLabels.value[cat] || cat; }

const categoryItems = computed(() => categories.map(cat => ({ value: cat, label: categoryLabel(cat) })));

const filteredServices = computed(() => {
  if (filterCat.value === 'all') return services.value;
  return services.value.filter(s => s.category === filterCat.value);
});

const defaultForm = () => ({
  slug: '', name: '', category: 'consultation', description: '', agency_hint: '',
  is_active: true, is_combinable: true, is_optional: true, is_required: false, sort_order: 0,
});

const form = ref(defaultForm());

onMounted(async () => {
  try {
    const res = await api.get('/owner/services');
    services.value = res.data.data || [];
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});

function openCreate() {
  editingId.value = null;
  form.value = defaultForm();
  showModal.value = true;
}

function openEdit(svc) {
  editingId.value = svc.id;
  form.value = { ...svc };
  showModal.value = true;
}

async function saveService() {
  saving.value = true;
  try {
    const payload = { ...form.value };
    payload.is_optional = !payload.is_required;
    if (editingId.value) {
      const res = await api.patch(`/owner/services/${editingId.value}`, payload);
      const idx = services.value.findIndex(s => s.id === editingId.value);
      if (idx !== -1) services.value[idx] = res.data.data;
    } else {
      const res = await api.post('/owner/services', payload);
      services.value.unshift(res.data.data);
    }
    showModal.value = false;
  } catch {
    // ignore
  } finally {
    saving.value = false;
  }
}

async function toggleActive(svc) {
  svc.is_active = !svc.is_active;
  await api.patch(`/owner/services/${svc.id}`, { is_active: svc.is_active }).catch(() => {
    svc.is_active = !svc.is_active;
  });
}

async function toggleRequired(svc) {
  svc.is_required = !svc.is_required;
  svc.is_optional = !svc.is_required;
  await api.patch(`/owner/services/${svc.id}`, { is_required: svc.is_required, is_optional: !svc.is_required }).catch(() => {
    svc.is_required = !svc.is_required;
    svc.is_optional = !svc.is_required;
  });
}

async function deleteService(id) {
  if (!confirm(t('owner.services.confirmDelete'))) return;
  await api.delete(`/owner/services/${id}`);
  services.value = services.value.filter(s => s.id !== id);
}
</script>

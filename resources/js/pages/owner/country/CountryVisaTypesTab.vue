<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <p class="text-sm text-gray-500">{{ $t('countryDetail.visaTypesDesc') }}</p>
      <button @click="openAdd"
        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e]">
        + {{ $t('countryDetail.addVisaType') }}
      </button>
    </div>

    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <!-- Таблица -->
    <div v-else-if="settings.length" class="bg-white rounded-xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500">
          <tr>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.name') }}</th>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.status') }}</th>
            <th class="text-left px-5 py-3 font-medium">{{ $t('countryDetail.description') }}</th>
            <th class="px-5 py-3 font-medium w-24"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="s in settings" :key="s.id" class="hover:bg-gray-50">
            <td class="px-5 py-3 font-medium text-gray-800">{{ s.visa_type }}</td>
            <td class="px-5 py-3">
              <span class="text-xs px-2 py-0.5 rounded-full"
                :class="s.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'">
                {{ s.is_active ? $t('countryDetail.active') : $t('countryDetail.inactive') }}
              </span>
            </td>
            <td class="px-5 py-3 text-gray-500 max-w-xs truncate">{{ s.description || '---' }}</td>
            <td class="px-5 py-3">
              <div class="flex gap-2 justify-end">
                <button @click="startEdit(s)" class="text-xs px-2.5 py-1 border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-600">
                  {{ $t('countryDetail.edit') }}
                </button>
                <button @click="deleteTarget = s" class="text-xs px-2.5 py-1 border border-red-200 rounded-lg hover:bg-red-50 text-red-600">
                  {{ $t('countryDetail.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
      {{ $t('countryDetail.noVisaSettings') }}
    </div>

    <!-- Modal: Add / Edit -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-4">
          {{ editingSetting ? $t('countryDetail.editVisaSetting') : $t('countryDetail.addVisaSetting') }}
        </h3>
        <form @submit.prevent="saveForm" class="space-y-4">
          <div v-if="!editingSetting">
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.visaTypeSlug') }}</label>
            <select v-model="formData.visa_type" required
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="" disabled>{{ $t('countryDetail.selectVisaType') }}</option>
              <option v-for="vt in availableVisaTypes" :key="vt.slug" :value="vt.slug">
                {{ vt.name_ru }} ({{ vt.slug }})
              </option>
            </select>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.status') }}</label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="formData.is_active" class="rounded" />
              {{ $t('countryDetail.active') }}
            </label>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.description') }}</label>
            <textarea v-model="formData.description" rows="3"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
          </div>
          <div class="flex gap-3 pt-2">
            <button type="submit" :disabled="saving"
              class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
              {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
            <button type="button" @click="showModal = false; editingSetting = null"
              class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
              {{ $t('common.cancel') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal: Confirm Delete -->
    <div v-if="deleteTarget" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-red-600 text-lg mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-1">{{ $t('common.confirmDeleteMessage') }}</p>
        <p class="text-sm font-medium text-gray-800 mb-4">{{ deleteTarget.visa_type }}</p>
        <div class="flex gap-3">
          <button @click="confirmDestroy" :disabled="saving"
            class="flex-1 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700 disabled:opacity-60">
            {{ saving ? $t('common.loading') : $t('common.confirmDeleteBtn') }}
          </button>
          <button @click="deleteTarget = null"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ $t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ownerCountriesApi } from '@/api/countries';
import api from '@/api/index';

const { t } = useI18n();
const props = defineProps({ countryCode: String, visaSettings: Array });
const emit  = defineEmits(['updated']);

const settings       = ref([]);
const loading        = ref(true);
const saving         = ref(false);
const showModal      = ref(false);
const editingSetting = ref(null);
const deleteTarget   = ref(null);
const allVisaTypes   = ref([]);

const availableVisaTypes = computed(() => {
  const usedSlugs = settings.value.map(s => s.visa_type);
  return allVisaTypes.value.filter(vt => vt.is_active && !usedSlugs.includes(vt.slug));
});

const formData = reactive({
  visa_type: '',
  is_active: true,
  description: '',
});

function openAdd() {
  editingSetting.value = null;
  formData.visa_type = '';
  formData.is_active = true;
  formData.description = '';
  showModal.value = true;
}

function startEdit(s) {
  editingSetting.value = s;
  formData.visa_type = s.visa_type;
  formData.is_active = s.is_active;
  formData.description = s.description ?? '';
  showModal.value = true;
}

async function loadSettings() {
  loading.value = true;
  try {
    const [settingsRes, vtRes] = await Promise.all([
      ownerCountriesApi.visaSettings(props.countryCode),
      api.get('/owner/visa-types'),
    ]);
    settings.value = settingsRes.data.data;
    allVisaTypes.value = vtRes.data.data ?? [];
  } finally {
    loading.value = false;
  }
}

async function saveForm() {
  saving.value = true;
  try {
    if (editingSetting.value) {
      await ownerCountriesApi.visaSettingUpdate(props.countryCode, editingSetting.value.id, {
        is_active: formData.is_active,
        description: formData.description,
      });
    } else {
      await ownerCountriesApi.visaSettingStore(props.countryCode, formData);
    }
    editingSetting.value = null;
    showModal.value = false;
    await loadSettings();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

async function confirmDestroy() {
  saving.value = true;
  try {
    await ownerCountriesApi.visaSettingDestroy(props.countryCode, deleteTarget.value.id);
    deleteTarget.value = null;
    await loadSettings();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

onMounted(loadSettings);
</script>

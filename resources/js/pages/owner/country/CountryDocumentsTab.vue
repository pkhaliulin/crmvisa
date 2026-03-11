<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <p class="text-sm text-gray-500">{{ $t('countryDetail.docsTabDesc') }}</p>
      <button @click="openAdd"
        class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e]">
        + {{ $t('countryDetail.addDoc') }}
      </button>
    </div>

    <div v-if="loading" class="text-sm text-gray-400">{{ $t('common.loading') }}</div>

    <template v-else>
      <!-- Счётчики по уровням -->
      <div class="flex gap-3">
        <span class="text-xs px-2.5 py-1 rounded-full bg-red-50 text-red-700 font-medium">
          {{ $t('countryDetail.levelRequired') }}: {{ countByLevel('required') }}
        </span>
        <span class="text-xs px-2.5 py-1 rounded-full bg-yellow-50 text-yellow-700 font-medium">
          {{ $t('countryDetail.levelRecommended') }}: {{ countByLevel('recommended') }}
        </span>
        <span class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 font-medium">
          {{ $t('countryDetail.levelConfirmation') }}: {{ countByLevel('confirmation_only') }}
        </span>
      </div>

      <!-- Группы по типу визы -->
      <div v-if="groups.length" class="space-y-4">
        <div v-for="g in groups" :key="g.visa_type"
          class="bg-white rounded-xl border border-gray-100 overflow-hidden">
          <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-gray-800">
                {{ g.visa_type === '*' ? $t('countryDetail.allVisaTypes') : g.visa_type }}
              </span>
              <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 font-medium">
                {{ g.docs.length }} {{ $t('countryDetail.docsCount') }}
              </span>
            </div>
          </div>

          <div class="divide-y divide-gray-50">
            <div v-for="doc in g.docs" :key="doc.id"
              class="flex items-center justify-between px-5 py-3 hover:bg-gray-50/50 transition-colors">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                  :class="{
                    'bg-red-500': doc.requirement_level === 'required',
                    'bg-yellow-500': doc.requirement_level === 'recommended',
                    'bg-gray-400': doc.requirement_level === 'confirmation_only',
                  }"></span>
                <div class="min-w-0">
                  <div class="text-sm text-gray-800 font-medium truncate">{{ doc.template_name }}</div>
                  <div class="text-[10px] text-gray-400" v-if="doc.notes">{{ doc.notes }}</div>
                </div>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-200 text-gray-500 flex-shrink-0">
                  {{ doc.template_category }}
                </span>
                <span class="text-[10px] px-1.5 py-0.5 rounded flex-shrink-0"
                  :class="doc.template_type === 'upload' ? 'bg-blue-50 text-blue-600' : 'bg-green-50 text-green-600'">
                  {{ doc.template_type === 'upload' ? $t('countryDetail.typeUpload') : $t('countryDetail.typeCheckbox') }}
                </span>
              </div>

              <div class="flex items-center gap-2 flex-shrink-0">
                <SearchSelect
                  :modelValue="doc.requirement_level"
                  @update:modelValue="updateLevel(doc.id, $event)"
                  :items="requirementLevelItems"
                  compact
                />
                <button @click="removeDoc(doc.id)"
                  class="text-red-400 hover:text-red-600 p-1 rounded hover:bg-red-50 transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else class="bg-white rounded-xl border border-gray-100 p-8 text-center text-gray-400 text-sm">
        {{ $t('countryDetail.noDocsYet') }}
      </div>
    </template>

    <!-- Modal: Add Document -->
    <div v-if="showModal" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-4">{{ $t('countryDetail.addDocTitle') }}</h3>

        <div class="space-y-3">
          <!-- Тип визы — мультивыбор -->
          <div>
            <label class="text-xs text-gray-500 mb-2 block">{{ $t('countryDetail.visaTypeLabel') }}</label>
            <div class="space-y-1.5">
              <label class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" v-model="form.allTypes" @change="onAllTypesToggle" class="rounded" />
                <span class="font-medium">{{ $t('countryDetail.allVisaTypes') }}</span>
              </label>
              <label v-for="vt in visaTypes" :key="vt" class="flex items-center gap-2 text-sm p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                <input type="checkbox" :value="vt" v-model="form.selectedTypes" class="rounded" />
                {{ vt }}
              </label>
            </div>
          </div>

          <!-- Шаблон документа -->
          <div>
            <SearchSelect
              v-model="form.document_template_id"
              :items="templateItems"
              :label="$t('countryDetail.docTemplate')"
              :placeholder="$t('countryDetail.selectDoc')"
            />
          </div>

          <!-- Уровень -->
          <div>
            <SearchSelect
              v-model="form.requirement_level"
              :items="requirementLevelItems"
              :label="$t('countryDetail.requirementLevel')"
            />
          </div>

          <!-- Примечание -->
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.docNotes') }}</label>
            <input v-model="form.notes"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>
        </div>

        <div class="flex gap-3 mt-4">
          <button @click="saveDoc" :disabled="!form.document_template_id || saving || (!form.allTypes && !form.selectedTypes.length)"
            class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
            {{ saving ? $t('common.loading') : $t('common.add') }}
          </button>
          <button @click="showModal = false"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ $t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Modal: Confirm Delete Document -->
    <div v-if="deleteDocId" class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-red-600 text-lg mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $t('common.confirmDeleteMessage') }}</p>
        <div class="flex gap-3">
          <button @click="confirmRemoveDoc"
            class="flex-1 py-2.5 bg-red-600 text-white font-semibold rounded-xl hover:bg-red-700">
            {{ $t('common.confirmDeleteBtn') }}
          </button>
          <button @click="deleteDocId = null"
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
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const props = defineProps({ countryCode: String });
const emit  = defineEmits(['updated']);

const requirements = ref([]);
const templates    = ref([]);
const visaTypes    = ref([]);
const loading      = ref(true);
const saving       = ref(false);
const showModal    = ref(false);

const form = reactive({
  allTypes: true,
  selectedTypes: [],
  document_template_id: '',
  requirement_level: 'required',
  notes: '',
});

const requirementLevelItems = computed(() => [
  { value: 'required', label: t('countryDetail.levelRequired') },
  { value: 'recommended', label: t('countryDetail.levelRecommended') },
  { value: 'confirmation_only', label: t('countryDetail.levelConfirmation') },
]);

const templateItems = computed(() =>
  filteredTemplates.value.map(tpl => ({ value: tpl.id, label: `${tpl.name} (${tpl.category})` }))
);

function onAllTypesToggle() {
  if (form.allTypes) {
    form.selectedTypes = [];
  }
}

// --- Computed ---

const groups = computed(() => {
  const map = {};
  for (const r of requirements.value) {
    const key = r.visa_type || '*';
    if (!map[key]) map[key] = { visa_type: key, docs: [] };
    map[key].docs.push(r);
  }
  // Сортировка: * первым, потом по алфавиту
  return Object.values(map).sort((a, b) => {
    if (a.visa_type === '*') return -1;
    if (b.visa_type === '*') return 1;
    return a.visa_type.localeCompare(b.visa_type);
  });
});

function countByLevel(level) {
  return requirements.value.filter(r => r.requirement_level === level).length;
}

const filteredTemplates = computed(() => {
  // Показываем шаблоны, которых нет ещё ни в одном выбранном типе
  const targetTypes = form.allTypes ? ['*'] : form.selectedTypes;
  const existing = requirements.value
    .filter(r => targetTypes.includes(r.visa_type))
    .map(r => r.document_template_id);
  return templates.value.filter(t => t.is_active && !existing.includes(t.id));
});

// --- Actions ---

async function loadData() {
  loading.value = true;
  try {
    const [reqRes, tplRes, vsRes] = await Promise.all([
      ownerCountriesApi.requirements(props.countryCode),
      ownerCountriesApi.documentTemplates(),
      ownerCountriesApi.visaSettings(props.countryCode),
    ]);
    requirements.value = reqRes.data.data ?? [];
    templates.value    = tplRes.data.data ?? [];
    visaTypes.value    = (vsRes.data.data ?? []).map(s => s.visa_type);
  } finally {
    loading.value = false;
  }
}

function openAdd() {
  form.allTypes = true;
  form.selectedTypes = [];
  form.document_template_id = '';
  form.requirement_level = 'required';
  form.notes = '';
  showModal.value = true;
}

async function saveDoc() {
  saving.value = true;
  try {
    // Определяем для каких типов виз создавать
    const targetTypes = form.allTypes
      ? ['*', ...visaTypes.value]  // Для всех: * + каждый тип отдельно
      : form.selectedTypes;

    // Создаём записи для каждого типа
    const promises = targetTypes.map(vt =>
      ownerCountriesApi.requirementStore({
        country_code: props.countryCode,
        visa_type: vt,
        document_template_id: form.document_template_id,
        requirement_level: form.requirement_level,
        notes: form.notes || null,
        is_active: true,
      }).catch(e => console.error('[CountryDocumentsTab]', e))
    );
    await Promise.all(promises);

    showModal.value = false;
    await loadData();
    emit('updated');
  } finally {
    saving.value = false;
  }
}

async function updateLevel(id, level) {
  try {
    await ownerCountriesApi.requirementUpdate(id, { requirement_level: level });
    const req = requirements.value.find(r => r.id === id);
    if (req) req.requirement_level = level;
  } catch { /* ignore */ }
}

const deleteDocId = ref(null);

function removeDoc(id) {
  deleteDocId.value = id;
}

async function confirmRemoveDoc() {
  const id = deleteDocId.value;
  deleteDocId.value = null;
  try {
    await ownerCountriesApi.requirementDestroy(id);
    requirements.value = requirements.value.filter(r => r.id !== id);
    emit('updated');
  } catch { /* ignore */ }
}

onMounted(loadData);
</script>

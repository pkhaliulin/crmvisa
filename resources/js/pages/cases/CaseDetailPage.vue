<template>
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="caseData" class="space-y-6 max-w-4xl">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-2xl">{{ flagEmoji }}</span>
            <h2 class="text-xl font-bold text-gray-900">
              {{ caseData.country_code }} — {{ caseData.visa_type }}
            </h2>
            <AppBadge :color="urgencyColor">{{ urgencyLabel }}</AppBadge>
          </div>
          <p class="text-sm text-gray-500">
            Клиент: <strong>{{ caseData.client?.name }}</strong>
            <span v-if="caseData.client?.phone"> · {{ caseData.client.phone }}</span>
          </p>
        </div>
        <div class="flex gap-2">
          <AppButton variant="outline" size="sm" @click="showMoveModal = true">
            Сменить этап
          </AppButton>
          <AppButton variant="danger" size="sm" @click="confirmDelete">
            Удалить
          </AppButton>
        </div>
      </div>

      <!-- Info grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-4 border-t">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Этап</p>
          <AppBadge :color="stageColor" class="mt-1">{{ stageLabel }}</AppBadge>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Приоритет</p>
          <p class="text-sm font-medium mt-1">{{ priorityLabel }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Дедлайн SLA</p>
          <p :class="['text-sm font-medium mt-1', deadlineClass]">{{ caseData.critical_date ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wide">Менеджер</p>
          <p class="text-sm font-medium mt-1">{{ caseData.assignee?.name ?? '—' }}</p>
        </div>
      </div>
    </div>

    <!-- Documents -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Документы</h3>
        <AppButton size="sm" @click="showDocUpload = true">+ Добавить</AppButton>
      </div>

      <div v-if="documents.length" class="space-y-2">
        <div v-for="doc in documents" :key="doc.id"
          class="flex items-center gap-3 py-2 px-3 rounded-lg bg-gray-50 border border-gray-100"
        >
          <span class="text-lg">📄</span>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ doc.original_name }}</p>
            <p class="text-xs text-gray-400">{{ doc.type }} · {{ doc.uploader?.name }}</p>
          </div>
          <AppBadge :color="docStatusColor(doc.status)">{{ doc.status }}</AppBadge>
          <a :href="doc.url" target="_blank" class="text-blue-600 text-xs hover:underline">Скачать</a>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">Документов нет</p>
    </div>

    <!-- Stage history -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h3 class="font-semibold text-gray-800 mb-4">История этапов</h3>
      <div class="space-y-3">
        <div v-for="h in caseData.stage_history" :key="h.id" class="flex items-start gap-3">
          <div class="w-2 h-2 rounded-full bg-blue-400 mt-1.5 shrink-0"></div>
          <div>
            <p class="text-sm font-medium">{{ STAGE_LABELS[h.stage] ?? h.stage }}</p>
            <p class="text-xs text-gray-400">{{ formatDate(h.entered_at) }} · {{ h.user?.name ?? '—' }}</p>
            <p v-if="h.notes" class="text-xs text-gray-600 mt-0.5">{{ h.notes }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Move stage modal -->
  <AppModal v-model="showMoveModal" title="Сменить этап">
    <div class="space-y-4">
      <AppSelect v-model="moveForm.stage" :options="stageOptions" label="Новый этап" />
      <AppInput v-model="moveForm.notes" label="Комментарий" placeholder="Необязательно..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showMoveModal = false">Отмена</AppButton>
        <AppButton :loading="moveForm.loading" @click="doMoveStage">Переместить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Doc upload modal -->
  <AppModal v-model="showDocUpload" title="Загрузить документ">
    <form @submit.prevent="uploadDoc" class="space-y-4">
      <div>
        <label class="text-sm font-medium text-gray-700">Файл <span class="text-red-500">*</span></label>
        <input type="file" @change="docFile = $event.target.files[0]" class="mt-1 block w-full text-sm" required />
      </div>
      <AppInput v-model="docType" label="Тип документа" placeholder="Паспорт, выписка, справка..." required />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" type="button" @click="showDocUpload = false">Отмена</AppButton>
        <AppButton type="submit" :loading="docLoading">Загрузить</AppButton>
      </div>
    </form>
  </AppModal>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { casesApi } from '@/api/cases';
import AppBadge from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput from '@/components/AppInput.vue';

const route   = useRoute();
const router  = useRouter();
const id      = route.params.id;

const caseData      = ref(null);
const documents     = ref([]);
const loading       = ref(true);
const showMoveModal = ref(false);
const showDocUpload = ref(false);
const docFile       = ref(null);
const docType       = ref('');
const docLoading    = ref(false);
const moveForm      = reactive({ stage: '', notes: '', loading: false });

const STAGE_LABELS = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'Рассмотрение', result: 'Результат',
};
const STAGE_COLORS = {
  lead: 'gray', qualification: 'blue', documents: 'purple',
  translation: 'yellow', appointment: 'orange', review: 'blue', result: 'green',
};
const stageOptions = Object.entries(STAGE_LABELS).map(([value, label]) => ({ value, label }));

const COUNTRY_FLAGS = {
  DE: '🇩🇪', FR: '🇫🇷', IT: '🇮🇹', ES: '🇪🇸', CZ: '🇨🇿', PL: '🇵🇱',
  US: '🇺🇸', GB: '🇬🇧', AE: '🇦🇪', TR: '🇹🇷', KR: '🇰🇷', CN: '🇨🇳',
};

const flagEmoji    = computed(() => COUNTRY_FLAGS[caseData.value?.country_code] ?? '🌍');
const stageLabel   = computed(() => STAGE_LABELS[caseData.value?.stage] ?? '');
const stageColor   = computed(() => STAGE_COLORS[caseData.value?.stage] ?? 'gray');
const urgencyColor = computed(() => {
  const u = caseData.value?.urgency ?? '';
  return u === 'overdue' ? 'red' : u === 'critical' ? 'yellow' : 'gray';
});
const urgencyLabel = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'Просрочено' : u === 'critical' ? 'Горящая' : 'В норме';
});
const deadlineClass = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'text-red-600' : u === 'critical' ? 'text-yellow-600' : 'text-gray-700';
});
const priorityMap = { low: 'Низкий', normal: 'Обычный', high: 'Высокий', urgent: 'Срочный' };
const priorityLabel = computed(() => priorityMap[caseData.value?.priority] ?? '');

function docStatusColor(s) {
  return s === 'approved' ? 'green' : s === 'rejected' ? 'red' : 'gray';
}
function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function load() {
  loading.value = true;
  try {
    const [cRes, dRes] = await Promise.all([
      casesApi.get(id),
      casesApi.getDocuments(id),
    ]);
    caseData.value  = cRes.data.data;
    documents.value = dRes.data.data;
  } finally {
    loading.value = false;
  }
}

async function doMoveStage() {
  if (!moveForm.stage) return;
  moveForm.loading = true;
  try {
    await casesApi.moveStage(id, { stage: moveForm.stage, notes: moveForm.notes || null });
    showMoveModal.value = false;
    await load();
  } finally {
    moveForm.loading = false;
  }
}

async function uploadDoc() {
  if (!docFile.value || !docType.value) return;
  docLoading.value = true;
  const form = new FormData();
  form.append('file', docFile.value);
  form.append('type', docType.value);
  try {
    await casesApi.uploadDocument(id, form);
    showDocUpload.value = false;
    docType.value = '';
    docFile.value = null;
    const { data } = await casesApi.getDocuments(id);
    documents.value = data.data;
  } finally {
    docLoading.value = false;
  }
}

async function confirmDelete() {
  if (!confirm('Удалить заявку? Это действие необратимо.')) return;
  await casesApi.remove(id);
  router.push({ name: 'cases' });
}

onMounted(load);
</script>

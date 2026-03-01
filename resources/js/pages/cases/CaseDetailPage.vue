<template>
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="caseData" class="space-y-6 max-w-4xl">

    <!-- Back nav -->
    <button @click="$router.back()"
        class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors -mb-2">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
      </svg>
      Назад
    </button>

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-2xl">{{ flagEmoji }}</span>
            <h2 class="text-xl font-bold text-gray-900">{{ countryName(caseData.country_code) }} — {{ visaTypeName(caseData.visa_type) }}</h2>
            <AppBadge :color="urgencyColor">{{ urgencyLabel }}</AppBadge>
          </div>
          <p class="text-sm text-gray-500">
            Клиент: <strong>{{ caseData.client?.name }}</strong>
            <span v-if="caseData.client?.phone"> · {{ caseData.client.phone }}</span>
          </p>
        </div>
        <div class="flex gap-2">
          <AppButton variant="outline" size="sm" @click="showMoveModal = true">Сменить этап</AppButton>
          <AppButton variant="danger"  size="sm" @click="confirmDelete">Удалить</AppButton>
        </div>
      </div>
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

    <!-- Documents checklist -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <!-- Panel header -->
      <div class="flex items-center justify-between mb-1">
        <div>
          <h3 class="font-semibold text-gray-800">Документы</h3>
          <p v-if="checklist.progress" class="text-xs text-gray-400 mt-0.5">
            {{ checklist.progress.uploaded }} из {{ checklist.progress.total }} загружено · {{ checklist.progress.percent }}%
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="uploadedCount > 0"
            @click="downloadZip"
            :disabled="zipLoading"
            class="flex items-center gap-1.5 text-xs text-gray-600 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-gray-50 disabled:opacity-50 transition-colors"
          >{{ zipLoading ? 'Подготовка...' : 'Скачать все (ZIP)' }}</button>
          <AppButton size="sm" variant="outline" @click="showAddSlot = true">+ Добавить</AppButton>
        </div>
      </div>

      <!-- Progress bar -->
      <div v-if="checklist.progress?.total > 0" class="w-full bg-gray-100 rounded-full h-1.5 mb-5 mt-3">
        <div
          class="h-1.5 rounded-full transition-all duration-500"
          :class="checklist.progress.percent === 100 ? 'bg-green-500' : 'bg-blue-500'"
          :style="{ width: checklist.progress.percent + '%' }"
        ></div>
      </div>

      <!-- Checklist items -->
      <div v-if="checklist.items?.length" class="space-y-3">
        <div
          v-for="item in checklist.items"
          :key="item.id"
          class="border rounded-xl overflow-hidden transition-colors"
          :class="itemBorderClass(item)"
        >
          <div class="flex items-start gap-3 p-4">
            <!-- Status icon -->
            <div class="shrink-0 mt-0.5 text-lg select-none">
              <span v-if="item.type === 'checkbox'">{{ item.is_checked ? '✅' : '⬜' }}</span>
              <span v-else>{{ item.status === 'approved' ? '✅' : item.status === 'rejected' ? '❌' : item.document ? '📎' : '📋' }}</span>
            </div>

            <!-- Main content -->
            <div class="flex-1 min-w-0">
              <!-- Document name — крупно, первым -->
              <div class="flex items-center gap-2 flex-wrap">
                <p class="text-sm font-semibold text-gray-900">{{ item.name }}</p>
                <span v-if="!item.is_required" class="text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded-md">опционально</span>
                <AppBadge :color="slotStatusColor(item)">{{ slotStatusLabel(item) }}</AppBadge>
              </div>

              <!-- Description -->
              <p v-if="item.description" class="text-xs text-gray-400 mt-1 leading-relaxed">{{ item.description }}</p>

              <!-- Uploaded file row -->
              <div v-if="item.document" class="mt-2 flex items-center gap-3 flex-wrap">
                <button
                  @click="openPreview(item.document)"
                  class="flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-medium"
                >
                  <span>{{ fileIcon(item.document.mime_type) }}</span>
                  <span class="truncate max-w-[220px]">{{ item.document.original_name }}</span>
                </button>
                <span class="text-gray-200 text-xs">|</span>
                <a :href="item.document.url" download class="text-xs text-gray-500 hover:text-gray-700">Скачать</a>
                <span class="text-gray-200 text-xs">|</span>
                <label class="text-xs text-gray-400 hover:text-gray-600 cursor-pointer">
                  Заменить
                  <input type="file" class="hidden" @change="uploadToSlot(item, $event)" />
                </label>
              </div>

              <!-- Reject note -->
              <p v-if="item.notes && item.status === 'rejected'" class="mt-2 text-xs text-red-600 bg-red-50 rounded-lg px-3 py-1.5">
                {{ item.notes }}
              </p>
            </div>

            <!-- Right-side actions -->
            <div class="shrink-0 flex items-center gap-2 flex-wrap justify-end">

              <!-- Checkbox toggle -->
              <template v-if="item.type === 'checkbox'">
                <button
                  @click="toggleCheck(item)"
                  class="text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors"
                  :class="item.is_checked ? 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100' : 'border-gray-200 text-gray-600 hover:bg-gray-50'"
                >{{ item.is_checked ? 'Готово' : 'Отметить' }}</button>
              </template>

              <!-- Upload button (пустой слот) -->
              <template v-else-if="!item.document">
                <label class="cursor-pointer text-xs px-3 py-1.5 rounded-lg border border-blue-200 text-blue-700 bg-blue-50 hover:bg-blue-100 font-medium">
                  Загрузить
                  <input type="file" class="hidden" @change="uploadToSlot(item, $event)" />
                </label>
              </template>

              <!-- Manager review buttons -->
              <template v-if="item.document && item.status === 'uploaded'">
                <button @click="reviewSlot(item, 'approved')" class="text-xs px-2.5 py-1.5 rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100">Принять</button>
                <button @click="openReject(item)"             class="text-xs px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100">Отклонить</button>
              </template>

              <!-- +1 для повторяемых (метрики детей) -->
              <button
                v-if="item.is_repeatable"
                @click="repeatSlot(item)"
                class="text-xs text-gray-400 hover:text-blue-600 px-1.5 py-1 rounded border border-dashed border-gray-300 hover:border-blue-400"
                title="Добавить ещё одного ребёнка"
              >+1</button>

              <!-- Удалить кастомный слот -->
              <button
                v-if="!item.requirement_id"
                @click="deleteSlot(item)"
                class="text-gray-300 hover:text-red-400 text-sm px-1 transition-colors"
              >✕</button>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400 py-6 text-center">Чек-лист документов пуст</p>
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

  <!-- Add custom slot modal -->
  <AppModal v-model="showAddSlot" title="Добавить документ">
    <div class="space-y-4">
      <AppInput v-model="newSlot.name" label="Название документа" placeholder="Напр: Справка из налоговой" />
      <AppInput v-model="newSlot.description" label="Пояснение" placeholder="Что именно нужно..." />
      <div class="flex items-center gap-2">
        <input type="checkbox" v-model="newSlot.is_required" id="slotReq" class="rounded" />
        <label for="slotReq" class="text-sm text-gray-700">Обязательный</label>
      </div>
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showAddSlot = false">Отмена</AppButton>
        <AppButton :loading="newSlot.loading" @click="addSlot">Добавить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- Reject note modal -->
  <AppModal v-model="showRejectModal" title="Причина отклонения">
    <div class="space-y-4">
      <AppInput v-model="rejectNote" label="Комментарий для клиента" placeholder="Что не так с документом..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="showRejectModal = false">Отмена</AppButton>
        <AppButton variant="danger" @click="submitReject">Отклонить</AppButton>
      </div>
    </div>
  </AppModal>

  <!-- File preview overlay -->
  <div
    v-if="preview"
    class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4"
    @click.self="preview = null"
  >
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
      <div class="flex items-center justify-between px-5 py-4 border-b shrink-0">
        <p class="font-medium text-gray-800 truncate max-w-[70%]">{{ preview.original_name }}</p>
        <div class="flex items-center gap-4">
          <a :href="preview.url" download class="text-sm text-blue-600 hover:underline">Скачать</a>
          <button @click="preview = null" class="text-gray-400 hover:text-gray-700 text-xl leading-none">✕</button>
        </div>
      </div>
      <div class="flex-1 overflow-auto p-4 min-h-0">
        <img
          v-if="isImage(preview.mime_type)"
          :src="preview.url"
          class="max-w-full mx-auto rounded-lg shadow"
          alt="preview"
        />
        <iframe
          v-else-if="isPdf(preview.mime_type)"
          :src="preview.url"
          class="w-full rounded-lg border"
          style="height: 70vh"
        ></iframe>
        <div v-else class="flex flex-col items-center justify-center py-16 text-gray-400">
          <span class="text-5xl mb-4">{{ fileIcon(preview.mime_type) }}</span>
          <p class="text-sm">Предпросмотр недоступен для этого формата</p>
          <a :href="preview.url" download class="mt-4 text-blue-600 text-sm hover:underline">Скачать файл</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { casesApi } from '@/api/cases';
import { useCountries } from '@/composables/useCountries';
import AppBadge  from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal  from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput  from '@/components/AppInput.vue';

const { countryName, countryFlag, visaTypeName } = useCountries();

const route  = useRoute();
const router = useRouter();
const id     = route.params.id;

const caseData        = ref(null);
const checklist       = ref({ items: [], progress: null });
const loading         = ref(true);
const showMoveModal   = ref(false);
const showAddSlot     = ref(false);
const showRejectModal = ref(false);
const rejectNote      = ref('');
const rejectItem      = ref(null);
const preview         = ref(null);
const zipLoading      = ref(false);
const moveForm        = reactive({ stage: '', notes: '', loading: false });
const newSlot         = reactive({ name: '', description: '', is_required: false, loading: false });

const STAGE_LABELS = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'Рассмотрение', result: 'Результат',
};
const STAGE_COLORS = {
  lead: 'gray', qualification: 'blue', documents: 'purple',
  translation: 'yellow', appointment: 'orange', review: 'blue', result: 'green',
};
const stageOptions  = Object.entries(STAGE_LABELS).map(([value, label]) => ({ value, label }));
const flagEmoji     = computed(() => countryFlag(caseData.value?.country_code ?? ''));
const stageLabel    = computed(() => STAGE_LABELS[caseData.value?.stage] ?? '');
const stageColor    = computed(() => STAGE_COLORS[caseData.value?.stage] ?? 'gray');
const urgencyColor  = computed(() => {
  const u = caseData.value?.urgency ?? '';
  return u === 'overdue' ? 'red' : u === 'critical' ? 'yellow' : 'gray';
});
const urgencyLabel  = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'Просрочено' : u === 'critical' ? 'Горящая' : 'В норме';
});
const deadlineClass = computed(() => {
  const u = caseData.value?.urgency;
  return u === 'overdue' ? 'text-red-600' : u === 'critical' ? 'text-yellow-600' : 'text-gray-700';
});
const priorityMap   = { low: 'Низкий', normal: 'Обычный', high: 'Высокий', urgent: 'Срочный' };
const priorityLabel = computed(() => priorityMap[caseData.value?.priority] ?? '');
const uploadedCount = computed(() =>
  (checklist.value.items ?? []).filter(i => i.document || i.is_checked).length
);

// ─── Helpers ─────────────────────────────────────────────────────────────────
function itemBorderClass(item) {
  if (item.status === 'approved')                       return 'border-green-200 bg-green-50/40';
  if (item.status === 'rejected')                       return 'border-red-200 bg-red-50/30';
  if (item.document || item.is_checked)                 return 'border-blue-200';
  return 'border-gray-200';
}
function slotStatusColor(item) {
  if (item.status === 'approved')                       return 'green';
  if (item.status === 'rejected')                       return 'red';
  if (item.status === 'uploaded' || item.is_checked)    return 'blue';
  return 'gray';
}
function slotStatusLabel(item) {
  if (item.status === 'approved')                       return 'Принято';
  if (item.status === 'rejected')                       return 'Отклонено';
  if (item.status === 'uploaded' || item.is_checked)    return 'На проверке';
  return item.is_required ? 'Ожидает' : 'Не загружен';
}
function fileIcon(mime) {
  if (!mime)                                            return '📄';
  if (mime.startsWith('image/'))                        return '🖼️';
  if (mime === 'application/pdf')                       return '📕';
  if (mime.includes('word'))                            return '📝';
  if (mime.includes('sheet') || mime.includes('excel')) return '📊';
  return '📄';
}
function isImage(mime) { return mime?.startsWith('image/'); }
function isPdf(mime)   { return mime === 'application/pdf'; }
function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}

// ─── Data ─────────────────────────────────────────────────────────────────────
async function load() {
  loading.value = true;
  try {
    const [cRes, clRes] = await Promise.all([
      casesApi.get(id),
      casesApi.getChecklist(id),
    ]);
    caseData.value  = cRes.data.data;
    checklist.value = clRes.data.data;
  } finally {
    loading.value = false;
  }
}
async function reloadChecklist() {
  const { data } = await casesApi.getChecklist(id);
  checklist.value = data.data;
}

// ─── Actions ──────────────────────────────────────────────────────────────────
async function uploadToSlot(item, event) {
  const file = event.target.files?.[0];
  if (!file) return;
  const form = new FormData();
  form.append('file', file);
  await casesApi.uploadToSlot(id, item.id, form);
  await reloadChecklist();
}

async function toggleCheck(item) {
  await casesApi.checkSlot(id, item.id, !item.is_checked);
  await reloadChecklist();
}

function openReject(item) {
  rejectItem.value = item;
  rejectNote.value = '';
  showRejectModal.value = true;
}
async function submitReject() {
  await casesApi.reviewSlot(id, rejectItem.value.id, { status: 'rejected', notes: rejectNote.value });
  showRejectModal.value = false;
  await reloadChecklist();
}
async function reviewSlot(item, status) {
  await casesApi.reviewSlot(id, item.id, { status });
  await reloadChecklist();
}

async function repeatSlot(item) {
  await casesApi.addChecklistItem(id, { name: item.name, description: item.description, is_required: false });
  await reloadChecklist();
}

async function addSlot() {
  if (!newSlot.name) return;
  newSlot.loading = true;
  try {
    await casesApi.addChecklistItem(id, {
      name: newSlot.name, description: newSlot.description, is_required: newSlot.is_required,
    });
    showAddSlot.value = false;
    Object.assign(newSlot, { name: '', description: '', is_required: false });
    await reloadChecklist();
  } finally {
    newSlot.loading = false;
  }
}

async function deleteSlot(item) {
  if (!confirm('Удалить этот пункт чек-листа?')) return;
  await casesApi.deleteChecklistItem(id, item.id);
  await reloadChecklist();
}

async function downloadZip() {
  zipLoading.value = true;
  try {
    const response = await casesApi.downloadAllZip(id);
    const url  = URL.createObjectURL(new Blob([response.data], { type: 'application/zip' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `docs-case-${id.slice(0, 8)}.zip`;
    link.click();
    URL.revokeObjectURL(url);
  } finally {
    zipLoading.value = false;
  }
}

function openPreview(doc) { preview.value = doc; }

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

async function confirmDelete() {
  if (!confirm('Удалить заявку? Это действие необратимо.')) return;
  await casesApi.remove(id);
  router.push({ name: 'cases' });
}

onMounted(load);
</script>

<template>
  <div class="flex flex-col h-full -m-6">
    <!-- Toolbar -->
    <div class="px-6 py-4 bg-white border-b flex items-center gap-4">
      <div class="flex gap-4 text-sm">
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
          Просрочено: <strong>{{ casesStore.stats.overdue }}</strong>
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
          Горящих: <strong>{{ casesStore.stats.critical }}</strong>
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
          Всего: <strong>{{ casesStore.stats.total }}</strong>
        </span>
        <span v-if="unassignedCount" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
          Без менеджера: {{ unassignedCount }}
        </span>
        <span v-if="awaitingPaymentCount" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">
          Ожидает оплаты: {{ awaitingPaymentCount }}
        </span>
      </div>
      <!-- Поиск по номеру заявки / клиенту -->
      <div class="relative ml-4">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="searchQuery" type="text"
          class="pl-8 pr-8 py-1.5 w-56 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
          placeholder="VB-XXXXXX / имя клиента..." />
        <button v-if="searchQuery" @click="searchQuery = ''"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <div class="ml-auto">
        <RouterLink :to="{ name: 'cases.create' }">
          <AppButton size="sm">+ Новая заявка</AppButton>
        </RouterLink>
      </div>
    </div>

    <!-- Board -->
    <div v-if="casesStore.loading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <div v-else class="flex-1 overflow-x-auto">
      <div class="flex gap-3 p-6 h-full" style="min-width: max-content">
        <KanbanColumn
          v-for="col in boardWithMeta"
          :key="col.key"
          :column="col"
          @move="handleMove"
          @open="openCase"
          @assign="handleAssign"
        />
      </div>
    </div>
  </div>

  <!-- Move stage modal -->
  <AppModal v-model="moveModal.show" title="Переместить заявку">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">
        Переместить заявку в этап:
      </p>
      <AppSelect v-model="moveModal.stage" :options="stageOptions" placeholder="Выберите этап" />
      <div v-if="moveModal.stage" class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
        {{ STAGES.find(s => s.value === moveModal.stage)?.tooltip }}
      </div>
      <AppInput v-model="moveModal.notes" label="Комментарий (необязательно)" placeholder="Причина перехода..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="moveModal.show = false">Отмена</AppButton>
        <AppButton :loading="moveModal.loading" @click="confirmMove">Переместить</AppButton>
      </div>
    </div>
  </AppModal>
</template>

<script setup>
import { ref, reactive, computed, onMounted, provide } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useCasesStore } from '@/stores/cases';
import KanbanColumn from '@/components/KanbanColumn.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput from '@/components/AppInput.vue';
import api from '@/api/index';

const STAGES = [
  {
    value: 'lead',
    label: 'Лид',
    icon: '📥',
    sla_hours: 1,
    tooltip: 'Связаться с клиентом в течение 1 часа. Уточнить цель поездки, сроки, состав группы.',
    goal: 'Связаться с клиентом в течение 1 часа',
    result: 'Клиент на связи, потребность понятна',
  },
  {
    value: 'qualification',
    label: 'Квалификация',
    icon: '🔍',
    sla_hours: 3,
    tooltip: 'Проверить паспорт, определить тип визы, сформировать чек-лист, определить дату записи в посольство.',
    goal: 'Оценить заявку, подготовить план, определить дату записи',
    result: 'Чек-лист готов, дата записи определена, план согласован',
  },
  {
    value: 'documents',
    label: 'Сбор документов',
    icon: '📋',
    sla_hours: 72,
    tooltip: 'Контролировать загрузку документов. Проверять качество сканов, запрашивать недостающие.',
    goal: 'Собрать все документы за 72 часа',
    result: 'Все документы загружены и готовы к проверке',
  },
  {
    value: 'doc_review',
    label: 'Проверка док.',
    icon: '🔎',
    sla_hours: 24,
    tooltip: 'Проверить каждый документ. Определить какие нужно перевести. Отклонить некачественные.',
    goal: 'Проверить документы и определить потребность в переводе за 24ч',
    result: 'Документы проверены, список на перевод готов',
  },
  {
    value: 'translation',
    label: 'Перевод',
    icon: '📝',
    sla_hours: 48,
    tooltip: 'Отправить документы на перевод. Контролировать сроки переводчика. Проверить качество.',
    goal: 'Получить качественные переводы за 48 часов',
    result: 'Переводы готовы и заверены',
  },
  {
    value: 'ready',
    label: 'Готов к подаче',
    icon: '📦',
    sla_hours: 24,
    tooltip: 'Проверить пакет, подтвердить дату записи, проинструктировать клиента.',
    goal: 'Подтвердить запись и проинструктировать клиента за 24ч',
    result: 'Пакет готов, запись подтверждена, клиент проинструктирован',
  },
  {
    value: 'review',
    label: 'Рассмотрение',
    icon: '⏳',
    sla_hours: null,
    tooltip: 'Ожидание решения посольства. Отслеживать статус, информировать клиента.',
    goal: 'Отслеживать статус и информировать клиента',
    result: 'Решение получено от посольства',
  },
  {
    value: 'result',
    label: 'Результат',
    icon: '✅',
    sla_hours: 4,
    tooltip: 'Одобрено: выдать паспорт, закрыть. Отказ: анализ ошибок, план повторной подачи.',
    goal: 'Завершить заявку или подготовить план при отказе',
    result: 'Заявка закрыта или план повторной подачи согласован',
  },
];

const casesStore = useCasesStore();
const router     = useRouter();
const stageOptions = STAGES.map(s => ({ value: s.value, label: `${s.icon} ${s.label}` }));

const searchQuery = ref('');
const managers = ref([]);
provide('kanbanManagers', managers);

async function loadManagers() {
  try {
    const { data } = await api.get('/users');
    managers.value = (data.data ?? data ?? []).filter(u => u.is_active !== false);
  } catch { /* ignore */ }
}

async function handleAssign({ caseId, managerId }) {
  try {
    await api.patch(`/cases/${caseId}`, { assigned_to: managerId });
    await casesStore.fetchKanban();
  } catch { /* ignore */ }
}

// Обогащаем колонки метаданными + фильтрация по поисковому запросу
const boardWithMeta = computed(() => {
  if (!casesStore.board) return [];
  const q = searchQuery.value.trim().toLowerCase();
  return casesStore.board.map(col => {
    const meta = STAGES.find(s => s.value === col.key) ?? {};
    const filtered = q
      ? col.cases.filter(c =>
          (c.case_number && c.case_number.toLowerCase().includes(q)) ||
          (c.client?.name && c.client.name.toLowerCase().includes(q)) ||
          (c.client?.phone && c.client.phone.includes(q))
        )
      : col.cases;
    return {
      ...col,
      cases: filtered,
      count: filtered.length,
      icon: meta.icon ?? '📌',
      tooltip: col.tooltip || meta.tooltip || '',
      sla_hours: col.sla_hours ?? meta.sla_hours ?? null,
    };
  });
});

const unassignedCount = computed(() => {
  if (!casesStore.board) return 0;
  return casesStore.board.reduce((sum, col) => sum + col.cases.filter(c => !c.assignee).length, 0);
});

const awaitingPaymentCount = computed(() => {
  if (!casesStore.board) return 0;
  return casesStore.board.reduce((sum, col) => sum + col.cases.filter(c => c.public_status === 'awaiting_payment').length, 0);
});

const moveModal = reactive({
  show: false, caseId: null, stage: '', notes: '', loading: false,
});

onMounted(() => {
  casesStore.fetchKanban();
  loadManagers();
});

function handleMove({ caseId, stage }) {
  moveModal.caseId = caseId;
  moveModal.stage  = stage;
  moveModal.notes  = '';
  moveModal.show   = true;
}

async function confirmMove() {
  if (!moveModal.stage) return;
  moveModal.loading = true;
  try {
    await casesStore.moveStage(moveModal.caseId, moveModal.stage, moveModal.notes || null);
    moveModal.show = false;
  } finally {
    moveModal.loading = false;
  }
}

function openCase(id) {
  router.push({ name: 'cases.show', params: { id } });
}
</script>

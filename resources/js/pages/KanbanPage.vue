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
import { reactive, computed, onMounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useCasesStore } from '@/stores/cases';
import KanbanColumn from '@/components/KanbanColumn.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput from '@/components/AppInput.vue';

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
    tooltip: 'Провести квалификацию за 3 часа: проверить паспорт, определить тип визы, сформировать список документов и стоимость.',
    goal: 'Оценить заявку и подготовить предложение за 3 часа',
    result: 'Чек-лист готов, стоимость озвучена, клиент согласен',
  },
  {
    value: 'awaiting_payment',
    label: 'Ожидание оплаты',
    icon: '💳',
    sla_hours: 24,
    tooltip: 'Клиент должен оплатить в течение 24 часов. При неоплате заявка вернётся в Квалификацию.',
    goal: 'Получить оплату в течение 24 часов',
    result: 'Оплата получена, можно приступать к документам',
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
    value: 'appointment',
    label: 'Запись',
    icon: '📅',
    sla_hours: 24,
    tooltip: 'Записать клиента на подачу в посольство/ВЦ. Подтвердить дату и время с клиентом.',
    goal: 'Записать на подачу и подготовить клиента за 24ч',
    result: 'Запись подтверждена, клиент проинструктирован',
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

// Обогащаем колонки метаданными (иконка, tooltip, sla_hours)
const boardWithMeta = computed(() => {
  if (!casesStore.board) return [];
  return casesStore.board.map(col => {
    const meta = STAGES.find(s => s.value === col.key) ?? {};
    return {
      ...col,
      icon: meta.icon ?? '📌',
      tooltip: col.tooltip || meta.tooltip || '',
      sla_hours: col.sla_hours ?? meta.sla_hours ?? null,
    };
  });
});

const moveModal = reactive({
  show: false, caseId: null, stage: '', notes: '', loading: false,
});

onMounted(() => casesStore.fetchKanban());

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

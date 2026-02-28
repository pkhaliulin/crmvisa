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
          v-for="col in casesStore.board"
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
        Переместить заявку <strong>{{ moveModal.caseId }}</strong> в этап:
      </p>
      <AppSelect v-model="moveModal.stage" :options="stageOptions" placeholder="Выберите этап" />
      <AppInput v-model="moveModal.notes" label="Комментарий (необязательно)" placeholder="Причина перехода..." />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="moveModal.show = false">Отмена</AppButton>
        <AppButton :loading="moveModal.loading" @click="confirmMove">Переместить</AppButton>
      </div>
    </div>
  </AppModal>
</template>

<script setup>
import { reactive, onMounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useCasesStore } from '@/stores/cases';
import KanbanColumn from '@/components/KanbanColumn.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput from '@/components/AppInput.vue';

const STAGES = [
  { value: 'lead',          label: 'Лид' },
  { value: 'qualification', label: 'Квалификация' },
  { value: 'documents',     label: 'Сбор документов' },
  { value: 'translation',   label: 'Перевод/нотариат' },
  { value: 'appointment',   label: 'Запись на подачу' },
  { value: 'review',        label: 'Рассмотрение' },
  { value: 'result',        label: 'Результат' },
];

const casesStore  = useCasesStore();
const router      = useRouter();
const stageOptions = STAGES;

const moveModal = reactive({
  show: false, caseId: null, stage: '', notes: '', loading: false,
});

onMounted(() => casesStore.fetchKanban());

function handleMove({ caseId, stage }) {
  moveModal.caseId  = caseId;
  moveModal.stage   = stage;
  moveModal.notes   = '';
  moveModal.show    = true;
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

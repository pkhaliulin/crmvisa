<template>
  <div class="flex flex-col w-64 shrink-0 bg-gray-100 rounded-xl overflow-hidden">
    <!-- Column header -->
    <div class="px-3 py-3 flex items-center gap-2">
      <h3 class="font-semibold text-sm text-gray-700 flex-1">{{ column.label }}</h3>
      <span class="bg-gray-200 text-gray-600 text-xs rounded-full px-2 py-0.5 font-medium">
        {{ column.count }}
      </span>
    </div>

    <!-- Cards (draggable) -->
    <draggable
      :list="column.cases"
      group="cases"
      item-key="id"
      class="flex-1 px-2 pb-2 space-y-2 min-h-20 overflow-y-auto"
      ghost-class="opacity-30"
      @end="onDragEnd"
    >
      <template #item="{ element }">
        <KanbanCard
          :item="element"
          @click="$emit('open', element.id)"
          @move="$emit('move', { caseId: element.id, stage: '' })"
        />
      </template>
    </draggable>
  </div>
</template>

<script setup>
import draggable from 'vuedraggable';
import KanbanCard from './KanbanCard.vue';

const props = defineProps({ column: Object });
const emit  = defineEmits(['move', 'open']);

function onDragEnd(event) {
  const { to, item } = event;
  const newStage = to.closest('[data-stage]')?.dataset.stage;
  const caseId   = item.querySelector('[data-id]')?.dataset.id;
  if (newStage && caseId) {
    emit('move', { caseId, stage: newStage });
  }
}
</script>

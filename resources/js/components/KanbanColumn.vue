<template>
  <div class="flex flex-col w-64 shrink-0 bg-gray-100 rounded-xl overflow-hidden">
    <!-- Column header -->
    <div class="px-3 py-3 flex items-center gap-2">
      <div class="flex items-center gap-1.5 flex-1 min-w-0">
        <span class="text-base leading-none">{{ column.icon }}</span>
        <h3 class="font-semibold text-sm text-gray-700 truncate">{{ column.label }}</h3>

        <!-- Tooltip кнопка -->
        <div v-if="column.tooltip" class="relative group">
          <button type="button"
            class="w-4 h-4 rounded-full bg-gray-300 text-gray-500 hover:bg-gray-400 hover:text-white
                   flex items-center justify-center text-xs leading-none transition-colors shrink-0"
            tabindex="-1">
            ?
          </button>
          <!-- Всплывающая подсказка -->
          <div class="absolute left-1/2 -translate-x-1/2 bottom-full mb-2 w-56 z-30
                      opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto
                      transition-opacity duration-150">
            <div class="bg-gray-900 text-white text-xs rounded-lg px-3 py-2.5 shadow-xl leading-relaxed">
              {{ column.tooltip }}
              <!-- Стрелка -->
              <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0
                          border-l-4 border-r-4 border-t-4
                          border-l-transparent border-r-transparent border-t-gray-900"></div>
            </div>
          </div>
        </div>
      </div>

      <span class="bg-gray-200 text-gray-600 text-xs rounded-full px-2 py-0.5 font-medium shrink-0">
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

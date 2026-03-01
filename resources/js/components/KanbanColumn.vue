<template>
  <div class="flex flex-col w-64 shrink-0 bg-gray-100 rounded-xl">
    <!-- Column header -->
    <div class="px-3 py-3 flex items-center gap-2">
      <div class="flex items-center gap-1.5 flex-1 min-w-0">
        <span class="text-base leading-none">{{ column.icon }}</span>
        <h3 class="font-semibold text-sm text-gray-700 truncate">{{ column.label }}</h3>

        <!-- Tooltip кнопка -->
        <div v-if="column.tooltip">
          <button type="button" ref="tipBtn"
            class="w-4 h-4 rounded-full bg-gray-300 text-gray-500 hover:bg-blue-100 hover:text-blue-600
                   flex items-center justify-center text-xs leading-none transition-colors shrink-0"
            tabindex="-1"
            @mouseenter="showTip" @mouseleave="hideTip" @focus="showTip" @blur="hideTip">
            ?
          </button>
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

  <!-- Tooltip через Teleport — рендерится в body, поверх overflow контейнеров -->
  <Teleport to="body">
    <Transition name="tip">
      <div v-if="tipVisible && tipPos"
        class="fixed z-[9999] w-60 pointer-events-none"
        :style="tipStyle">
        <div class="bg-white border border-blue-100 text-slate-600 text-xs rounded-xl
                    px-3.5 py-2.5 shadow-xl leading-relaxed">
          <!-- Иконка -->
          <div class="flex items-start gap-2">
            <svg class="w-3.5 h-3.5 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ column.tooltip }}</span>
          </div>
          <!-- Стрелка вниз -->
          <div class="absolute left-1/2 -translate-x-1/2 top-full w-0 h-0
                      border-l-[6px] border-r-[6px] border-t-[6px]
                      border-l-transparent border-r-transparent border-t-white
                      drop-shadow-sm"></div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.tip-enter-active, .tip-leave-active { transition: opacity 0.15s, transform 0.15s; }
.tip-enter-from, .tip-leave-to       { opacity: 0; transform: translateY(4px); }
</style>

<script setup>
import { ref, computed } from 'vue';
import { Teleport } from 'vue';
import draggable from 'vuedraggable';
import KanbanCard from './KanbanCard.vue';

const props = defineProps({ column: Object });
const emit  = defineEmits(['move', 'open']);

// ── Tooltip ──────────────────────────────────────────────────────────────────
const tipBtn     = ref(null);
const tipVisible = ref(false);
const tipPos     = ref(null);

function showTip() {
  const rect = tipBtn.value?.getBoundingClientRect();
  if (!rect) return;
  tipPos.value     = rect;
  tipVisible.value = true;
}
function hideTip() {
  tipVisible.value = false;
}

// Позиция: по центру над кнопкой
const TOOLTIP_W = 240; // w-60 = 15rem = 240px
const tipStyle = computed(() => {
  if (!tipPos.value) return {};
  const r = tipPos.value;
  const left = Math.max(8, r.left + r.width / 2 - TOOLTIP_W / 2);
  const top  = r.top - 8; // чуть выше кнопки — сдвигаем через transform
  return {
    left: left + 'px',
    top:  top  + 'px',
    transform: 'translateY(-100%)',
  };
});

// ── Drag & Drop ──────────────────────────────────────────────────────────────
function onDragEnd(event) {
  const { to, item } = event;
  const newStage = to.closest('[data-stage]')?.dataset.stage;
  const caseId   = item.querySelector('[data-id]')?.dataset.id;
  if (newStage && caseId) {
    emit('move', { caseId, stage: newStage });
  }
}
</script>

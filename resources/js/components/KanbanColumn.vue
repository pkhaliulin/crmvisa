<template>
  <div class="flex flex-col w-64 shrink-0 bg-gray-100 rounded-xl" :data-stage="column.key">
    <!-- Column header -->
    <div class="px-3 py-3 flex items-center gap-2">
      <div class="flex items-center gap-1.5 flex-1 min-w-0"
        @mouseenter="showTip" @mouseleave="hideTip">
        <span class="text-base leading-none">{{ column.icon }}</span>
        <h3 ref="tipBtn" class="font-semibold text-sm text-gray-700 truncate cursor-help">{{ column.label }}</h3>

        <!-- Tooltip кнопка -->
        <div v-if="column.tooltip">
          <span
            class="w-4 h-4 rounded-full bg-gray-300 text-gray-500
                   flex items-center justify-center text-xs leading-none shrink-0">
            ?
          </span>
        </div>
      </div>

      <span v-if="column.sla_hours" class="text-[10px] text-gray-400 font-medium shrink-0">
        {{ t('crm.kanbanPage.slaLabel', { n: column.sla_hours }) }}
      </span>
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
      :move="onMove"
      @end="onDragEnd"
    >
      <template #item="{ element }">
        <KanbanCard
          :item="element"
          @click="$emit('open', element.id)"
          @move="$emit('move', { caseId: element.id, stage: '' })"
          @assign="$emit('assign', $event)"
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
        <div class="relative bg-gray-900 text-white text-xs rounded-lg
                    px-3.5 py-2.5 shadow-xl leading-relaxed">
          <span>{{ column.tooltip }}</span>
          <!-- Стрелка вниз -->
          <div class="absolute left-1/2 -translate-x-1/2 top-full -mt-[1px]">
            <div class="w-2.5 h-2.5 bg-gray-900 rotate-45 -translate-y-1/2"></div>
          </div>
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
import { ref, computed, inject } from 'vue';
import { Teleport } from 'vue';
import { useI18n } from 'vue-i18n';
import draggable from 'vuedraggable';
import KanbanCard from './KanbanCard.vue';

const { t } = useI18n();

const props = defineProps({ column: Object });
const emit  = defineEmits(['move', 'open', 'assign']);

const allowedTransitions = inject('allowedTransitions', {});

// ── Tooltip ──────────────────────────────────────────────────────────────────
const tipBtn     = ref(null);
const tipVisible = ref(false);
const tipPos     = ref(null);

function showTip() {
  if (!props.column.tooltip) return;
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
// onMove вызывается ДО мутации DOM — return false отменяет перемещение
function onMove(evt) {
  const fromStage = evt.from.closest('[data-stage]')?.dataset.stage;
  const toStage   = evt.to.closest('[data-stage]')?.dataset.stage;
  if (!fromStage || !toStage || fromStage === toStage) return true;
  // Блокировать перемещение карточки без назначенного менеджера
  const caseItem = evt.draggedContext?.element;
  if (caseItem && !caseItem.assignee) return false;
  const allowed = allowedTransitions[fromStage] || [];
  return allowed.includes(toStage);
}

function onDragEnd(event) {
  const { from, to, item } = event;
  const fromStage = from.closest('[data-stage]')?.dataset.stage;
  const newStage  = to.closest('[data-stage]')?.dataset.stage;
  const caseId    = item.querySelector('[data-id]')?.dataset.id;
  if (newStage && caseId && newStage !== fromStage) {
    emit('move', { caseId, stage: newStage, fromStage });
  }
}
</script>

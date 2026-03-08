<template>
  <div
    :data-id="item.id"
    :class="[
      'bg-white rounded-lg p-3 shadow-sm border cursor-pointer hover:shadow-md transition-shadow select-none',
      cardBorder,
    ]"
    @click="$emit('click', item.id)"
  >
    <!-- Awaiting payment banner -->
    <div v-if="item.public_status === 'awaiting_payment'"
      class="flex items-center gap-1.5 -mx-3 -mt-3 mb-2 px-3 py-1.5 bg-amber-50 border-b border-amber-200 rounded-t-lg">
      <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <span class="text-[10px] font-semibold text-amber-700">Ожидает оплаты клиентом</span>
    </div>

    <!-- Header: country + priority -->
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-1.5">
        <span class="text-base">{{ flagEmoji }}</span>
        <span class="text-xs font-semibold text-gray-600 uppercase">{{ item.country_code }}</span>
        <span class="text-xs text-gray-400">{{ item.visa_type }}</span>
      </div>
      <AppBadge :color="priorityColor">{{ priorityLabel }}</AppBadge>
    </div>

    <!-- Case number -->
    <div v-if="item.case_number" class="text-[10px] font-mono text-gray-500 mb-1">
      № {{ item.case_number }}
    </div>

    <!-- Client name -->
    <div class="flex items-center gap-2 mb-1">
      <p class="text-sm font-medium text-gray-900 truncate">
        {{ item.client?.name ?? '—' }}
      </p>
    </div>
    <a v-if="item.client?.phone" :href="`tel:${item.client.phone}`" @click.stop class="text-xs text-gray-400 hover:text-blue-600 block">{{ formatPhone(item.client.phone) }}</a>
    <p v-else class="text-xs text-gray-400">—</p>

    <!-- SLA Stage timer -->
    <div v-if="item.stage_sla_hours_left !== null && item.stage_sla_hours_left !== undefined"
      :class="['flex items-center gap-1 mt-2 text-xs font-medium', slaTextColor]">
      <span>{{ slaIcon }}</span>
      <span>{{ slaLabel }}</span>
    </div>

    <!-- Deadline (critical_date) -->
    <div v-else-if="item.critical_date" :class="['flex items-center gap-1 mt-2 text-xs font-medium', urgencyText]"
      :title="item.deadline_info ? `Рассмотрение: ${item.deadline_info.processing_days} дн, ожидание приема: ${item.deadline_info.appointment_wait_days} дн, запас: ${item.deadline_info.buffer_days} дн` : ''">
      <span>{{ urgencyIcon }}</span>
      <span>{{ deadlineLabel }}</span>
    </div>

    <!-- Payment badge -->
    <div v-if="item.payment_status" class="mt-2">
      <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
        :class="paymentBadgeClass">
        {{ paymentLabel }}
      </span>
    </div>

    <!-- Appointment date (дата приема) — только для этапов подачи -->
    <div v-if="item.appointment_date" class="flex items-center gap-1 mt-2 text-xs font-medium text-green-600">
      <span>📅</span>
      <span>{{ item.appointment_date }}{{ item.appointment_time ? ' ' + item.appointment_time : '' }}</span>
    </div>
    <div v-else-if="['ready','review'].includes(item.stage)" class="flex items-center gap-1 mt-2 text-xs font-medium text-red-500">
      <span>🔴</span>
      <span>Дата приема не назначена</span>
    </div>

    <!-- Assignee / Assign button -->
    <div class="mt-2 flex items-center gap-1.5" @click.stop>
      <!-- Режим выбора менеджера (inline select) -->
      <template v-if="showAssignDropdown">
        <select
          class="text-xs border border-blue-300 rounded-lg px-2 py-0.5 outline-none focus:border-blue-500 bg-white w-full"
          autofocus
          @change="onSelectManager($event.target.value)"
          @blur="showAssignDropdown = false">
          <option value="">-- выберите --</option>
          <option v-for="m in managers" :key="m.id" :value="m.id"
            :selected="item.assigned_to === m.id">{{ m.name }}</option>
        </select>
        <button class="text-gray-300 hover:text-gray-500 text-xs shrink-0" @click.stop="showAssignDropdown = false">✕</button>
      </template>

      <!-- Назначен: аватар + имя + карандаш при ховере -->
      <template v-else-if="item.assignee">
        <div class="flex items-center gap-1 group cursor-pointer" @click.stop="managers?.length ? showAssignDropdown = true : null">
          <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center text-xs text-blue-600 font-bold shrink-0">
            {{ item.assignee.name[0] }}
          </div>
          <span class="text-xs text-gray-400 truncate">{{ item.assignee.name }}</span>
          <svg v-if="managers?.length" class="w-3 h-3 text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
          </svg>
        </div>
      </template>

      <!-- Не назначен: выделенная pill-кнопка как в заявках -->
      <template v-else>
        <button @click.stop="showAssignDropdown = true"
          class="text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2.5 py-0.5 rounded-full transition-colors flex items-center gap-1">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
          </svg>
          Назначить
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue';
import AppBadge from './AppBadge.vue';
import { formatPhone } from '@/utils/format';

const props = defineProps({ item: Object });
const emit = defineEmits(['click', 'move', 'assign']);

const managers = inject('kanbanManagers', ref([]));
const showAssignDropdown = ref(false);

function onSelectManager(managerId) {
  showAssignDropdown.value = false;
  if (!managerId) return;
  emit('assign', { caseId: props.item.id, managerId });
}

const COUNTRY_FLAGS = {
  DE: '🇩🇪', FR: '🇫🇷', IT: '🇮🇹', ES: '🇪🇸', CZ: '🇨🇿', PL: '🇵🇱',
  US: '🇺🇸', GB: '🇬🇧', AE: '🇦🇪', TR: '🇹🇷', KR: '🇰🇷', CN: '🇨🇳',
  UZ: '🇺🇿', KZ: '🇰🇿', RU: '🇷🇺', IN: '🇮🇳', JP: '🇯🇵',
};

const flagEmoji     = computed(() => COUNTRY_FLAGS[props.item.country_code] ?? '🌍');
const priorityMap   = {
  low:    { color: 'gray',   label: 'Низкий' },
  normal: { color: 'blue',   label: 'Обычный' },
  high:   { color: 'orange', label: 'Высокий' },
  urgent: { color: 'red',    label: 'Срочный' },
};
const priorityColor = computed(() => priorityMap[props.item.priority]?.color ?? 'gray');
const priorityLabel = computed(() => priorityMap[props.item.priority]?.label ?? '');

// Border: SLA overdue (red bg) > urgency overdue > critical > normal
const cardBorder = computed(() => {
  if (props.item.public_status === 'awaiting_payment') return 'border-amber-300 bg-amber-50/30';
  if (props.item.stage_sla_overdue) return 'border-red-400 bg-red-50/50';
  if (props.item.urgency === 'overdue')  return 'border-red-300';
  if (props.item.urgency === 'critical') return 'border-yellow-300';
  return 'border-gray-100';
});

const urgencyText = computed(() => {
  if (props.item.urgency === 'overdue')  return 'text-red-600';
  if (props.item.urgency === 'critical') return 'text-yellow-600';
  return 'text-gray-400';
});
const urgencyIcon = computed(() => {
  if (props.item.urgency === 'overdue')  return '🔴';
  if (props.item.urgency === 'critical') return '🟡';
  return '⏰';
});
const deadlineLabel = computed(() => {
  const d = props.item.days_left;
  if (d === null || d === undefined) return '';
  if (d < 0)  return `Просрочено на ${Math.abs(d)} дн.`;
  if (d === 0) return 'Сегодня дедлайн!';
  return `${d} дн. до дедлайна`;
});

// SLA stage timer
const slaTextColor = computed(() => {
  if (props.item.stage_sla_overdue) return 'text-red-600';
  if (props.item.stage_sla_hours_left <= 2) return 'text-orange-500';
  return 'text-blue-500';
});
const slaIcon = computed(() => {
  if (props.item.stage_sla_overdue) return '🔴';
  if (props.item.stage_sla_hours_left <= 2) return '🟠';
  return '⏱️';
});
const slaLabel = computed(() => {
  const h = props.item.stage_sla_hours_left;
  if (h === null || h === undefined) return '';
  if (h < 0) return `Просрочено на ${Math.abs(h)} ч`;
  if (h === 0) return 'SLA истекает сейчас!';
  return `SLA: ${h} ч осталось`;
});

// Payment badge
const paymentBadgeClass = computed(() => {
  const s = props.item.payment_status;
  if (s === 'paid') return 'bg-green-50 text-green-700';
  if (s === 'pending') return 'bg-amber-50 text-amber-600';
  return 'bg-gray-50 text-gray-400';
});
const paymentLabel = computed(() => {
  const s = props.item.payment_status;
  if (s === 'paid') return 'Оплачено';
  if (s === 'pending') return 'Ожидает оплаты';
  return 'Не оплачено';
});
</script>

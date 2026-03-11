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
      <span class="text-[10px] font-semibold text-amber-700">{{ t('crm.card.awaitingPayment') }}</span>
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

    <!-- Source & payment badges -->
    <div v-if="item.source === 'marketplace' || item.payment_status !== 'paid'"
      class="flex flex-wrap gap-1 mb-1.5">
      <span v-if="item.source === 'marketplace'"
        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-green-50 border border-green-200 text-green-700">
        {{ t('crm.card.sourceMarketplace') }}
      </span>
      <span v-if="item.payment_status !== 'paid'"
        class="inline-flex items-center text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 cursor-help"
        :title="t('crm.card.notPaidTooltip')">
        {{ t('crm.card.notPaid') }}
      </span>
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
      :title="deadlineTooltip">
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
      <span>{{ t('crm.card.appointmentNotSet') }}</span>
    </div>

    <!-- Assignee / Assign button -->
    <div class="mt-2 flex items-center gap-1.5" @click.stop>
      <!-- Режим выбора менеджера (inline select) -->
      <template v-if="showAssignDropdown">
        <SearchSelect
          :model-value="item.assigned_to ? String(item.assigned_to) : ''"
          :items="managerItems"
          :placeholder="t('crm.card.selectManager')"
          compact
          show-initials
          @change="onSelectManager"
          class="flex-1"
        />
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
          {{ t('crm.card.assign') }}
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import AppBadge from './AppBadge.vue';
import SearchSelect from './SearchSelect.vue';
import { formatPhone } from '@/utils/format';

const { t } = useI18n();

const props = defineProps({ item: Object });
const emit = defineEmits(['click', 'move', 'assign']);

const managers = inject('kanbanManagers', ref([]));
const showAssignDropdown = ref(false);

const managerItems = computed(() =>
  (managers.value || []).map(m => ({ value: String(m.id), label: m.name }))
);

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

const PRIORITY_COLORS = { low: 'gray', normal: 'blue', high: 'orange', urgent: 'red' };
const priorityColor = computed(() => PRIORITY_COLORS[props.item.priority] ?? 'gray');
const priorityLabel = computed(() => {
  const key = props.item.priority;
  return key ? t(`crm.priority.${key}`) : '';
});

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
  if (d < 0)  return t('crm.card.overdueBy', { n: Math.abs(d) });
  if (d === 0) return t('crm.card.deadlineToday');
  return t('crm.card.daysLeft', { n: d });
});

const deadlineTooltip = computed(() => {
  if (!props.item.deadline_info) return '';
  const info = props.item.deadline_info;
  return `${info.processing_days}d / ${info.appointment_wait_days}d / ${info.buffer_days}d`;
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
function formatSlaDuration(totalHours, totalMinutes) {
  const abs = Math.abs(totalHours);
  const absMin = totalMinutes !== null && totalMinutes !== undefined ? Math.abs(totalMinutes) : abs * 60;
  if (abs >= 24) {
    const days = Math.floor(abs / 24);
    const hours = abs % 24;
    return hours > 0
      ? t('crm.card.durationDaysHours', { d: days, h: hours })
      : t('crm.card.durationDays', { d: days });
  }
  if (abs >= 1) {
    return t('crm.card.durationHours', { h: abs });
  }
  // < 1 hour — show minutes
  if (absMin > 0) {
    return t('crm.card.durationMinutesN', { m: absMin });
  }
  return t('crm.card.durationMinutes');
}

const slaLabel = computed(() => {
  const h = props.item.stage_sla_hours_left;
  const m = props.item.stage_sla_minutes_left;
  if (h === null || h === undefined) return '';
  if (h < 0) return t('crm.card.slaOverdueByFormatted', { duration: formatSlaDuration(h, m) });
  if (h === 0 && (m === null || m === undefined || m <= 0)) return t('crm.card.slaExpiresNow');
  if (h === 0) return t('crm.card.slaLeftFormatted', { duration: formatSlaDuration(h, m) });
  return t('crm.card.slaLeftFormatted', { duration: formatSlaDuration(h, m) });
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
  if (s === 'paid') return t('crm.card.paid');
  if (s === 'pending') return t('crm.card.pendingPayment');
  return t('crm.card.unpaid');
});
</script>

<template>
  <div
    :data-id="item.id"
    :class="[
      'bg-white rounded-lg p-3 shadow-sm border cursor-pointer hover:shadow-md transition-shadow select-none',
      urgencyBorder,
    ]"
    @click="$emit('click', item.id)"
  >
    <!-- Header: country + priority -->
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-1.5">
        <span class="text-base">{{ flagEmoji }}</span>
        <span class="text-xs font-semibold text-gray-600 uppercase">{{ item.country_code }}</span>
        <span class="text-xs text-gray-400">{{ item.visa_type }}</span>
      </div>
      <AppBadge :color="priorityColor">{{ priorityLabel }}</AppBadge>
    </div>

    <!-- Client name -->
    <p class="text-sm font-medium text-gray-900 truncate mb-1">
      {{ item.client?.name ?? '—' }}
    </p>
    <p class="text-xs text-gray-400">{{ item.client?.phone }}</p>

    <!-- Deadline -->
    <div v-if="item.critical_date" :class="['flex items-center gap-1 mt-2 text-xs font-medium', urgencyText]">
      <span>{{ urgencyIcon }}</span>
      <span>{{ deadlineLabel }}</span>
    </div>

    <!-- Assignee -->
    <div v-if="item.assignee" class="flex items-center gap-1 mt-2">
      <div class="w-5 h-5 bg-blue-100 rounded-full flex items-center justify-center text-xs text-blue-600 font-bold">
        {{ item.assignee.name[0] }}
      </div>
      <span class="text-xs text-gray-400 truncate">{{ item.assignee.name }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import AppBadge from './AppBadge.vue';

const props = defineProps({ item: Object });
defineEmits(['click', 'move']);

const COUNTRY_FLAGS = {
  DE: '🇩🇪', FR: '🇫🇷', IT: '🇮🇹', ES: '🇪🇸', CZ: '🇨🇿', PL: '🇵🇱',
  US: '🇺🇸', GB: '🇬🇧', AE: '🇦🇪', TR: '🇹🇷', KR: '🇰🇷', CN: '🇨🇳',
  UZ: '🇺🇿', KZ: '🇰🇿', RU: '🇷🇺',
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
const urgencyBorder = computed(() => {
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
</script>

<template>
  <div :class="['bg-white rounded-xl border p-5', borderColor]">
    <div class="flex items-start justify-between">
      <div>
        <p class="text-sm text-gray-500">{{ title }}</p>
        <p :class="['text-3xl font-bold mt-1', textColor]">{{ value }}</p>
      </div>
      <span class="text-2xl">{{ icon }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
const props = defineProps({
  title: String, value: [Number, String], icon: String,
  color: { type: String, default: 'blue' },
});
const colors = {
  blue:   { border: 'border-blue-100',   text: 'text-blue-600' },
  red:    { border: 'border-red-100',    text: 'text-red-600' },
  yellow: { border: 'border-yellow-100', text: 'text-yellow-600' },
  gray:   { border: 'border-gray-200',   text: 'text-gray-700' },
  green:  { border: 'border-green-100',  text: 'text-green-600' },
};
const borderColor = computed(() => colors[props.color]?.border ?? 'border-gray-200');
const textColor   = computed(() => colors[props.color]?.text ?? 'text-gray-700');
</script>

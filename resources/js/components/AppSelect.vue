<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" :for="id" class="text-sm font-medium text-gray-700">{{ label }}</label>
    <select
      :id="id"
      :value="modelValue"
      @change="$emit('update:modelValue', $event.target.value)"
      :class="[
        'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors bg-white',
        'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
      ]"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option v-for="opt in options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
    </select>
  </div>
</template>

<script setup>
defineProps({
  modelValue:  { default: '' },
  options:     { type: Array, default: () => [] },
  label:       { type: String, default: '' },
  placeholder: { type: String, default: '' },
  id:          { type: String, default: () => `sel-${Math.random().toString(36).slice(2)}` },
});
defineEmits(['update:modelValue']);
</script>

<template>
  <div class="flex flex-col gap-1">
    <div class="flex items-center justify-between">
      <label v-if="label" :for="id" class="text-sm font-medium text-gray-700">
        {{ label }} <span v-if="required" class="text-red-500">*</span>
      </label>
      <span v-if="maxlength" :class="[
        'text-xs tabular-nums transition-colors',
        remaining <= 0 ? 'text-red-600 font-medium' :
        remaining <= maxlength * 0.1 ? 'text-orange-500' :
        'text-gray-400',
      ]">
        {{ charCount }} / {{ maxlength }}
      </span>
    </div>

    <input
      :id="id"
      v-bind="$attrs"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :required="required"
      :maxlength="maxlength || undefined"
      :class="[
        'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors',
        error
          ? 'border-red-300 focus:border-red-500 focus:ring-1 focus:ring-red-500'
          : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
      ]"
    />
    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-gray-400">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { default: '' },
  label:      { type: String,  default: '' },
  id:         { type: String,  default: () => `input-${Math.random().toString(36).slice(2)}` },
  error:      { type: String,  default: '' },
  hint:       { type: String,  default: '' },
  required:   { type: Boolean, default: false },
  maxlength:  { type: Number,  default: null },
});
defineEmits(['update:modelValue']);

const charCount = computed(() => String(props.modelValue || '').length);
const remaining = computed(() => props.maxlength ? props.maxlength - charCount.value : null);
</script>

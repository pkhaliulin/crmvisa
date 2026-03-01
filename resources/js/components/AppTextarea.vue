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

    <textarea
      :id="id"
      :value="modelValue"
      @input="onInput"
      :rows="rows"
      :maxlength="maxlength || undefined"
      :required="required"
      :placeholder="placeholder"
      :class="[
        'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors resize-none',
        error
          ? 'border-red-300 focus:border-red-500 focus:ring-1 focus:ring-red-500'
          : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
      ]"
    />

    <div class="flex items-start justify-between gap-2">
      <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
      <p v-else-if="hint" class="text-xs text-gray-400">{{ hint }}</p>
      <span v-else class="flex-1" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  label:      { type: String, default: '' },
  id:         { type: String, default: () => `textarea-${Math.random().toString(36).slice(2)}` },
  error:      { type: String, default: '' },
  hint:       { type: String, default: '' },
  required:   { type: Boolean, default: false },
  placeholder:{ type: String, default: '' },
  rows:       { type: Number, default: 4 },
  maxlength:  { type: Number, default: null },
});

const emit = defineEmits(['update:modelValue']);

function onInput(e) {
  emit('update:modelValue', e.target.value);
}

const charCount = computed(() => (props.modelValue || '').length);
const remaining = computed(() => props.maxlength ? props.maxlength - charCount.value : null);
</script>

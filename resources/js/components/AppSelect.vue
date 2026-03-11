<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" class="text-sm font-medium text-gray-700">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>
    <SearchSelect
      :modelValue="modelValue"
      @update:modelValue="$emit('update:modelValue', $event)"
      @change="$emit('change', $event)"
      :items="normalizedOptions"
      :placeholder="placeholder"
      :required="required"
      :compact="compact"
      :allow-all="!!placeholder"
      :all-label="placeholder"
      :disabled="disabled"
      :no-results-text="noResultsText"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SearchSelect from './SearchSelect.vue';

const props = defineProps({
  modelValue:    { default: '' },
  options:       { type: Array, default: () => [] },
  label:         { type: String, default: '' },
  placeholder:   { type: String, default: '' },
  required:      { type: Boolean, default: false },
  compact:       { type: Boolean, default: false },
  disabled:      { type: Boolean, default: false },
  noResultsText: { type: String, default: '' },
  id:            { type: String, default: '' },
});

defineEmits(['update:modelValue', 'change']);

const normalizedOptions = computed(() =>
  props.options.map(opt => ({
    value: opt.value ?? opt,
    label: opt.label ?? opt.text ?? String(opt.value ?? opt),
  }))
);
</script>

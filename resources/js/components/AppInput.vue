<template>
  <div class="flex flex-col gap-1">
    <label v-if="label" :for="id" class="text-sm font-medium text-gray-700">
      {{ label }} <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      :id="id"
      v-bind="$attrs"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      :required="required"
      :class="[
        'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors',
        error
          ? 'border-red-300 focus:border-red-500 focus:ring-1 focus:ring-red-500'
          : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
      ]"
    />
    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { default: '' },
  label:    { type: String, default: '' },
  id:       { type: String, default: () => `input-${Math.random().toString(36).slice(2)}` },
  error:    { type: String, default: '' },
  required: { type: Boolean, default: false },
});
defineEmits(['update:modelValue']);
</script>

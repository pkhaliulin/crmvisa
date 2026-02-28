<template>
  <button
    v-bind="$attrs"
    :disabled="loading || disabled"
    :class="[
      'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed',
      sizes[size],
      variants[variant],
    ]"
  >
    <span v-if="loading" class="animate-spin w-4 h-4 border-2 border-current border-t-transparent rounded-full" />
    <slot />
  </button>
</template>

<script setup>
defineProps({
  variant:  { type: String, default: 'primary' },
  size:     { type: String, default: 'md' },
  loading:  { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

const variants = {
  primary:  'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
  danger:   'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
  ghost:    'bg-transparent text-gray-600 hover:bg-gray-100 focus:ring-gray-400',
  outline:  'border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-400',
  success:  'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
};
const sizes = {
  sm: 'text-xs px-3 py-1.5',
  md: 'text-sm px-4 py-2',
  lg: 'text-base px-5 py-2.5',
};
</script>

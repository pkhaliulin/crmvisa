<template>
  <RouterLink :to="to" v-slot="{ isActive, isExactActive }" custom>
    <a @click="$router.push(to)"
      :class="[
        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors cursor-pointer',
        (exact ? isExactActive : isActive)
          ? 'bg-blue-600 text-white'
          : 'text-gray-300 hover:bg-gray-700/50 hover:text-white'
      ]"
    >
      <component :is="icon" class="w-5 h-5 shrink-0" />
      <span v-if="!collapsed" class="truncate">{{ label }}</span>
      <span v-if="badge && !collapsed"
        class="ml-auto bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
        {{ badge > 9 ? '9+' : badge }}
      </span>
    </a>
  </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router';
defineProps({
  to:       { type: Object, required: true },
  icon:     { required: true },
  label:    { type: String, required: true },
  collapsed:{ type: Boolean, default: false },
  badge:    { type: Number, default: 0 },
  exact:    { type: Boolean, default: false },
});
</script>

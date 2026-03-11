<template>
  <div class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto max-w-sm w-80 rounded-lg shadow-lg px-4 py-3 flex items-start gap-3 cursor-pointer"
        :class="bgClass(toast.type)"
        @click="removeToast(toast.id)"
      >
        <span class="text-sm font-medium leading-5">{{ toast.message }}</span>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '@/composables/useToast';

const { toasts, removeToast } = useToast();

function bgClass(type) {
  switch (type) {
    case 'success': return 'bg-green-600 text-white';
    case 'error':   return 'bg-red-600 text-white';
    case 'info':
    default:        return 'bg-gray-800 text-white';
  }
}
</script>

<style scoped>
.toast-enter-active {
  transition: all 0.3s ease-out;
}
.toast-leave-active {
  transition: all 0.2s ease-in;
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(60px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(60px);
}
</style>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <LogoBrand size="1.6rem" class="mx-auto mb-3" />
        <p class="text-gray-500 text-sm">{{ $t('landing.subtitle') }}</p>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import LogoBrand from '@/components/LogoBrand.vue';

const { t } = useI18n();
</script>

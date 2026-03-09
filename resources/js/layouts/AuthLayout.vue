<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img :src="logoUrl" :srcset="`${logoUrl} 1x, ${logoUrl2x} 2x`"
             alt="VisaBor" class="h-10 w-auto mx-auto mb-3">
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
import { useLogo } from '@/composables/useLogo';

const { t } = useI18n();
const { logoUrl, logoUrl2x } = useLogo();
</script>

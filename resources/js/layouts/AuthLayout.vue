<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="text-2xl font-extrabold tracking-tight mx-auto mb-3">
          <span class="text-[#0A1F44]">Visa</span><span class="text-[#1BA97F]">CRM</span>
        </div>
        <p class="text-gray-500 text-sm">Система управления визовыми заявками</p>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import logoUrl from '@/assets/logo.png';
import logoUrl2x from '@/assets/logo@2x.png';
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <img src="/images/logo.png"
             srcset="/images/logo@1x.png 1x, /images/logo@2x.png 2x"
             alt="VisaBor" class="h-10 w-auto mx-auto mb-3">
        <p class="text-gray-500 text-sm">Система управления визовыми заявками</p>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8">
        <slot />
      </div>
    </div>
  </div>
</template>

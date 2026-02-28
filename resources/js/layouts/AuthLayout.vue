<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-2xl mb-4 shadow-lg">
          <span class="text-white text-2xl font-bold">V</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">VisaBor CRM</h1>
        <p class="text-gray-500 text-sm mt-1">Система управления визовыми заявками</p>
      </div>

      <div class="bg-white rounded-2xl shadow-xl p-8">
        <slot />
      </div>
    </div>
  </div>
</template>

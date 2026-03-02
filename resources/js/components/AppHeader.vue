<template>
  <header class="bg-white border-b border-gray-200 h-16 flex items-center px-6 gap-4 shrink-0">
    <button @click="$emit('toggle-sidebar')" class="text-gray-400 hover:text-gray-600 transition-colors">
      <Bars3Icon class="w-5 h-5" />
    </button>

    <!-- Breadcrumb / Page title -->
    <div class="flex-1">
      <h2 class="text-sm font-semibold text-gray-700">{{ pageTitle }}</h2>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3">
      <!-- Overdue badge -->
      <RouterLink :to="{ name: 'cases', query: { status: 'overdue' } }"
        v-if="overdueCount > 0"
        class="flex items-center gap-1.5 bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors"
      >
        <ExclamationTriangleIcon class="w-4 h-4" />
        {{ overdueCount }} просроченных
      </RouterLink>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { Bars3Icon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { useCasesStore } from '@/stores/cases';

defineEmits(['toggle-sidebar']);

const route       = useRoute();
const casesStore  = useCasesStore();
const overdueCount = computed(() => casesStore.stats.overdue ?? 0);

const titles = {
  dashboard:      'Дашборд',
  kanban:         'Канбан-доска',
  cases:          'Заявки',
  'cases.create': 'Новая заявка',
  'cases.show':   'Заявка',
  clients:        'Клиенты',
  'clients.create': 'Новый клиент',
  'clients.show': 'Клиент',
  users:          'Сотрудники',
};
const pageTitle = computed(() => titles[route.name] ?? 'VisaBor CRM');
</script>

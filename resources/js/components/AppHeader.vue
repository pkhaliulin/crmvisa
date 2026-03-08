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
        {{ t('crm.header.overdueCount', { n: overdueCount }) }}
      </RouterLink>

      <!-- Language switcher -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
        <button v-for="loc in ['ru', 'uz']" :key="loc" @click="switchLocale(loc)"
          :class="['px-2 py-1 text-xs font-medium rounded-md transition-colors', currentLoc === loc ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          {{ loc.toUpperCase() }}
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Bars3Icon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
import { useCasesStore } from '@/stores/cases';
import { setLocale, currentLocale } from '@/i18n';

defineEmits(['toggle-sidebar']);

const { t } = useI18n();
const route       = useRoute();
const casesStore  = useCasesStore();
const overdueCount = computed(() => casesStore.stats.overdue ?? 0);

const currentLoc = computed(() => currentLocale());

function switchLocale(loc) {
  setLocale(loc);
}

const titles = computed(() => ({
  dashboard:      t('crm.nav.dashboard'),
  kanban:         t('crm.nav.kanbanBoard'),
  cases:          t('crm.nav.cases'),
  'cases.create': t('crm.nav.newCase'),
  'cases.show':   t('crm.nav.case'),
  clients:        t('crm.nav.clients'),
  'clients.create': t('crm.nav.newClient'),
  'clients.show': t('crm.nav.client'),
  overdue:        t('crm.nav.overdue'),
  countries:      t('crm.nav.countries'),
  'countries.show': t('crm.nav.countries'),
  reports:        t('crm.nav.reports'),
  services:       t('crm.nav.services'),
  'services.show': t('crm.nav.services'),
  billing:        t('crm.nav.billing'),
  settings:       t('crm.nav.settings'),
  users:          t('crm.nav.users'),
}));
const pageTitle = computed(() => titles.value[route.name] ?? 'VisaCRM');
</script>

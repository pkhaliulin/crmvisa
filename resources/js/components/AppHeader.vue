<template>
  <header class="bg-white border-b border-gray-200 h-16 flex items-center px-6 gap-4 shrink-0">
    <button @click="$emit('toggle-sidebar')" class="text-gray-400 hover:text-gray-600 transition-colors">
      <Bars3Icon class="w-5 h-5" />
    </button>

    <!-- Breadcrumb / Page title -->
    <div class="flex-1">
      <h1 class="text-xl font-bold text-gray-800">{{ pageTitle }}</h1>
    </div>

    <!-- Actions -->
    <div class="flex items-center gap-3">
      <!-- Notification bell -->
      <NotificationBell />

      <!-- Overdue badge -->
      <RouterLink :to="{ name: 'cases', query: { status: 'overdue' } }"
        v-if="overdueCount > 0"
        class="flex items-center gap-1.5 bg-red-50 text-red-600 px-3 py-1.5 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors"
      >
        <ExclamationTriangleIcon class="w-4 h-4" />
        {{ t('crm.header.overdueCount', { n: overdueCount }) }}
      </RouterLink>

      <!-- User name -->
      <span class="text-sm text-gray-600">{{ userName }}</span>

      <!-- Language switcher -->
      <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5">
        <button v-for="loc in ['ru', 'uz']" :key="loc" @click="switchLocale(loc)"
          :class="['px-2 py-1 text-xs font-medium rounded-md transition-colors', currentLoc === loc ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
          {{ loc.toUpperCase() }}
        </button>
      </div>

      <!-- Logout -->
      <button @click="handleLogout" class="text-sm text-gray-500 hover:text-red-500 transition-colors flex items-center gap-1">
        <ArrowRightOnRectangleIcon class="w-4 h-4" />
        <span class="hidden sm:inline">{{ t('logout') }}</span>
      </button>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { Bars3Icon, ExclamationTriangleIcon, ArrowRightOnRectangleIcon } from '@heroicons/vue/24/outline';
import NotificationBell from '@/components/NotificationBell.vue';
import { useCasesStore } from '@/stores/cases';
import { useAuthStore } from '@/stores/auth';
import { setLocale, currentLocale } from '@/i18n';

defineEmits(['toggle-sidebar']);

const { t } = useI18n();
const route       = useRoute();
const router      = useRouter();
const casesStore  = useCasesStore();
const auth        = useAuthStore();
const overdueCount = computed(() => casesStore.stats.overdue ?? 0);
const userName = computed(() => auth.user?.name ?? '');

const currentLoc = computed(() => currentLocale());

function switchLocale(loc) {
  setLocale(loc);
}

async function handleLogout() {
  await auth.logout();
  router.push({ name: 'login' });
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
  'service.detail': t('crm.nav.services'),
  billing:        t('crm.nav.billing'),
  settings:       t('crm.nav.settings'),
  users:          t('crm.nav.users'),
  'users.show':   t('crm.nav.users'),
  tasks:          t('crm.nav.tasks'),
  notifications:  t('crm.notifications.title'),
  knowledge:      t('crm.nav.knowledge'),
  leadgen:        t('crm.nav.leadgen'),
  'leadgen.detail':        t('crm.nav.leadgen'),
  'leadgen.analytics':     t('crm.nav.leadgen'),
  'leadgen.notifications': t('crm.nav.leadgen'),
}));
const pageTitle = computed(() => titles.value[route.name] ?? 'VisaCRM');
</script>

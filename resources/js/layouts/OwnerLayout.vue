<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- Сайдбар -->
    <aside class="w-60 shrink-0 bg-[#0A1F44] text-white flex flex-col min-h-screen sticky top-0 h-screen">
      <!-- Логотип -->
      <div class="px-5 py-5 border-b border-white/10">
        <div class="flex items-center gap-2">
          <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
            <path d="M2 8L10 20L14 14L18 20L26 8" stroke="#1BA97F" stroke-width="3.5"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <div>
            <div class="font-bold text-sm leading-none">VisaBor</div>
            <div class="text-[10px] text-white/40 mt-0.5 uppercase tracking-wider">Owner Panel</div>
          </div>
        </div>
      </div>

      <!-- Навигация -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <div v-for="group in navGroups" :key="group.title" class="mb-5">
          <div class="px-2 mb-1.5 text-[10px] font-semibold uppercase tracking-wider text-white/30">
            {{ group.title }}
          </div>
          <router-link
            v-for="item in group.items"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-white/60
                   hover:text-white hover:bg-white/10 transition-all"
            active-class="bg-white/15 text-white font-medium"
          >
            <span class="text-base w-5 text-center shrink-0">{{ item.icon }}</span>
            <span class="truncate">{{ item.label }}</span>
            <span v-if="item.badge" class="ml-auto text-xs bg-[#1BA97F] text-white
                                            px-1.5 py-0.5 rounded-full shrink-0">
              {{ item.badge }}
            </span>
          </router-link>
        </div>
      </nav>

      <!-- Низ — пользователь -->
      <div class="px-4 py-4 border-t border-white/10">
        <div class="flex items-center gap-2.5 mb-2">
          <div class="w-7 h-7 rounded-full bg-[#1BA97F]/20 flex items-center justify-center
                      text-xs font-bold text-[#1BA97F]">
            {{ auth.user?.name?.[0]?.toUpperCase() ?? 'S' }}
          </div>
          <div class="min-w-0">
            <div class="text-xs font-medium text-white/80 truncate">{{ auth.user?.name }}</div>
            <div class="text-[10px] text-white/30">Суперадмин</div>
          </div>
        </div>
        <button @click="logout"
          class="text-xs text-white/30 hover:text-white/70 transition-colors">
          Выйти из системы
        </button>
      </div>
    </aside>

    <!-- Контент -->
    <main class="flex-1 overflow-auto">
      <!-- Топ-бар -->
      <div class="sticky top-0 z-10 bg-white border-b border-gray-100 px-6 py-3.5
                  flex items-center justify-between shadow-sm">
        <div class="text-sm font-semibold text-gray-700">{{ currentPageTitle }}</div>
        <div class="text-xs text-gray-400">{{ today }}</div>
      </div>

      <div class="p-6">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth   = useAuthStore();
const route  = useRoute();
const router = useRouter();

const today = new Date().toLocaleDateString('ru-RU', { day: 'numeric', month: 'long', year: 'numeric' });

const navGroups = [
  {
    title: 'Аналитика',
    items: [
      { to: '/crm',          icon: '📊', label: 'Дашборд' },
    ],
  },
  {
    title: 'Экосистема',
    items: [
      { to: '/crm/agencies', icon: '🏢', label: 'Агентства' },
      { to: '/crm/crm-users',icon: '👔', label: 'CRM Пользователи' },
      { to: '/crm/users',    icon: '👤', label: 'Клиенты (портал)' },
      { to: '/crm/leads',    icon: '🎯', label: 'Лиды' },
    ],
  },
  {
    title: 'Настройки платформы',
    items: [
      { to: '/crm/countries', icon: '🌍', label: 'Страны и веса' },
      { to: '/crm/documents', icon: '📄', label: 'Документы' },
      { to: '/crm/services',  icon: '🛠', label: 'Каталог услуг' },
    ],
  },
  {
    title: 'Финансы',
    items: [
      { to: '/crm/finance',  icon: '💰', label: 'Транзакции' },
    ],
  },
];

const routeTitles = {
  '/crm':            'Главный дашборд',
  '/crm/agencies':   'Управление агентствами',
  '/crm/crm-users':  'CRM Пользователи',
  '/crm/users':      'Клиенты (публичный портал)',
  '/crm/leads':      'Лиды',
  '/crm/countries':  'Страны и веса скоринга',
  '/crm/documents':  'Справочник документов',
  '/crm/services':   'Каталог услуг',
  '/crm/finance':    'Финансовые транзакции',
};

const currentPageTitle = computed(() => routeTitles[route.path] ?? 'Owner Panel');

function logout() {
  auth.logout();
  router.push({ name: 'login' });
}
</script>

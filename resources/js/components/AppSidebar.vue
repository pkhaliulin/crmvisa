<template>
  <aside
    :class="[
      'bg-gray-900 text-white flex flex-col transition-all duration-300 shrink-0',
      collapsed ? 'w-16' : 'w-60'
    ]"
  >
    <!-- Logo -->
    <div class="flex items-center px-4 h-16 border-b border-gray-700/50">
      <!-- Иконка — всегда видна -->
      <svg width="26" height="26" viewBox="0 0 26 26" fill="none" class="shrink-0">
        <path d="M2 5L10 21L13 14L16 21L24 5" stroke="#1BA97F" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <!-- Текст — только в развёрнутом виде -->
      <span v-if="!collapsed" class="ml-2 font-bold text-sm text-white truncate">VisaBor</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 py-4 space-y-1 px-2 overflow-y-auto">
      <SidebarLink v-for="item in navItems" :key="item.name"
        :to="item.to" :icon="item.icon" :label="item.label"
        :collapsed="collapsed" :badge="item.badge" :exact="item.exact ?? false"
      />

      <div class="pt-3 mt-3 border-t border-gray-700/50">
        <SidebarLink v-if="isOwner"
          :to="{ name: 'users' }" :icon="UserGroupIcon"
          label="Сотрудники" :collapsed="collapsed"
        />
      </div>
    </nav>

    <!-- User -->
    <div class="p-3 border-t border-gray-700/50">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center shrink-0 text-xs font-bold">
          {{ userInitial }}
        </div>
        <div v-if="!collapsed" class="min-w-0">
          <p class="text-sm font-medium truncate">{{ user?.name }}</p>
          <p class="text-xs text-gray-400 capitalize">{{ user?.role }}</p>
        </div>
        <button v-if="!collapsed" @click="handleLogout"
          class="ml-auto text-gray-400 hover:text-red-400 transition-colors">
          <ArrowRightOnRectangleIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import SidebarLink from './SidebarLink.vue';
import {
  HomeIcon, ViewColumnsIcon, ClipboardDocumentListIcon,
  UsersIcon, UserGroupIcon, ArrowRightOnRectangleIcon,
  ChartBarIcon, BriefcaseIcon, ExclamationTriangleIcon, Cog6ToothIcon
} from '@heroicons/vue/24/outline';

defineProps({ collapsed: Boolean });
defineEmits(['toggle']);

const auth   = useAuthStore();
const router = useRouter();
const user   = computed(() => auth.user);
const isOwner = computed(() => auth.isOwner);
const userInitial = computed(() => user.value?.name?.[0]?.toUpperCase() ?? 'U');

const navItems = computed(() => {
  const items = [
    { to: { name: 'dashboard' }, icon: HomeIcon,                   label: 'Дашборд', exact: true },
    { to: { name: 'kanban' },    icon: ViewColumnsIcon,             label: 'Канбан' },
    { to: { name: 'cases' },     icon: ClipboardDocumentListIcon,   label: 'Заявки' },
    { to: { name: 'clients' },   icon: UsersIcon,                   label: 'Клиенты' },
    { to: { name: 'overdue' },   icon: ExclamationTriangleIcon,     label: 'Просрочки' },
  ];

  if (isOwner.value) {
    items.push(
      { to: { name: 'reports' },   icon: ChartBarIcon,   label: 'Отчёты' },
      { to: { name: 'services' },  icon: BriefcaseIcon,  label: 'Услуги' },
      { to: { name: 'settings' },  icon: Cog6ToothIcon,  label: 'Настройки' },
    );
  }

  return items;
});

async function handleLogout() {
  await auth.logout();
  router.push({ name: 'login' });
}
</script>

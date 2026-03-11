<template>
  <aside
    :class="[
      'bg-gray-900 text-white flex flex-col transition-all duration-300 shrink-0',
      collapsed ? 'w-16' : 'w-60'
    ]"
  >
    <!-- Logo -->
    <div class="flex items-center px-4 h-16 border-b border-gray-700/50">
      <span class="text-xl font-extrabold tracking-tight truncate"><span class="text-white">Visa</span><span class="text-blue-500">CRM</span></span>
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
          :label="t('crm.nav.users')" :collapsed="collapsed"
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
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import SidebarLink from './SidebarLink.vue';
import {
  HomeIcon, ViewColumnsIcon, ClipboardDocumentListIcon, ClipboardDocumentCheckIcon,
  UsersIcon, UserGroupIcon,
  ChartBarIcon, BriefcaseIcon, ExclamationTriangleIcon, Cog6ToothIcon, CreditCardIcon, GlobeAltIcon,
  MegaphoneIcon, BookOpenIcon
} from '@heroicons/vue/24/outline';

defineProps({ collapsed: Boolean });
defineEmits(['toggle']);

const { t }  = useI18n();
const auth   = useAuthStore();
const user   = computed(() => auth.user);
const isOwner = computed(() => auth.isOwner);
const userInitial = computed(() => user.value?.name?.[0]?.toUpperCase() ?? 'U');

const navItems = computed(() => {
  const items = [
    { to: { name: 'dashboard' }, icon: HomeIcon,                   label: t('crm.nav.dashboard'), exact: true },
    { to: { name: 'kanban' },    icon: ViewColumnsIcon,             label: t('crm.nav.kanban') },
    { to: { name: 'cases' },     icon: ClipboardDocumentListIcon,   label: t('crm.nav.cases') },
    { to: { name: 'clients' },   icon: UsersIcon,                   label: t('crm.nav.clients') },
    { to: { name: 'tasks' },     icon: ClipboardDocumentCheckIcon,  label: t('crm.nav.tasks') },
    { to: { name: 'overdue' },   icon: ExclamationTriangleIcon,     label: t('crm.nav.overdue') },
  ];

  // Страны — для owner и manager
  items.push(
    { to: { name: 'countries' }, icon: GlobeAltIcon, label: t('crm.nav.countries') },
    { to: { name: 'knowledge' }, icon: BookOpenIcon, label: t('crm.nav.knowledge') },
  );

  if (isOwner.value) {
    items.push(
      { to: { name: 'leadgen' },   icon: MegaphoneIcon,  label: t('crm.nav.leadgen') },
      { to: { name: 'reports' },   icon: ChartBarIcon,   label: t('crm.nav.reports') },
      { to: { name: 'services' },  icon: BriefcaseIcon,  label: t('crm.nav.services') },
      { to: { name: 'billing' },   icon: CreditCardIcon, label: t('crm.nav.billing') },
      { to: { name: 'settings' },  icon: Cog6ToothIcon,  label: t('crm.nav.settings') },
    );
  }

  return items;
});

</script>

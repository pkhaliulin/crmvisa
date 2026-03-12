<template>
  <div class="relative" ref="bellRef">
    <!-- Bell button -->
    <button @click="togglePanel" class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-gray-100">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
      </svg>
      <!-- Unread badge -->
      <span v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[10px] font-bold rounded-full px-1 leading-none">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown panel -->
    <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-1">
      <div v-if="showPanel" class="absolute right-0 top-full mt-2 w-96 bg-white rounded-xl shadow-xl border border-gray-200 z-50 max-h-[480px] flex flex-col">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0">
          <h3 class="text-sm font-bold text-gray-800">{{ t('crm.notifications.title') }}</h3>
          <button v-if="unreadCount > 0" @click="markAllRead"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">
            {{ t('crm.notifications.markAllRead') }}
          </button>
        </div>

        <!-- Notifications list -->
        <div class="overflow-y-auto flex-1">
          <div v-if="loading && !notifications.length" class="flex items-center justify-center py-8">
            <div class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></div>
          </div>

          <div v-else-if="notifications.length" class="divide-y divide-gray-50">
            <div v-for="n in notifications" :key="n.id"
              class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors flex items-start gap-3"
              :class="{ 'bg-blue-50/50': !n.read_at }"
              @click="handleClick(n)">
              <!-- Icon -->
              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="iconClass(n.data?.type)">
                <svg v-if="n.data?.type?.startsWith('case.')" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <svg v-else-if="n.data?.type?.startsWith('sla.')" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/>
                </svg>
                <svg v-else-if="n.data?.type?.startsWith('lead.')" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
              </div>

              <!-- Content -->
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-800 leading-snug" :class="{ 'font-medium': !n.read_at }">
                  {{ n.data?.message || n.data?.subject || t('crm.notifications.noNew') }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">{{ timeAgo(n.created_at) }}</p>
              </div>

              <!-- Unread dot -->
              <span v-if="!n.read_at" class="w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-2"></span>
            </div>
          </div>

          <div v-else class="py-8 text-center">
            <svg class="w-10 h-10 mx-auto text-gray-200 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
            <p class="text-sm text-gray-400">{{ t('crm.notifications.noNew') }}</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2.5 border-t border-gray-100 shrink-0">
          <RouterLink :to="{ name: 'notifications' }"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors"
            @click="showPanel = false">
            {{ t('crm.notifications.viewAll') }}
          </RouterLink>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { notificationsApi } from '@/api/cases';

const { t } = useI18n();
const router = useRouter();

const bellRef = ref(null);
const showPanel = ref(false);
const loading = ref(false);
const unreadCount = ref(0);
const notifications = ref([]);
let pollInterval = null;

function togglePanel() {
  showPanel.value = !showPanel.value;
  if (showPanel.value && !notifications.value.length) {
    fetchNotifications();
  }
}

async function fetchUnreadCount() {
  try {
    const { data } = await notificationsApi.unreadCount();
    unreadCount.value = data.data?.count ?? 0;
  } catch {
    // silently fail
  }
}

async function fetchNotifications() {
  loading.value = true;
  try {
    const { data } = await notificationsApi.list({ page: 1 });
    notifications.value = (data.data ?? []).slice(0, 10);
  } finally {
    loading.value = false;
  }
}

async function markAllRead() {
  try {
    await notificationsApi.markAllRead();
    unreadCount.value = 0;
    notifications.value.forEach(n => { n.read_at = new Date().toISOString(); });
  } catch {
    // silently fail
  }
}

async function handleClick(n) {
  // Mark as read
  if (!n.read_at) {
    try {
      await notificationsApi.markAsRead(n.id);
      n.read_at = new Date().toISOString();
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch {
      // silently fail
    }
  }

  // Navigate to relevant page
  const caseId = n.data?.case_id;
  if (caseId) {
    router.push({ name: 'cases.show', params: { id: caseId } });
  }

  showPanel.value = false;
}

function timeAgo(isoDate) {
  const now = Date.now();
  const date = new Date(isoDate).getTime();
  const diffMs = now - date;
  const diffMin = Math.floor(diffMs / 60000);

  if (diffMin < 1) return t('crm.notifications.justNow');
  if (diffMin < 60) return t('crm.notifications.minutesAgo', { n: diffMin });
  const diffHours = Math.floor(diffMin / 60);
  if (diffHours < 24) return t('crm.notifications.hoursAgo', { n: diffHours });
  const diffDays = Math.floor(diffHours / 24);
  return t('crm.notifications.daysAgo', { n: diffDays });
}

function iconClass(type) {
  if (!type) return 'bg-gray-100 text-gray-500';
  if (type.startsWith('sla.')) return 'bg-red-100 text-red-600';
  if (type.startsWith('lead.')) return 'bg-purple-100 text-purple-600';
  if (type === 'case.completed') return 'bg-green-100 text-green-600';
  if (type === 'case.rejected' || type === 'case.cancelled') return 'bg-red-100 text-red-600';
  return 'bg-blue-100 text-blue-600';
}

// Close on outside click
function onClickOutside(e) {
  if (bellRef.value && !bellRef.value.contains(e.target)) {
    showPanel.value = false;
  }
}

onMounted(() => {
  fetchUnreadCount();
  pollInterval = setInterval(fetchUnreadCount, 30000);
  document.addEventListener('click', onClickOutside);
});

onBeforeUnmount(() => {
  if (pollInterval) clearInterval(pollInterval);
  document.removeEventListener('click', onClickOutside);
});
</script>

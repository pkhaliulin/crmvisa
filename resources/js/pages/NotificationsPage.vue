<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-xl font-bold text-gray-800">{{ t('crm.notifications.title') }}</h1>
      <button v-if="notifications.length" @click="markAllRead"
        class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
        {{ t('crm.notifications.markAllRead') }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <div v-if="notifications.length" class="space-y-2">
        <div v-for="n in notifications" :key="n.id"
          class="bg-white rounded-xl border border-gray-200 px-5 py-4 flex items-start gap-4 cursor-pointer hover:border-blue-300 transition-colors"
          :class="{ 'bg-blue-50/50 border-blue-200': !n.read_at }"
          @click="handleClick(n)">

          <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" :class="iconClass(n.data?.type)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm text-gray-800" :class="{ 'font-semibold': !n.read_at }">
              {{ n.data?.message || n.data?.subject || '' }}
            </p>
            <div v-if="n.data?.details" class="mt-1 space-y-0.5">
              <p v-for="(d, i) in n.data.details" :key="i" class="text-xs text-gray-500">{{ d }}</p>
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ formatDate(n.created_at) }}</p>
          </div>

          <span v-if="!n.read_at" class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0 mt-2"></span>
        </div>
      </div>

      <div v-else class="bg-white rounded-xl border border-gray-200 py-20 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        <p class="text-sm">{{ t('crm.notifications.noNew') }}</p>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-1 py-2 text-sm text-gray-500">
        <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button :disabled="meta.current_page === 1" @click="fetchPage(meta.current_page - 1)"
            class="px-3 py-1 border border-gray-200 rounded-lg text-sm disabled:opacity-40 hover:bg-gray-50">
            &larr;
          </button>
          <button :disabled="meta.current_page === meta.last_page" @click="fetchPage(meta.current_page + 1)"
            class="px-3 py-1 border border-gray-200 rounded-lg text-sm disabled:opacity-40 hover:bg-gray-50">
            &rarr;
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { notificationsApi } from '@/api/cases';

const { t } = useI18n();
const router = useRouter();

const loading = ref(true);
const notifications = ref([]);
const meta = ref(null);

async function fetchPage(page = 1) {
  loading.value = true;
  try {
    const { data } = await notificationsApi.list({ page });
    notifications.value = data.data ?? [];
    meta.value = data.meta ?? null;
  } finally {
    loading.value = false;
  }
}

async function markAllRead() {
  try {
    await notificationsApi.markAllRead();
    notifications.value.forEach(n => { n.read_at = new Date().toISOString(); });
  } catch { /* */ }
}

async function handleClick(n) {
  if (!n.read_at) {
    try {
      await notificationsApi.markAsRead(n.id);
      n.read_at = new Date().toISOString();
    } catch { /* */ }
  }
  const caseId = n.data?.case_id;
  if (caseId) {
    router.push({ name: 'cases.show', params: { id: caseId } });
  }
}

function iconClass(type) {
  if (!type) return 'bg-gray-100 text-gray-500';
  if (type.startsWith('sla.')) return 'bg-red-100 text-red-600';
  if (type.startsWith('lead.')) return 'bg-purple-100 text-purple-600';
  if (type === 'case.completed') return 'bg-green-100 text-green-600';
  if (type === 'case.rejected' || type === 'case.cancelled') return 'bg-red-100 text-red-600';
  return 'bg-blue-100 text-blue-600';
}

function formatDate(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}

onMounted(() => fetchPage(1));
</script>

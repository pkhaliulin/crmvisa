<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-50">
      <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('me.case.timeline') }}</h2>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin w-5 h-5 border-2 border-[#1BA97F] border-t-transparent rounded-full"></div>
    </div>

    <div v-else-if="activities.length" class="px-5 py-4">
      <div class="relative">
        <!-- Timeline line -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-100"></div>

        <div v-for="(a, i) in activities" :key="a.id" class="relative pl-10 pb-6 last:pb-0">
          <!-- Dot -->
          <div class="absolute left-2.5 top-0.5 w-3 h-3 rounded-full border-2 border-white"
            :class="dotClass(a.type)"></div>

          <!-- Content -->
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <!-- Icon -->
              <div class="w-6 h-6 rounded-md flex items-center justify-center shrink-0" :class="iconBg(a.type)">
                <svg v-if="a.type === 'stage_change'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                <svg v-else-if="a.type === 'document_upload'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <svg v-else-if="a.type === 'document_approved'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else-if="a.type === 'payment'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <svg v-else-if="a.type === 'manager_assigned'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>

              <span class="text-sm font-medium text-[#0A1F44]">{{ localizedTitle(a) }}</span>
            </div>

            <p v-if="a.description" class="text-xs text-gray-500 mt-1 leading-relaxed">{{ a.description }}</p>

            <div class="flex items-center gap-2 mt-1.5 text-[10px] text-gray-400">
              <span>{{ formatDateTime(a.created_at) }}</span>
              <span v-if="a.user?.name" class="text-gray-300"> -- {{ a.user.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="px-5 py-6 text-center">
      <p class="text-sm text-gray-400">{{ $t('me.case.noActivities') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  caseId: { type: String, required: true },
  fetchFn: { type: Function, required: true },
});

const { t } = useI18n();
const loading = ref(true);
const activities = ref([]);

function dotClass(type) {
  switch (type) {
    case 'stage_change':      return 'bg-green-500';
    case 'document_upload':   return 'bg-blue-500';
    case 'document_approved': return 'bg-blue-600';
    case 'payment':           return 'bg-amber-500';
    case 'manager_assigned':  return 'bg-purple-500';
    default:                  return 'bg-gray-400';
  }
}

function iconBg(type) {
  switch (type) {
    case 'stage_change':      return 'bg-green-100 text-green-600';
    case 'document_upload':   return 'bg-blue-100 text-blue-600';
    case 'document_approved': return 'bg-blue-100 text-blue-700';
    case 'payment':           return 'bg-amber-100 text-amber-600';
    case 'manager_assigned':  return 'bg-purple-100 text-purple-600';
    default:                  return 'bg-gray-100 text-gray-500';
  }
}

function localizedTitle(a) {
  const typeMap = {
    stage_change:      t('me.case.stageChanged'),
    document_upload:   t('me.case.documentUploaded'),
    document_approved: t('me.case.documentApproved'),
    payment:           t('me.case.paymentReceived'),
    manager_assigned:  t('me.case.managerAssigned'),
    message:           t('me.case.message'),
    note:              t('me.case.note'),
  };
  return typeMap[a.type] || a.type;
}

function formatDateTime(iso) {
  if (!iso) return '';
  const d = new Date(iso);
  return d.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' });
}

onMounted(async () => {
  try {
    const { data } = await props.fetchFn(props.caseId);
    activities.value = data.data ?? [];
  } catch {
    // silently fail
  } finally {
    loading.value = false;
  }
});
</script>

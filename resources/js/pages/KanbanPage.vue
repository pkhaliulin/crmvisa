<template>
  <div class="flex flex-col h-full -m-6">
    <!-- Toolbar -->
    <div class="px-6 py-4 bg-white border-b flex flex-wrap items-center gap-4">
      <div class="flex gap-4 text-sm">
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
          {{ t('crm.kanbanPage.overdueLabel') }} <strong>{{ casesStore.stats.overdue }}</strong>
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
          {{ t('crm.kanbanPage.criticalLabel') }} <strong>{{ casesStore.stats.critical }}</strong>
        </span>
        <span class="flex items-center gap-1.5">
          <span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span>
          {{ t('crm.kanbanPage.totalLabel') }} <strong>{{ casesStore.stats.total }}</strong>
        </span>
        <span v-if="unassignedCount" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-semibold">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
          </svg>
          {{ t('crm.kanbanPage.unassignedLabel') }} {{ unassignedCount }}
        </span>
        <span v-if="awaitingPaymentCount" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">
          {{ t('crm.kanbanPage.awaitingPaymentLabel') }} {{ awaitingPaymentCount }}
        </span>
      </div>
      <!-- Поиск по номеру заявки / клиенту -->
      <div class="relative ml-4">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="searchQuery" type="text"
          class="pl-8 pr-8 py-1.5 w-56 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
          :placeholder="t('crm.kanbanPage.searchPlaceholder')" />
        <button v-if="searchQuery" @click="searchQuery = ''"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Фильтр по стране -->
      <CountrySelect
        v-model="filters.country_code"
        :countries="availableCountries"
        :placeholder="t('crm.kanbanPage.allCountries')"
        allow-all
        :all-label="t('crm.kanbanPage.allCountries')"
        compact
      />

      <!-- Фильтр: только просроченные -->
      <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer select-none">
        <input v-model="filters.overdue_only" type="checkbox"
          class="rounded border-gray-300 text-red-500 focus:ring-red-400" />
        {{ t('crm.kanbanPage.overdueOnly') }}
      </label>

      <!-- Фильтр по менеджеру (только для owner) -->
      <SearchSelect
        v-if="isOwner"
        v-model="filters.assigned_to"
        :items="managerOptions"
        :placeholder="t('crm.kanbanPage.allManagers')"
        allow-all
        :all-label="t('crm.kanbanPage.allManagers')"
        compact
        show-initials
        :no-results-text="t('crm.kanbanPage.noResults')"
      />

      <!-- Сброс фильтров -->
      <button v-if="hasActiveFilters" @click="resetFilters"
        class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{ t('crm.kanbanPage.resetFilters') }}
      </button>

      <div class="ml-auto">
        <RouterLink :to="{ name: 'cases.create' }">
          <AppButton size="sm">{{ t('crm.kanbanPage.newCase') }}</AppButton>
        </RouterLink>
      </div>
    </div>

    <!-- Board -->
    <div v-if="casesStore.loading" class="flex-1 flex items-center justify-center">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <div v-else class="flex-1 overflow-x-auto">
      <div class="flex gap-3 p-6 h-full" style="min-width: max-content">
        <KanbanColumn
          v-for="col in boardWithMeta"
          :key="col.key"
          :column="col"
          @move="handleMove"
          @open="openCase"
          @assign="handleAssign"
        />
      </div>
    </div>
  </div>

  <!-- Error toast -->
  <Transition name="toast">
    <div v-if="moveError" class="fixed top-4 right-4 z-50 bg-red-600 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg max-w-sm">
      {{ moveError }}
    </div>
  </Transition>

  <!-- Move stage modal -->
  <AppModal v-model="moveModal.show" :title="t('crm.kanbanPage.moveTitle')">
    <div class="space-y-4">
      <p class="text-sm text-gray-600">
        {{ t('crm.kanbanPage.moveDesc') }}
      </p>
      <AppSelect v-model="moveModal.stage" :options="filteredStageOptions" :placeholder="t('crm.kanbanPage.selectStage')" />
      <div v-if="moveModal.stage" class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
        {{ STAGES.find(s => s.value === moveModal.stage)?.tooltip }}
      </div>
      <AppInput v-model="moveModal.notes" :label="t('crm.kanbanPage.commentOptional')" :placeholder="t('crm.kanbanPage.commentPlaceholder')" />
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" @click="moveModal.show = false">{{ t('crm.kanbanPage.cancel') }}</AppButton>
        <AppButton :loading="moveModal.loading" @click="confirmMove">{{ t('crm.kanbanPage.moveBtn') }}</AppButton>
      </div>
    </div>
  </AppModal>
</template>

<script setup>
import { ref, reactive, computed, onMounted, provide } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useCasesStore } from '@/stores/cases';
import { useAuthStore } from '@/stores/auth';
import { useCountries } from '@/composables/useCountries';
import KanbanColumn from '@/components/KanbanColumn.vue';
import CountrySelect from '@/components/CountrySelect.vue';
import SearchSelect from '@/components/SearchSelect.vue';
import AppButton from '@/components/AppButton.vue';
import AppModal from '@/components/AppModal.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppInput from '@/components/AppInput.vue';
import api from '@/api/index';

const { t } = useI18n();

const STAGE_KEYS = ['lead', 'qualification', 'documents', 'doc_review', 'translation', 'ready', 'review', 'result'];
const STAGE_ICONS = { lead: '📥', qualification: '🔍', documents: '📋', doc_review: '🔎', translation: '📝', ready: '📦', review: '⏳', result: '✅' };
const STAGE_SLA   = { lead: 1, qualification: 3, documents: 72, doc_review: 24, translation: 48, ready: 24, review: null, result: 4 };

const STAGES = computed(() => STAGE_KEYS.map(key => ({
  value: key,
  label: t(`crm.stages.${key}`),
  icon: STAGE_ICONS[key],
  sla_hours: STAGE_SLA[key],
  tooltip: t(`crm.kanbanStageInfo.${key}_desc`),
  goal: t(`crm.kanbanStageInfo.${key}_goal`),
  result: t(`crm.kanbanStageInfo.${key}_done`),
})));

const casesStore = useCasesStore();
const router     = useRouter();
const auth       = useAuthStore();
const isOwner    = computed(() => auth.isOwner);
const { countries: availableCountries } = useCountries();
const moveError  = ref('');

// Единый источник правды — правила переходов приходят с бэкенда
const ALLOWED_TRANSITIONS = computed(() => casesStore.allowedTransitions);
provide('allowedTransitions', ALLOWED_TRANSITIONS);

const searchQuery = ref('');
const filters = reactive({
  country_code: '',
  overdue_only: false,
  assigned_to: '',
});
const managers = ref([]);
provide('kanbanManagers', managers);

const managerOptions = computed(() => {
  const opts = [
    { value: 'unassigned', label: t('crm.kanbanPage.noManager'), badge: String(unassignedCount.value || ''), badgeClass: 'bg-amber-100 text-amber-700' },
  ];
  for (const m of managers.value) {
    opts.push({ value: m.id, label: m.name, avatar: m.avatar_url ?? null });
  }
  return opts;
});

const hasActiveFilters = computed(() =>
  filters.country_code || filters.overdue_only || filters.assigned_to
);

function resetFilters() {
  filters.country_code = '';
  filters.overdue_only = false;
  filters.assigned_to = '';
}

async function loadManagers() {
  try {
    const { data } = await api.get('/users');
    managers.value = (data.data ?? data ?? []).filter(u => u.is_active !== false);
  } catch { /* ignore */ }
}

async function handleAssign({ caseId, managerId }) {
  try {
    await api.patch(`/cases/${caseId}`, { assigned_to: managerId });
    await casesStore.fetchKanban();
  } catch { /* ignore */ }
}

// Обогащаем колонки метаданными + фильтрация по поисковому запросу и фильтрам
const boardWithMeta = computed(() => {
  if (!casesStore.board) return [];
  const q = searchQuery.value.trim().toLowerCase();
  return casesStore.board.map(col => {
    const meta = STAGES.value.find(s => s.value === col.key) ?? {};
    let filtered = col.cases;

    // Текстовый поиск
    if (q) {
      filtered = filtered.filter(c =>
        (c.case_number && c.case_number.toLowerCase().includes(q)) ||
        (c.client?.name && c.client.name.toLowerCase().includes(q)) ||
        (c.client?.phone && c.client.phone.includes(q))
      );
    }

    // Фильтр по стране
    if (filters.country_code) {
      filtered = filtered.filter(c => c.country_code === filters.country_code);
    }

    // Фильтр по просроченным (SLA или дедлайн)
    if (filters.overdue_only) {
      filtered = filtered.filter(c => c.stage_sla_overdue || c.urgency === 'overdue');
    }

    // Фильтр по менеджеру
    if (filters.assigned_to) {
      if (filters.assigned_to === 'unassigned') {
        filtered = filtered.filter(c => !c.assignee);
      } else {
        filtered = filtered.filter(c => c.assignee?.id === filters.assigned_to);
      }
    }

    return {
      ...col,
      cases: filtered,
      count: filtered.length,
      label: meta.label ?? col.label ?? col.key,
      icon: meta.icon ?? '',
      tooltip: col.tooltip || meta.tooltip || '',
      sla_hours: col.sla_hours ?? meta.sla_hours ?? null,
    };
  });
});

const unassignedCount = computed(() => {
  if (!casesStore.board) return 0;
  return casesStore.board.reduce((sum, col) => sum + col.cases.filter(c => !c.assignee).length, 0);
});

const awaitingPaymentCount = computed(() => {
  if (!casesStore.board) return 0;
  return casesStore.board.reduce((sum, col) => sum + col.cases.filter(c => c.public_status === 'awaiting_payment').length, 0);
});

const moveModal = reactive({
  show: false, caseId: null, fromStage: '', stage: '', notes: '', loading: false,
});

const filteredStageOptions = computed(() => {
  const allowed = ALLOWED_TRANSITIONS[moveModal.fromStage] || [];
  return STAGES.value
    .filter(s => allowed.includes(s.value))
    .map(s => ({ value: s.value, label: `${s.icon} ${s.label}` }));
});

onMounted(() => {
  casesStore.fetchKanban();
  loadManagers();
});

function findCaseById(caseId) {
  if (!casesStore.board) return null;
  for (const col of casesStore.board) {
    const found = col.cases.find(c => c.id === caseId);
    if (found) return found;
  }
  return null;
}

function handleMove({ caseId, stage, fromStage }) {
  // Определяем текущий stage карточки
  const currentStage = fromStage || findCaseStage(caseId);
  if (!currentStage) return;

  // Проверяем назначен ли менеджер — без него нельзя двигать заявку
  const caseItem = findCaseById(caseId);
  if (caseItem && !caseItem.assignee) {
    moveError.value = t('crm.kanbanPage.noManagerError');
    setTimeout(() => { moveError.value = ''; }, 5000);
    casesStore.fetchKanban();
    return;
  }

  // Если stage указан и он недопустим — показать ошибку и обновить канбан
  const allowed = ALLOWED_TRANSITIONS[currentStage] || [];
  if (stage && !allowed.includes(stage)) {
    const fromLabel = STAGES.value.find(s => s.value === currentStage)?.label ?? currentStage;
    const toLabel = STAGES.value.find(s => s.value === stage)?.label ?? stage;
    moveError.value = t('crm.kanbanPage.transitionError', { from: fromLabel, to: toLabel });
    setTimeout(() => { moveError.value = ''; }, 3000);
    casesStore.fetchKanban(); // вернуть карточку
    return;
  }

  moveModal.caseId    = caseId;
  moveModal.fromStage = currentStage;
  moveModal.stage     = stage || '';
  moveModal.notes     = '';
  moveModal.show      = true;
}

function findCaseStage(caseId) {
  if (!casesStore.board) return null;
  for (const col of casesStore.board) {
    if (col.cases.some(c => c.id === caseId)) return col.key;
  }
  return null;
}

async function confirmMove() {
  if (!moveModal.stage) return;
  moveModal.loading = true;
  try {
    await casesStore.moveStage(moveModal.caseId, moveModal.stage, moveModal.notes || null);
    moveModal.show = false;
    moveError.value = '';
  } catch (err) {
    const msg = err.response?.data?.errors?.assigned_to?.[0] || err.response?.data?.errors?.stage?.[0] || err.response?.data?.message || t('crm.kanbanPage.moveError');
    moveError.value = msg;
    setTimeout(() => { moveError.value = ''; }, 4000);
    moveModal.show = false;
    await casesStore.fetchKanban();
  } finally {
    moveModal.loading = false;
  }
}

function openCase(id) {
  router.push({ name: 'cases.show', params: { id } });
}
</script>

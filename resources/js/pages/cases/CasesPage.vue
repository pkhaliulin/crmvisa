<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-3">
      <!-- Поиск по номеру / имени клиента -->
      <div class="relative">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="filters.q" type="text"
          class="pl-8 pr-8 py-2 w-60 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
          :placeholder="t('crm.casesPage.searchPlaceholder')"
          @input="debouncedFetch" />
        <button v-if="filters.q" @click="filters.q = ''; fetchCases()"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <!-- Фильтры -->
      <AppSelect v-model="filters.stage" :options="stageOptions" :placeholder="t('crm.casesPage.stage')" @change="fetchCases" />
      <AppSelect v-model="filters.priority" :options="priorityOptions" :placeholder="t('crm.casesPage.priorityFilter')" @change="fetchCases" />

      <!-- Страна -->
      <CountrySelect
        v-model="filters.country_code"
        :countries="availableCountries"
        :placeholder="t('crm.casesPage.allCountries')"
        allow-all
        :all-label="t('crm.casesPage.allCountries')"
        compact
        @change="fetchCases"
      />

      <!-- Менеджер -->
      <select v-model="filters.assigned_to" @change="fetchCases"
        class="border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 text-gray-700 bg-white">
        <option value="">{{ t('crm.casesPage.allManagers') }}</option>
        <option value="unassigned" class="font-medium text-amber-700">{{ t('crm.casesPage.noManager') }}</option>
        <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
      </select>

      <!-- Дата -->
      <div class="flex items-center gap-1.5">
        <input v-model="filters.date_from" type="date"
          class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm outline-none focus:border-blue-400 text-gray-600"
          @change="fetchCases" />
        <span class="text-gray-300 text-xs">--</span>
        <input v-model="filters.date_to" type="date"
          class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm outline-none focus:border-blue-400 text-gray-600"
          @change="fetchCases" />
      </div>

      <!-- Сброс фильтров -->
      <button v-if="hasActiveFilters" @click="resetFilters"
        class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{ t('crm.casesPage.reset') }}
      </button>

      <!-- Счётчик без менеджера -->
      <button v-if="unassignedCount > 0 && !filters.assigned_to"
        class="flex items-center gap-1.5 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-3 py-1.5 hover:bg-amber-100 transition-colors"
        @click="filters.assigned_to = 'unassigned'; fetchCases()">
        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
        {{ t('crm.casesPage.unassignedCount') }} {{ unassignedCount }}
      </button>

      <div class="ml-auto">
        <RouterLink :to="{ name: 'cases.create' }">
          <AppButton>{{ t('crm.casesPage.newCase') }}</AppButton>
        </RouterLink>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Cards list -->
      <div v-if="cases.length" class="space-y-3">
        <div v-for="c in cases" :key="c.id"
          class="group bg-white rounded-xl border border-gray-200 overflow-hidden
                 hover:border-blue-300 hover:shadow-md transition-all cursor-pointer
                 border-l-4"
          :class="urgencyBorder(c)"
          @click="$router.push({ name: 'cases.show', params: { id: c.id } })">

          <!-- Top: country + client + priority -->
          <div class="px-5 pt-4 pb-3 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
              <span class="text-3xl leading-none shrink-0">{{ countryFlag(c.country_code) }}</span>
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors text-base leading-tight truncate">
                  {{ countryName(c.country_code) }}
                  <span class="text-gray-400 font-normal"> -- {{ visaTypeName(c.visa_type) }}</span>
                </p>
                <p class="text-sm text-gray-500 mt-0.5 truncate">
                  <span v-if="c.case_number" class="font-mono text-gray-400 text-xs mr-2">{{ c.case_number }}</span>
                  <span class="font-medium text-gray-700">{{ c.client?.name }}</span>
                  <a v-if="c.client?.phone" :href="`tel:${c.client.phone}`" @click.stop class="text-gray-400 hover:text-blue-600"> · {{ formatPhone(c.client.phone) }}</a>
                </p>
              </div>
            </div>
            <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full leading-none"
              :class="priorityChip(c.priority)">
              {{ PRIORITY_LABELS[c.priority] ?? c.priority }}
            </span>
          </div>

          <!-- Bottom stats bar -->
          <div class="border-t border-gray-100 bg-gray-50/60 px-5 py-2.5 flex flex-wrap items-center gap-x-5 gap-y-1.5">

            <!-- Stage -->
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full shrink-0" :class="stageDot(c.stage)"></span>
              <span class="text-xs font-semibold text-gray-600">{{ stageLabel(c.stage) }}</span>
            </div>

            <!-- Deadline -->
            <div class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span v-if="c.critical_date" class="text-xs font-semibold" :class="deadlineText(c)">
                {{ formatDate(c.critical_date) }}
              </span>
              <span v-else class="text-xs text-gray-400">{{ t('crm.casesPage.deadlineNotSet') }}</span>
            </div>

            <!-- Manager — инлайн назначение -->
            <div class="flex items-center gap-1.5" @click.stop>
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>

              <!-- Режим выбора менеджера -->
              <template v-if="assigningId === c.id">
                <select
                  class="text-xs border border-blue-300 rounded-lg px-2 py-0.5 outline-none focus:border-blue-500 bg-white min-w-0"
                  autofocus
                  @change="assignManager(c, $event.target.value)"
                  @blur="assigningId = null"
                  @click.stop>
                  <option value="">{{ t('crm.casesPage.selectManager') }}</option>
                  <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }}</option>
                </select>
                <button class="text-gray-300 hover:text-gray-500 text-xs" @click.stop="assigningId = null">✕</button>
              </template>

              <!-- Назначен: имя + карандаш при ховере -->
              <template v-else-if="c.assignee">
                <span class="text-xs text-gray-600 font-medium">{{ c.assignee.name }}</span>
                <button v-if="isOwner"
                  class="opacity-0 group-hover:opacity-100 w-4 h-4 flex items-center justify-center rounded text-gray-300 hover:text-blue-500 transition-all"
                  :title="t('crm.casesPage.reassign')"
                  @click.stop="startAssign(c)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                  </svg>
                </button>
              </template>

              <!-- Не назначен: кнопка "Назначить" -->
              <template v-else>
                <button v-if="isOwner"
                  class="text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2.5 py-0.5 rounded-full transition-colors flex items-center gap-1"
                  @click.stop="startAssign(c)">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                  </svg>
                  {{ t('crm.casesPage.assignBtn') }}
                </button>
                <span v-else class="text-xs text-amber-600 font-medium">{{ t('crm.casesPage.noManagerLabel') }}</span>
              </template>
            </div>

            <!-- Docs progress -->
            <div class="ml-auto flex items-center gap-2">
              <svg class="w-3.5 h-3.5 shrink-0" :class="docsIconColor(c)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <template v-if="c.docs_total > 0">
                <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all" :class="docsBarColor(c)"
                    :style="{ width: Math.round(c.docs_uploaded / c.docs_total * 100) + '%' }"></div>
                </div>
                <span class="text-xs font-semibold tabular-nums" :class="docsTextColor(c)">
                  {{ c.docs_uploaded }}/{{ c.docs_total }}
                </span>
              </template>
              <span v-else class="text-xs text-gray-400">{{ t('crm.casesPage.noChecklist') }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-xl border border-gray-200 py-20 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">
          {{ filters.assigned_to === 'unassigned' ? t('crm.casesPage.emptyUnassigned') : t('crm.casesPage.emptyAll') }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-1 py-2 text-sm text-gray-500">
        <span>{{ t('crm.casesPage.pageOf', { current: meta.current_page, total: meta.last_page }) }}</span>
        <div class="flex gap-2">
          <AppButton variant="outline" size="sm" :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)">←</AppButton>
          <AppButton variant="outline" size="sm" :disabled="meta.current_page === meta.last_page" @click="changePage(meta.current_page + 1)">→</AppButton>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { RouterLink, useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { casesApi } from '@/api/cases';
import { usersApi } from '@/api/users';
import { useAuthStore } from '@/stores/auth';
import { useCountries } from '@/composables/useCountries';
import AppInput from '@/components/AppInput.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';
import CountrySelect from '@/components/CountrySelect.vue';
import { formatPhone, formatDate } from '@/utils/format';

const { t } = useI18n();
const router     = useRouter();
const auth       = useAuthStore();
const isOwner    = computed(() => auth.isOwner);
const { countries: availableCountries, countryName, countryFlag, visaTypeName } = useCountries();

const cases        = ref([]);
const meta         = ref(null);
const loading      = ref(false);
const managers     = ref([]);
const assigningId  = ref(null);
const filters      = reactive({ q: '', stage: '', priority: '', assigned_to: '', country_code: '', date_from: '', date_to: '', status: '', page: 1 });

const hasActiveFilters = computed(() =>
  filters.q || filters.stage || filters.priority || filters.assigned_to || filters.country_code || filters.date_from || filters.date_to || filters.status
);

function resetFilters() {
  Object.assign(filters, { q: '', stage: '', priority: '', assigned_to: '', country_code: '', date_from: '', date_to: '', status: '', page: 1 });
  fetchCases();
}

// Счётчик незакреплённых (из текущей страницы — для уведомления)
const unassignedCount = computed(() => cases.value.filter(c => !c.assignee).length);

const PRIORITY_LABELS = computed(() => ({
  low: t('crm.priority.low'),
  normal: t('crm.priority.normal'),
  high: t('crm.priority.high'),
  urgent: t('crm.priority.urgent'),
}));

const stageOptions = computed(() => [
  { value: 'lead',          label: t('crm.stages.lead') },
  { value: 'qualification', label: t('crm.stages.qualification') },
  { value: 'documents',     label: t('crm.stages.documents') },
  { value: 'doc_review',    label: t('crm.stages.doc_review') },
  { value: 'translation',   label: t('crm.stages.translation') },
  { value: 'ready',         label: t('crm.stages.ready') },
  { value: 'review',        label: t('crm.stages.review') },
  { value: 'result',        label: t('crm.stages.result') },
]);
const priorityOptions = computed(() => [
  { value: 'low',    label: t('crm.priority.low') },
  { value: 'normal', label: t('crm.priority.normal') },
  { value: 'high',   label: t('crm.priority.high') },
  { value: 'urgent', label: t('crm.priority.urgent') },
]);

const STAGE_LABELS = computed(() => ({
  lead: t('crm.stages.lead'),
  qualification: t('crm.stages.qualification'),
  documents: t('crm.stages.documents'),
  doc_review: t('crm.stages.doc_review'),
  translation: t('crm.stages.translation'),
  ready: t('crm.stages.ready'),
  review: t('crm.stages.review'),
  result: t('crm.stages.result'),
}));
const STAGE_DOTS = {
  lead: 'bg-gray-400', qualification: 'bg-blue-500', documents: 'bg-purple-500',
  doc_review: 'bg-cyan-500', translation: 'bg-yellow-500', ready: 'bg-orange-500',
  review: 'bg-indigo-500', result: 'bg-green-500',
};

const stageLabel = (s) => STAGE_LABELS.value[s] ?? s;
const stageDot   = (s) => STAGE_DOTS[s] ?? 'bg-gray-400';

function daysUntilDeadline(c) {
  if (!c.critical_date || c.stage === 'result') return null;
  return Math.floor((new Date(c.critical_date) - new Date()) / 86400000);
}

function urgencyBorder(c) {
  if (!c.assignee) return 'border-l-amber-400'; // без менеджера — жёлтый
  if (c.stage === 'result') return 'border-l-green-400';
  const d = daysUntilDeadline(c);
  if (d === null) return 'border-l-gray-200';
  if (d < 0)  return 'border-l-red-500';
  if (d <= 5) return 'border-l-yellow-400';
  return 'border-l-blue-300';
}

function deadlineText(c) {
  const d = daysUntilDeadline(c);
  if (d === null) return 'text-gray-400';
  if (d < 0)  return 'text-red-600';
  if (d <= 5) return 'text-yellow-600';
  return 'text-gray-600';
}

function priorityChip(p) {
  return {
    urgent: 'bg-red-100 text-red-700',
    high:   'bg-orange-100 text-orange-700',
    normal: 'bg-blue-100 text-blue-700',
    low:    'bg-gray-100 text-gray-500',
  }[p] ?? 'bg-gray-100 text-gray-500';
}

function docsBarColor(c)  { return c.docs_uploaded === c.docs_total ? 'bg-green-500' : 'bg-blue-500'; }
function docsIconColor(c) { return c.docs_uploaded === c.docs_total ? 'text-green-500' : 'text-gray-400'; }
function docsTextColor(c) { return c.docs_uploaded === c.docs_total ? 'text-green-600' : 'text-gray-600'; }


// ── Назначение менеджера ──────────────────────────────────────────────────────
function startAssign(c) {
  assigningId.value = c.id;
}

async function assignManager(c, managerId) {
  if (!managerId) { assigningId.value = null; return; }
  try {
    await casesApi.assign(c.id, managerId);
    const mgr = managers.value.find(m => m.id === managerId);
    // Обновляем карточку на месте без перезагрузки
    c.assignee = mgr ? { id: mgr.id, name: mgr.name } : null;
  } catch {
    // silently fail — user can retry
  } finally {
    assigningId.value = null;
  }
}

// ── Данные ────────────────────────────────────────────────────────────────────
let debounce;
function debouncedFetch() {
  clearTimeout(debounce);
  debounce = setTimeout(fetchCases, 350);
}

async function fetchCases() {
  loading.value = true;
  assigningId.value = null;
  try {
    const params = {};
    if (filters.q)            params.q            = filters.q;
    if (filters.stage)        params.stage        = filters.stage;
    if (filters.priority)     params.priority     = filters.priority;
    if (filters.assigned_to)  params.assigned_to  = filters.assigned_to;
    if (filters.country_code) params.country_code = filters.country_code;
    if (filters.date_from)    params.date_from    = filters.date_from;
    if (filters.date_to)      params.date_to      = filters.date_to;
    if (filters.status)       params.status       = filters.status;
    params.page = filters.page;
    const { data } = await casesApi.list(params);
    cases.value = data.data ?? [];
    meta.value  = data.meta ?? null;
  } finally {
    loading.value = false;
  }
}

function changePage(p) {
  filters.page = p;
  fetchCases();
}

onMounted(async () => {
  // Читаем query params из URL (для кликабельных счётчиков дашборда)
  const route = useRoute();
  const q = route.query;
  if (q.stage)       filters.stage       = q.stage;
  if (q.priority)    filters.priority    = q.priority;
  if (q.assigned_to) filters.assigned_to = q.assigned_to;
  if (q.country_code) filters.country_code = q.country_code;
  if (q.status)      filters.status      = q.status;
  if (q.q)           filters.q           = q.q;

  const [, uRes] = await Promise.all([
    fetchCases(),
    usersApi.list(),
  ]);
  managers.value = (uRes.data.data ?? []).filter(u => ['manager', 'owner'].includes(u.role));
});
</script>

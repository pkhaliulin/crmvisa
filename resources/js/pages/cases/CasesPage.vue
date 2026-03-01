<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-3">
      <AppInput v-model="filters.q" placeholder="Поиск по клиенту..." class="w-56" @input="debouncedFetch" />
      <AppSelect v-model="filters.stage" :options="stageOptions" placeholder="Этап" @change="fetchCases" />
      <AppSelect v-model="filters.priority" :options="priorityOptions" placeholder="Приоритет" @change="fetchCases" />
      <div class="ml-auto">
        <RouterLink :to="{ name: 'cases.create' }">
          <AppButton>+ Новая заявка</AppButton>
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
                  <span class="text-gray-400 font-normal"> — {{ visaTypeName(c.visa_type) }}</span>
                </p>
                <p class="text-sm text-gray-500 mt-0.5 truncate">
                  <span class="font-medium text-gray-700">{{ c.client?.name }}</span>
                  <span v-if="c.client?.phone" class="text-gray-400"> · {{ c.client.phone }}</span>
                </p>
              </div>
            </div>
            <!-- Priority chip -->
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
              <span v-else class="text-xs text-gray-400">Дедлайн не задан</span>
            </div>

            <!-- Manager -->
            <div class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span class="text-xs text-gray-500">{{ c.assignee?.name ?? 'Без менеджера' }}</span>
            </div>

            <!-- Docs progress -->
            <div class="ml-auto flex items-center gap-2">
              <svg class="w-3.5 h-3.5 shrink-0" :class="docsIconColor(c)" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <template v-if="c.docs_total > 0">
                <div class="w-20 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full rounded-full transition-all"
                    :class="docsBarColor(c)"
                    :style="{ width: Math.round(c.docs_uploaded / c.docs_total * 100) + '%' }">
                  </div>
                </div>
                <span class="text-xs font-semibold tabular-nums" :class="docsTextColor(c)">
                  {{ c.docs_uploaded }}/{{ c.docs_total }}
                </span>
              </template>
              <span v-else class="text-xs text-gray-400">нет чеклиста</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-xl border border-gray-200 py-20 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <p class="text-sm">Заявок не найдено</p>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-1 py-2 text-sm text-gray-500">
        <span>Страница {{ meta.current_page }} из {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <AppButton variant="outline" size="sm" :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)">←</AppButton>
          <AppButton variant="outline" size="sm" :disabled="meta.current_page === meta.last_page" @click="changePage(meta.current_page + 1)">→</AppButton>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { casesApi } from '@/api/cases';
import { useCountries } from '@/composables/useCountries';
import AppInput from '@/components/AppInput.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';

const router = useRouter();
const { countryName, countryFlag, visaTypeName } = useCountries();

const cases   = ref([]);
const meta    = ref(null);
const loading = ref(false);
const filters = reactive({ q: '', stage: '', priority: '', page: 1 });

const PRIORITY_LABELS = { low: 'Низкий', normal: 'Обычный', high: 'Высокий', urgent: 'Срочный' };

const stageOptions = [
  { value: 'lead',          label: 'Лид' },
  { value: 'qualification', label: 'Квалификация' },
  { value: 'documents',     label: 'Документы' },
  { value: 'translation',   label: 'Перевод' },
  { value: 'appointment',   label: 'Запись' },
  { value: 'review',        label: 'Рассмотрение' },
  { value: 'result',        label: 'Результат' },
];
const priorityOptions = [
  { value: 'low',    label: 'Низкий' },
  { value: 'normal', label: 'Обычный' },
  { value: 'high',   label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
];

const STAGE_LABELS = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'Рассмотрение', result: 'Результат',
};
const STAGE_DOTS = {
  lead: 'bg-gray-400', qualification: 'bg-blue-500', documents: 'bg-purple-500',
  translation: 'bg-yellow-500', appointment: 'bg-orange-500', review: 'bg-indigo-500', result: 'bg-green-500',
};

const stageLabel = (s) => STAGE_LABELS[s] ?? s;
const stageDot   = (s) => STAGE_DOTS[s] ?? 'bg-gray-400';

function daysUntilDeadline(c) {
  if (!c.critical_date || c.stage === 'result') return null;
  return Math.floor((new Date(c.critical_date) - new Date()) / 86400000);
}

function urgencyBorder(c) {
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
  const map = {
    urgent: 'bg-red-100 text-red-700',
    high:   'bg-orange-100 text-orange-700',
    normal: 'bg-blue-100 text-blue-700',
    low:    'bg-gray-100 text-gray-500',
  };
  return map[p] ?? 'bg-gray-100 text-gray-500';
}

function docsBarColor(c)  { return c.docs_uploaded === c.docs_total ? 'bg-green-500' : 'bg-blue-500'; }
function docsIconColor(c) { return c.docs_uploaded === c.docs_total ? 'text-green-500' : 'text-gray-400'; }
function docsTextColor(c) { return c.docs_uploaded === c.docs_total ? 'text-green-600' : 'text-gray-600'; }

function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

let debounce;
function debouncedFetch() {
  clearTimeout(debounce);
  debounce = setTimeout(fetchCases, 350);
}

async function fetchCases() {
  loading.value = true;
  try {
    const params = {};
    if (filters.q)        params.q        = filters.q;
    if (filters.stage)    params.stage    = filters.stage;
    if (filters.priority) params.priority = filters.priority;
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

onMounted(fetchCases);
</script>

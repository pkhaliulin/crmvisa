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

    <!-- Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <template v-else>
        <table class="w-full text-sm" v-if="cases.length">
          <thead class="bg-gray-50 border-b text-gray-500 text-xs uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">Клиент</th>
              <th class="text-left px-4 py-3">Страна / Виза</th>
              <th class="text-left px-4 py-3">Этап</th>
              <th class="text-left px-4 py-3">Дедлайн</th>
              <th class="text-left px-4 py-3">Менеджер</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="c in cases" :key="c.id"
              class="hover:bg-blue-50 transition-colors cursor-pointer group"
              @click="$router.push({ name: 'cases.show', params: { id: c.id } })">
              <td class="px-4 py-3">
                <p class="font-semibold text-blue-700 group-hover:underline">{{ c.client?.name }}</p>
                <p class="text-xs text-gray-400">{{ c.client?.phone }}</p>
              </td>
              <td class="px-4 py-3">
                <span class="text-base mr-1">{{ countryFlag(c.country_code) }}</span>
                <span class="font-medium">{{ countryName(c.country_code) }}</span>
                <span class="text-gray-400"> · {{ visaTypeName(c.visa_type) }}</span>
              </td>
              <td class="px-4 py-3">
                <AppBadge :color="stageColor(c.stage)">{{ stageLabel(c.stage) }}</AppBadge>
              </td>
              <td class="px-4 py-3">
                <span v-if="c.critical_date" :class="deadlineClass(c)">
                  {{ formatDate(c.critical_date) }}
                </span>
                <span v-else class="text-gray-300">—</span>
              </td>
              <td class="px-4 py-3 text-gray-500">
                {{ c.assignee?.name ?? '—' }}
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="py-20 text-center text-gray-400">
          <p class="text-4xl mb-3">📋</p>
          <p>Заявок не найдено</p>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t text-sm text-gray-500">
          <span>Страница {{ meta.current_page }} из {{ meta.last_page }}</span>
          <div class="flex gap-2">
            <AppButton variant="outline" size="sm" :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)">
              ←
            </AppButton>
            <AppButton variant="outline" size="sm" :disabled="meta.current_page === meta.last_page" @click="changePage(meta.current_page + 1)">
              →
            </AppButton>
          </div>
        </div>
      </template>
    </div>
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
import AppBadge from '@/components/AppBadge.vue';

const router = useRouter();
const { countryName, countryFlag, visaTypeName } = useCountries();

const cases   = ref([]);
const meta    = ref(null);
const loading = ref(false);
const filters = reactive({ q: '', stage: '', priority: '', page: 1 });

const stageOptions = [
  { value: 'lead', label: 'Лид' },
  { value: 'qualification', label: 'Квалификация' },
  { value: 'documents', label: 'Документы' },
  { value: 'translation', label: 'Перевод' },
  { value: 'appointment', label: 'Запись' },
  { value: 'review', label: 'Рассмотрение' },
  { value: 'result', label: 'Результат' },
];
const priorityOptions = [
  { value: 'low', label: 'Низкий' },
  { value: 'normal', label: 'Обычный' },
  { value: 'high', label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
];

const STAGE_LABELS = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'Рассмотрение', result: 'Результат',
};
const STAGE_COLORS = {
  lead: 'gray', qualification: 'blue', documents: 'purple',
  translation: 'yellow', appointment: 'orange', review: 'blue', result: 'green',
};

const stageLabel = (s) => STAGE_LABELS[s] ?? s;
const stageColor = (s) => STAGE_COLORS[s] ?? 'gray';

function deadlineClass(c) {
  if (!c.critical_date) return '';
  const d = new Date(c.critical_date);
  const now = new Date();
  const diff = Math.floor((d - now) / 86400000);
  if (diff < 0) return 'text-red-600 font-medium';
  if (diff <= 5) return 'text-yellow-600 font-medium';
  return 'text-gray-600';
}

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

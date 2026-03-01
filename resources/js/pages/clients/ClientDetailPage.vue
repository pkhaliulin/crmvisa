<template>
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="client" class="space-y-6 max-w-4xl">

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <!-- Title row with back button -->
      <div class="flex items-center gap-3 mb-4">
        <button @click="$router.back()"
            class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        <div class="flex-1 min-w-0">
          <h2 class="text-xl font-bold text-gray-900 truncate">{{ client.name }}</h2>
          <p class="text-sm text-gray-500">
            {{ client.phone }}<span v-if="client.email"> · {{ client.email }}</span>
          </p>
        </div>
        <RouterLink :to="{ name: 'clients.create', query: { edit: client.id } }">
          <AppButton variant="outline" size="sm">Редактировать</AppButton>
        </RouterLink>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-4 border-t">
        <div>
          <p class="text-xs text-gray-400 uppercase">Гражданство</p>
          <p class="text-sm font-medium mt-1">{{ client.nationality ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">Паспорт</p>
          <p class="text-sm font-medium mt-1">{{ client.passport_number ?? '—' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">Действует до</p>
          <p :class="['text-sm font-medium mt-1', passportClass]">
            {{ client.passport_expires_at ? formatDate(client.passport_expires_at) : '—' }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-400 uppercase">Источник</p>
          <AppBadge :color="sourceColor" class="mt-1">{{ sourceLabel }}</AppBadge>
        </div>
      </div>
    </div>

    <!-- Cases -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Заявки ({{ client.cases?.length ?? 0 }})</h3>
        <RouterLink :to="{ name: 'cases.create', query: { client_id: client.id, client_label: `${client.name} — ${client.phone}` } }">
          <AppButton size="sm">+ Новая заявка</AppButton>
        </RouterLink>
      </div>

      <div v-if="client.cases?.length" class="space-y-3">
        <div v-for="c in client.cases" :key="c.id"
          class="group border border-gray-200 rounded-xl overflow-hidden hover:border-blue-300 hover:shadow-sm transition-all cursor-pointer border-l-4"
          :class="urgencyBorder(c)"
          @click="$router.push({ name: 'cases.show', params: { id: c.id } })">

          <!-- Top row -->
          <div class="px-4 pt-3 pb-2 flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="text-xl leading-none shrink-0">{{ countryFlag(c.country_code) }}</span>
              <div class="min-w-0">
                <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors text-sm leading-tight truncate">
                  {{ countryName(c.country_code) }}
                  <span class="text-gray-400 font-normal"> — {{ visaTypeName(c.visa_type) }}</span>
                </p>
              </div>
            </div>
            <span class="shrink-0 text-xs font-bold px-2 py-0.5 rounded-full" :class="priorityChip(c.priority)">
              {{ PRIORITY_LABELS[c.priority] ?? c.priority }}
            </span>
          </div>

          <!-- Stats bar -->
          <div class="border-t border-gray-100 bg-gray-50/60 px-4 py-2 flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full shrink-0" :class="stageDot(c.stage)"></span>
              <span class="text-xs font-semibold text-gray-600">{{ STAGE_LABELS[c.stage] ?? c.stage }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <span v-if="c.critical_date" class="text-xs font-semibold" :class="deadlineText(c)">
                {{ formatDate(c.critical_date) }}
              </span>
              <span v-else class="text-xs text-gray-400">Без дедлайна</span>
            </div>
            <div class="flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span class="text-xs text-gray-500">{{ c.assignee?.name ?? 'Без менеджера' }}</span>
            </div>
          </div>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">Заявок нет</p>
    </div>

    <!-- Scoring -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-800">Скоринг</h3>
        <AppButton variant="outline" size="sm" :loading="recalcLoading" @click="recalculate">
          Пересчитать
        </AppButton>
      </div>

      <div v-if="scores.length" class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <div v-for="s in scores" :key="s.country_code"
          class="border rounded-lg p-3"
          :class="s.is_blocked ? 'border-red-200 bg-red-50' : 'border-gray-100'"
        >
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-semibold">{{ flags[s.country_code] ?? '' }} {{ s.country_code }}</span>
            <span :class="['text-lg font-bold', scoreColor(s.score)]">{{ s.score }}</span>
          </div>
          <div class="bg-gray-200 rounded-full h-1.5 overflow-hidden">
            <div :class="['h-full rounded-full', scoreBarColor(s.score)]" :style="{ width: `${s.score}%` }" />
          </div>
          <p class="text-xs text-gray-500 mt-1">{{ s.level_label }}</p>
        </div>
      </div>
      <p v-else class="text-sm text-gray-400">Скоринг не рассчитан. Нажмите «Пересчитать».</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { clientsApi } from '@/api/clients';
import { useCountries } from '@/composables/useCountries';
import AppBadge from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';

const { countryName, countryFlag, visaTypeName } = useCountries();

const route  = useRoute();
const id     = route.params.id;
const client = ref(null);
const scores = ref([]);
const loading = ref(true);
const recalcLoading = ref(false);

const STAGE_LABELS = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'Рассмотрение', result: 'Результат',
};

const PRIORITY_LABELS = { low: 'Низкий', normal: 'Обычный', high: 'Высокий', urgent: 'Срочный' };

const STAGE_DOTS = {
  lead: 'bg-gray-400', qualification: 'bg-blue-500', documents: 'bg-purple-500',
  translation: 'bg-yellow-500', appointment: 'bg-orange-500', review: 'bg-indigo-500', result: 'bg-green-500',
};
const stageDot = (s) => STAGE_DOTS[s] ?? 'bg-gray-400';

function daysUntil(c) {
  if (!c.critical_date || c.stage === 'result') return null;
  return Math.floor((new Date(c.critical_date) - new Date()) / 86400000);
}
function urgencyBorder(c) {
  if (c.stage === 'result') return 'border-l-green-400';
  const d = daysUntil(c);
  if (d === null) return 'border-l-gray-200';
  if (d < 0)  return 'border-l-red-500';
  if (d <= 5) return 'border-l-yellow-400';
  return 'border-l-blue-300';
}
function deadlineText(c) {
  const d = daysUntil(c);
  if (d === null) return 'text-gray-400';
  if (d < 0)  return 'text-red-600';
  if (d <= 5) return 'text-yellow-600';
  return 'text-gray-600';
}
function priorityChip(p) {
  return { urgent: 'bg-red-100 text-red-700', high: 'bg-orange-100 text-orange-700', normal: 'bg-blue-100 text-blue-700', low: 'bg-gray-100 text-gray-500' }[p] ?? 'bg-gray-100 text-gray-500';
}


const sourceMap = {
  direct: { label: 'Прямой', color: 'blue' },
  referral: { label: 'Реферал', color: 'purple' },
  marketplace: { label: 'Маркетплейс', color: 'green' },
  other: { label: 'Другое', color: 'gray' },
};
const sourceLabel = computed(() => sourceMap[client.value?.source]?.label ?? '');
const sourceColor = computed(() => sourceMap[client.value?.source]?.color ?? 'gray');

const passportClass = computed(() => {
  const d = client.value?.passport_expires_at;
  if (!d) return '';
  const days = Math.floor((new Date(d) - new Date()) / 86400000);
  return days < 0 ? 'text-red-600 font-bold' : days <= 90 ? 'text-yellow-600' : 'text-gray-700';
});

function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU');
}
function scoreColor(s) {
  if (s >= 80) return 'text-green-600';
  if (s >= 60) return 'text-yellow-600';
  return 'text-red-600';
}
function scoreBarColor(s) {
  if (s >= 80) return 'bg-green-500';
  if (s >= 60) return 'bg-yellow-400';
  return 'bg-red-400';
}

async function load() {
  loading.value = true;
  try {
    const [cRes, sRes] = await Promise.all([
      clientsApi.get(id),
      clientsApi.getScores(id),
    ]);
    client.value = cRes.data.data;
    scores.value = sRes.data.data ?? [];
  } finally {
    loading.value = false;
  }
}

async function recalculate() {
  recalcLoading.value = true;
  try {
    const { data } = await clientsApi.recalculate(id);
    scores.value = data.data ?? [];
  } finally {
    recalcLoading.value = false;
  }
}

onMounted(load);
</script>

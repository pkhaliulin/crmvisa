<template>
  <div v-if="loading" class="flex items-center justify-center py-32">
    <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
  </div>

  <div v-else-if="client" class="space-y-6 max-w-4xl">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-xl font-bold text-gray-900">{{ client.name }}</h2>
          <p class="text-sm text-gray-500 mt-1">
            {{ client.phone }} <span v-if="client.email">· {{ client.email }}</span>
          </p>
        </div>
        <div class="flex gap-2">
          <RouterLink :to="{ name: 'clients.create', query: { edit: client.id } }">
            <AppButton variant="outline" size="sm">Редактировать</AppButton>
          </RouterLink>
        </div>
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
        <RouterLink :to="{ name: 'cases.create' }">
          <AppButton size="sm">+ Новая заявка</AppButton>
        </RouterLink>
      </div>

      <div v-if="client.cases?.length" class="space-y-2">
        <RouterLink v-for="c in client.cases" :key="c.id"
          :to="{ name: 'cases.show', params: { id: c.id } }"
          class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 hover:bg-gray-50 transition-colors"
        >
          <span class="text-lg">{{ flags[c.country_code] ?? '🌍' }}</span>
          <div class="flex-1">
            <p class="text-sm font-medium">{{ c.country_code }} — {{ c.visa_type }}</p>
            <p class="text-xs text-gray-400">{{ STAGE_LABELS[c.stage] ?? c.stage }}</p>
          </div>
          <span class="text-xs text-gray-400">→</span>
        </RouterLink>
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
import AppBadge from '@/components/AppBadge.vue';
import AppButton from '@/components/AppButton.vue';

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
const flags = {
  DE: '🇩🇪', FR: '🇫🇷', IT: '🇮🇹', ES: '🇪🇸', CZ: '🇨🇿', PL: '🇵🇱',
  US: '🇺🇸', GB: '🇬🇧', AE: '🇦🇪', TR: '🇹🇷', KR: '🇰🇷', CN: '🇨🇳',
};

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

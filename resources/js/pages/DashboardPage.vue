<template>
  <div class="space-y-4">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
    <!-- Stats row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <StatCard title="Активных заявок" :value="stats?.cases?.total_active ?? 0" icon="📋" color="blue" />
      <StatCard title="Просроченных" :value="stats?.cases?.overdue ?? 0" icon="🔴" color="red" />
      <StatCard title="Горящих (≤5 дней)" :value="stats?.cases?.critical ?? 0" icon="🟡" color="yellow" />
      <StatCard title="Без ответственного" :value="stats?.cases?.unassigned ?? 0" icon="👤" color="gray" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Stages breakdown -->
      <div class="bg-white rounded-xl border border-gray-200 p-5 lg:col-span-2">
        <h3 class="font-semibold text-gray-800 mb-4">Заявки по этапам</h3>
        <div class="space-y-3">
          <div v-for="stage in stageRows" :key="stage.key" class="flex items-center gap-3">
            <span class="text-sm text-gray-500 w-40 shrink-0">{{ stage.label }}</span>
            <div class="flex-1 bg-gray-100 rounded-full h-2.5 overflow-hidden">
              <div
                class="bg-blue-500 h-full rounded-full transition-all duration-700"
                :style="{ width: `${stage.percent}%` }"
              />
            </div>
            <span class="text-sm font-semibold text-gray-700 w-6 text-right">{{ stage.count }}</span>
          </div>
        </div>
      </div>

      <!-- Quick stats -->
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-4">За последние 30 дней</h3>
        <div class="space-y-4">
          <div>
            <p class="text-3xl font-bold text-gray-900">{{ stats?.last_30_days?.total_created ?? 0 }}</p>
            <p class="text-sm text-gray-500">новых заявок</p>
          </div>
          <div>
            <p class="text-3xl font-bold text-green-600">{{ stats?.last_30_days?.completed ?? 0 }}</p>
            <p class="text-sm text-gray-500">завершено</p>
          </div>
          <div class="pt-2 border-t">
            <p class="text-2xl font-bold text-gray-900">{{ stats?.clients_total ?? 0 }}</p>
            <p class="text-sm text-gray-500">всего клиентов</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Manager workload -->
    <div v-if="stats?.managers?.length" class="bg-white rounded-xl border border-gray-200 p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Нагрузка менеджеров</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b text-gray-500 text-xs uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3 font-medium">Менеджер</th>
              <th class="text-right px-4 py-3 font-medium">Активных заявок</th>
              <th class="px-4 py-3 w-40"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="m in stats.managers" :key="m.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-2.5 font-medium text-gray-800">{{ m.name }}</td>
              <td class="px-4 py-2.5 text-right font-bold text-gray-900">{{ m.active_cases }}</td>
              <td class="px-4 py-2.5">
                <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                  <div
                    class="bg-indigo-500 h-full rounded-full"
                    :style="{ width: `${Math.min(100, (m.active_cases / maxManagerLoad) * 100)}%` }"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { dashboardApi } from '@/api/dashboard';
import StatCard from '@/components/StatCard.vue';

const loading = ref(true);

const STAGES = [
  { key: 'lead',          label: 'Лид' },
  { key: 'qualification', label: 'Квалификация' },
  { key: 'documents',     label: 'Документы' },
  { key: 'translation',   label: 'Перевод' },
  { key: 'appointment',   label: 'Запись' },
  { key: 'review',        label: 'Рассмотрение' },
  { key: 'result',        label: 'Результат' },
];

const stats = ref(null);

const stageRows = computed(() => {
  if (!stats.value?.cases?.by_stage) return [];
  const byStage  = stats.value.cases.by_stage;
  const maxCount = Math.max(1, ...Object.values(byStage).map(Number));
  return STAGES.map(s => ({
    ...s,
    count:   Number(byStage[s.key] ?? 0),
    percent: Math.round((Number(byStage[s.key] ?? 0) / maxCount) * 100),
  }));
});

const maxManagerLoad = computed(() =>
  Math.max(1, ...(stats.value?.managers ?? []).map(m => m.active_cases))
);

onMounted(async () => {
  try {
    const { data } = await dashboardApi.index();
    stats.value = data.data;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Просроченные заявки</h1>
      <p class="text-sm text-gray-500 mt-1">Заявки с истёкшим дедлайном</p>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <div v-else-if="cases.length === 0"
      class="text-center py-16 bg-white rounded-xl border border-gray-200">
      <p class="text-2xl mb-2">✓</p>
      <p class="text-gray-600 font-medium">Просроченных заявок нет</p>
      <p class="text-sm text-gray-400 mt-1">Все заявки в срок</p>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">
          {{ cases.length }} {{ cases.length === 1 ? 'заявка' : cases.length < 5 ? 'заявки' : 'заявок' }}
        </span>
      </div>

      <div class="divide-y divide-gray-100">
        <div v-for="c in cases" :key="c.id"
          :class="['px-4 py-4 hover:bg-gray-50 transition-colors',
            c.severity === 'critical' ? 'border-l-4 border-red-500' : 'border-l-4 border-yellow-400']">
          <div class="flex items-center justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-3">
                <span :class="['text-xs font-bold px-2 py-0.5 rounded-full',
                  c.severity === 'critical'
                    ? 'bg-red-100 text-red-700'
                    : 'bg-yellow-100 text-yellow-700']">
                  {{ c.severity === 'critical' ? 'Критично' : 'Предупреждение' }}
                </span>
                <span class="text-sm font-medium text-gray-900">
                  {{ c.client?.name }}
                </span>
                <span class="text-xs text-gray-400">{{ c.country_code }} / {{ c.visa_type }}</span>
              </div>

              <div class="flex items-center gap-4 mt-1.5 text-xs text-gray-500">
                <span>Этап: <span class="font-medium text-gray-700">{{ stageLabel(c.stage) }}</span></span>
                <span v-if="c.assignee">Менеджер: <span class="font-medium text-gray-700">{{ c.assignee.name }}</span></span>
                <span>Дедлайн: <span class="font-medium text-gray-700">{{ formatDate(c.critical_date) }}</span></span>
              </div>
            </div>

            <div class="text-right shrink-0 ml-4">
              <div :class="['text-2xl font-bold',
                c.severity === 'critical' ? 'text-red-600' : 'text-yellow-600']">
                +{{ c.days_overdue }}
              </div>
              <div class="text-xs text-gray-400">дней</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const loading = ref(true);
const cases = ref([]);

const stageLabels = {
  lead: 'Лид', qualification: 'Квалификация', documents: 'Документы',
  translation: 'Перевод', appointment: 'Запись', review: 'На рассмотрении', result: 'Результат',
};

function stageLabel(s) { return stageLabels[s] || s; }

function formatDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' });
}

onMounted(async () => {
  try {
    const res = await api.get('/reports/overdue');
    cases.value = res.data.data || [];
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});
</script>

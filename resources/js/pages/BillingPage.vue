<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Биллинг и тарифы</h1>
      <p class="text-sm text-gray-500 mt-1">Управление подпиской и история платежей</p>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <template v-else>
      <!-- Toast -->
      <div v-if="toast"
        class="fixed top-5 right-5 z-50 bg-[#0A1F44] text-white text-sm px-4 py-3 rounded-xl shadow-lg max-w-xs transition-all">
        {{ toast }}
      </div>

      <!-- Текущий план -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-4">Текущая подписка</h2>
        <div class="flex flex-wrap items-start gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <span class="text-2xl font-bold text-[#0A1F44]">{{ planLabel(subscription.plan) }}</span>
              <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', statusBadgeClass(subscription.status)]">
                {{ statusLabel(subscription.status) }}
              </span>
            </div>
            <div v-if="subscription.expires_at" class="mt-2 text-sm text-gray-500">
              Действует до: <span class="font-medium text-gray-700">{{ formatDate(subscription.expires_at) }}</span>
            </div>
            <div v-else class="mt-2 text-sm text-gray-400">Без ограничения по времени</div>
          </div>
          <div class="text-right shrink-0">
            <p class="text-3xl font-bold text-[#1BA97F]">{{ planPrice(subscription.plan) }}</p>
            <p v-if="planPrice(subscription.plan) !== 'Бесплатно'" class="text-xs text-gray-400 mt-0.5">/ месяц</p>
          </div>
        </div>
      </section>

      <!-- Лимиты -->
      <section v-if="limits" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-4">Использование лимитов</h2>
        <div class="space-y-4">
          <!-- Менеджеры -->
          <div>
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">Менеджеры</span>
              <span class="font-medium text-gray-900">
                {{ limits.managers_count }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_managers ?? 'без лимита' }}</span>
              </span>
            </div>
            <div v-if="limits.max_managers" class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="managersPct >= 90 ? 'bg-red-500' : managersPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: managersPct + '%' }"></div>
            </div>
            <div v-else class="h-2 bg-[#1BA97F]/20 rounded-full">
              <div class="h-full bg-[#1BA97F] rounded-full w-full opacity-30"></div>
            </div>
          </div>

          <!-- Заявки -->
          <div>
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">Активные заявки</span>
              <span class="font-medium text-gray-900">
                {{ limits.cases_count }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_cases ?? 'без лимита' }}</span>
              </span>
            </div>
            <div v-if="limits.max_cases" class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="casesPct >= 90 ? 'bg-red-500' : casesPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: casesPct + '%' }"></div>
            </div>
            <div v-else class="h-2 bg-[#1BA97F]/20 rounded-full">
              <div class="h-full bg-[#1BA97F] rounded-full w-full opacity-30"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Карточки тарифов -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-4">Тарифные планы</h2>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div v-for="plan in plans" :key="plan.key"
            :class="[
              'relative flex flex-col rounded-xl border-2 p-4 transition-all',
              isCurrentPlan(plan.key)
                ? 'border-[#1BA97F] bg-[#1BA97F]/5'
                : 'border-gray-200 bg-white hover:border-gray-300',
            ]">
            <div v-if="isCurrentPlan(plan.key)"
              class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#1BA97F] text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider whitespace-nowrap">
              Текущий
            </div>
            <p class="font-bold text-[#0A1F44] text-base">{{ plan.label }}</p>
            <p class="text-2xl font-extrabold mt-1" :class="isCurrentPlan(plan.key) ? 'text-[#1BA97F]' : 'text-gray-800'">
              {{ plan.price }}
            </p>
            <p v-if="plan.price !== 'Бесплатно'" class="text-[11px] text-gray-400 -mt-0.5">/ месяц</p>
            <ul class="mt-3 space-y-1.5 flex-1">
              <li v-for="feat in plan.features" :key="feat" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5">✓</span>
                <span>{{ feat }}</span>
              </li>
            </ul>
            <button @click="selectPlan(plan)"
              :disabled="isCurrentPlan(plan.key)"
              :class="[
                'mt-4 w-full py-2 rounded-lg text-sm font-medium transition-colors',
                isCurrentPlan(plan.key)
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-[#0A1F44] text-white hover:bg-[#0d2a5e]',
              ]">
              {{ isCurrentPlan(plan.key) ? 'Активен' : 'Выбрать' }}
            </button>
          </div>
        </div>
      </section>

      <!-- История транзакций -->
      <section class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">История платежей</h2>
        </div>
        <div v-if="transactions.length === 0" class="px-6 py-10 text-center text-gray-400 text-sm">
          История платежей пуста
        </div>
        <table v-else class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="px-6 py-3 font-medium text-gray-500">Дата</th>
              <th class="px-6 py-3 font-medium text-gray-500">Тариф</th>
              <th class="px-6 py-3 font-medium text-gray-500">Сумма</th>
              <th class="px-6 py-3 font-medium text-gray-500">Статус</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions" :key="tx.id"
              class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
              <td class="px-6 py-3 text-gray-700">{{ formatDate(tx.created_at) }}</td>
              <td class="px-6 py-3 font-medium text-gray-900">{{ planLabel(tx.plan) }}</td>
              <td class="px-6 py-3 text-gray-700">{{ tx.amount ? '$' + tx.amount : '—' }}</td>
              <td class="px-6 py-3">
                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', txStatusClass(tx.status)]">
                  {{ txStatusLabel(tx.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/index';

const loading = ref(true);
const toast = ref('');

const subscription = ref({
  plan: 'trial',
  status: 'active',
  expires_at: null,
});

const limits = ref(null);
const transactions = ref([]);

const plans = [
  {
    key: 'trial',
    label: 'Trial',
    price: 'Бесплатно',
    features: ['3 менеджера', '50 заявок', '30 дней', 'Базовый функционал'],
  },
  {
    key: 'starter',
    label: 'Starter',
    price: '$19',
    features: ['5 менеджеров', '100 заявок', 'Отчёты', 'Email поддержка'],
  },
  {
    key: 'pro',
    label: 'Pro',
    price: '$49',
    features: ['10 менеджеров', '200 заявок', 'SLA-контроль', 'Маркетплейс'],
  },
  {
    key: 'enterprise',
    label: 'Enterprise',
    price: '$99',
    features: ['Без лимитов', 'Приоритет поддержки', 'API доступ', 'Персональный менеджер'],
  },
];

const planLabels = {
  trial: 'Trial',
  starter: 'Starter',
  pro: 'Pro',
  enterprise: 'Enterprise',
};

const planPrices = {
  trial: 'Бесплатно',
  starter: '$19',
  pro: '$49',
  enterprise: '$99',
};

function planLabel(key) {
  return planLabels[key] || key || '—';
}

function planPrice(key) {
  return planPrices[key] || '—';
}

function isCurrentPlan(key) {
  return subscription.value.plan === key;
}

function statusLabel(s) {
  const map = { active: 'Активна', expired: 'Истекла', cancelled: 'Отменена', trial: 'Пробный' };
  return map[s] || s || 'Неизвестно';
}

function statusBadgeClass(s) {
  if (s === 'active') return 'bg-green-100 text-green-700';
  if (s === 'expired') return 'bg-red-100 text-red-700';
  if (s === 'cancelled') return 'bg-gray-100 text-gray-500';
  return 'bg-blue-100 text-blue-700';
}

function txStatusLabel(s) {
  const map = { completed: 'Оплачено', pending: 'Ожидает', failed: 'Ошибка', refunded: 'Возврат' };
  return map[s] || s || '—';
}

function txStatusClass(s) {
  if (s === 'completed') return 'bg-green-100 text-green-700';
  if (s === 'pending') return 'bg-yellow-100 text-yellow-700';
  if (s === 'failed') return 'bg-red-100 text-red-700';
  if (s === 'refunded') return 'bg-gray-100 text-gray-500';
  return 'bg-gray-100 text-gray-500';
}

function formatDate(dateStr) {
  if (!dateStr) return '—';
  try {
    return new Date(dateStr).toLocaleDateString('ru-RU', {
      day: '2-digit', month: '2-digit', year: 'numeric',
    });
  } catch {
    return dateStr;
  }
}

const managersPct = computed(() => {
  if (!limits.value?.max_managers) return 0;
  return Math.min(100, Math.round((limits.value.managers_count / limits.value.max_managers) * 100));
});

const casesPct = computed(() => {
  if (!limits.value?.max_cases) return 0;
  return Math.min(100, Math.round((limits.value.cases_count / limits.value.max_cases) * 100));
});

function showToast(msg) {
  toast.value = msg;
  setTimeout(() => { toast.value = ''; }, 4000);
}

function selectPlan(plan) {
  if (isCurrentPlan(plan.key)) return;
  showToast('Для смены тарифа свяжитесь с поддержкой: info@visabor.uz');
}

onMounted(async () => {
  try {
    const [subRes, limRes, txRes] = await Promise.allSettled([
      api.get('/billing/subscription'),
      api.get('/billing/limits'),
      api.get('/billing/transactions'),
    ]);

    if (subRes.status === 'fulfilled') {
      const data = subRes.value.data?.data ?? subRes.value.data ?? {};
      subscription.value = { ...subscription.value, ...data };
    }

    if (limRes.status === 'fulfilled') {
      limits.value = limRes.value.data?.data ?? limRes.value.data ?? null;
    }

    if (txRes.status === 'fulfilled') {
      transactions.value = txRes.value.data?.data ?? txRes.value.data ?? [];
    }
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});
</script>

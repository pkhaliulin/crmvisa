<template>
  <div class="max-w-4xl mx-auto space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Тариф и подписка</h1>
      <p class="text-sm text-gray-500 mt-1">Ваш текущий план, использование лимитов и история оплат</p>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Toast -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-2"
          leave-active-class="transition duration-200 ease-in" leave-to-class="opacity-0 translate-y-2">
          <div v-if="toast"
            :class="['fixed top-5 right-5 z-50 text-white text-sm px-4 py-3 rounded-xl shadow-lg max-w-xs', toastError ? 'bg-red-600' : 'bg-[#0A1F44]']">
            {{ toast }}
          </div>
        </Transition>
      </Teleport>

      <!-- Текущий план -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center gap-2 mb-4">
          <div class="w-8 h-8 bg-[#1BA97F]/10 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
          </div>
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Текущая подписка</h2>
        </div>

        <div class="flex flex-wrap items-start gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 flex-wrap">
              <span class="text-2xl font-bold text-[#0A1F44]">{{ currentPlanName }}</span>
              <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', statusBadgeClass(sub.status)]">
                {{ statusLabel(sub.status) }}
              </span>
            </div>
            <div v-if="sub.plan?.slug" class="text-xs text-gray-400 mt-1">Тариф: {{ sub.plan_slug }}</div>
            <div v-if="sub.expires_at" class="mt-2 text-sm text-gray-500">
              Действует до: <span class="font-medium text-gray-700">{{ formatDate(sub.expires_at) }}</span>
              <span v-if="sub.days_left !== null && sub.days_left !== undefined" class="ml-1 text-xs"
                :class="sub.days_left <= 7 ? 'text-red-500 font-bold' : sub.days_left <= 30 ? 'text-amber-500' : 'text-gray-400'">
                ({{ sub.days_left }} дн.)
              </span>
            </div>
            <div v-else class="mt-2 text-sm text-gray-400">Без ограничения по времени</div>

            <div v-if="sub.payment_model" class="mt-2 text-xs text-gray-400">
              <span v-if="sub.payment_model === 'earn_first'" class="text-green-600 font-medium">Оплата из дохода (earn-first)</span>
              <span v-else-if="sub.payment_model === 'prepaid'" class="text-blue-600 font-medium">Предоплата</span>
              <span v-else class="text-gray-600 font-medium">{{ sub.payment_model }}</span>
            </div>

            <div v-if="sub.earn_first_progress" class="mt-3 p-3 bg-green-50 rounded-lg">
              <div class="flex justify-between text-xs mb-1">
                <span class="text-green-700 font-medium">Прогресс автосписания</span>
                <span class="text-green-700 font-bold">{{ sub.earn_first_progress.pct }}%</span>
              </div>
              <div class="h-2 bg-green-100 rounded-full overflow-hidden">
                <div class="h-full bg-green-500 rounded-full transition-all" :style="{ width: sub.earn_first_progress.pct + '%' }"></div>
              </div>
              <div class="text-[10px] text-green-600 mt-1">
                {{ fmtMoney(sub.earn_first_progress.deducted) }} из {{ fmtMoney(sub.earn_first_progress.target) }}
              </div>
            </div>

            <div v-if="sub.is_in_grace_period" class="mt-2 px-3 py-2 bg-amber-50 rounded-lg border border-amber-200">
              <div class="text-xs font-medium text-amber-700">Льготный период -- оплатите подписку до {{ formatDate(sub.grace_ends_at) }}</div>
            </div>
          </div>

          <div class="text-right shrink-0">
            <p class="text-3xl font-bold text-[#1BA97F]">{{ currentPlanPrice }}</p>
            <p v-if="currentPlanPrice !== 'Бесплатно'" class="text-xs text-gray-400 mt-0.5">/ месяц</p>
          </div>
        </div>
      </section>

      <!-- Лимиты -->
      <section v-if="limits" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-4">Использование лимитов</h2>
        <div class="space-y-4">
          <div>
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">Менеджеры</span>
              <span class="font-medium text-gray-900">
                {{ limits.managers_count }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_managers || 'безлимит' }}</span>
              </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="managersPct >= 90 ? 'bg-red-500' : managersPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: (limits.max_managers ? managersPct : 20) + '%' }"></div>
            </div>
          </div>

          <div>
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">Активные заявки</span>
              <span class="font-medium text-gray-900">
                {{ limits.cases_count }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_cases || 'безлимит' }}</span>
              </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="casesPct >= 90 ? 'bg-red-500' : casesPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: (limits.max_cases ? casesPct : 20) + '%' }"></div>
            </div>
          </div>

          <div v-if="limits.max_leads_per_month">
            <div class="flex justify-between text-sm mb-1.5">
              <span class="text-gray-600">Лиды за месяц</span>
              <span class="font-medium text-gray-900">
                {{ limits.leads_this_month || 0 }}
                <span class="text-gray-400 font-normal">/ {{ limits.max_leads_per_month }}</span>
              </span>
            </div>
            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full rounded-full transition-all duration-500"
                :class="leadsPct >= 90 ? 'bg-red-500' : leadsPct >= 70 ? 'bg-yellow-400' : 'bg-[#1BA97F]'"
                :style="{ width: leadsPct + '%' }"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- Тарифные планы -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Тарифные планы</h2>
            <p class="text-xs text-gray-400 mt-0.5">Выберите подходящий план для вашего агентства</p>
          </div>
          <div class="flex bg-gray-100 rounded-lg p-0.5">
            <button @click="selectedPeriod = 'monthly'"
              :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
                selectedPeriod === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
              Месяц
            </button>
            <button @click="selectedPeriod = 'yearly'"
              :class="['px-3 py-1.5 text-xs font-medium rounded-md transition-colors',
                selectedPeriod === 'yearly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']">
              Год <span class="text-[#1BA97F] font-bold">-17%</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="plan in availablePlans" :key="plan.slug"
            :class="[
              'relative flex flex-col rounded-xl border-2 p-4 transition-all',
              isCurrentPlan(plan.slug)
                ? 'border-[#1BA97F] bg-[#1BA97F]/5 ring-1 ring-[#1BA97F]/20'
                : plan.is_recommended
                  ? 'border-blue-400 bg-blue-50/30 hover:border-blue-500'
                  : 'border-gray-200 bg-white hover:border-gray-300',
            ]">
            <div v-if="isCurrentPlan(plan.slug)"
              class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[#1BA97F] text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider whitespace-nowrap">
              Текущий план
            </div>
            <div v-else-if="plan.is_recommended"
              class="absolute -top-3 left-1/2 -translate-x-1/2 bg-blue-500 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full whitespace-nowrap">
              Рекомендуемый
            </div>

            <p class="font-bold text-[#0A1F44] text-base">{{ plan.name }}</p>
            <p class="text-2xl font-extrabold mt-1" :class="isCurrentPlan(plan.slug) ? 'text-[#1BA97F]' : 'text-gray-800'">
              {{ planPrice(plan) > 0 ? fmtMoney(planPrice(plan)) : 'Бесплатно' }}
            </p>
            <p v-if="planPrice(plan) > 0" class="text-[11px] text-gray-400 -mt-0.5">
              / {{ selectedPeriod === 'yearly' ? 'год' : 'месяц' }}
            </p>

            <ul class="mt-3 space-y-1.5 flex-1">
              <li class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ plan.max_managers === 0 ? 'Без лимита менеджеров' : 'До ' + plan.max_managers + ' менеджеров' }}
              </li>
              <li class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                {{ plan.max_cases === 0 ? 'Без лимита заявок' : 'До ' + plan.max_cases + ' заявок' }}
              </li>
              <li v-if="plan.has_marketplace" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                Маркетплейс
              </li>
              <li v-if="plan.has_analytics" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                Аналитика
              </li>
              <li v-if="plan.has_api_access" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                API доступ
              </li>
              <li v-if="plan.has_white_label" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                White-label
              </li>
              <li v-if="plan.has_priority_support" class="flex items-start gap-1.5 text-[11px] text-gray-600">
                <span class="text-[#1BA97F] mt-0.5 shrink-0">+</span>
                Приоритетная поддержка
              </li>
            </ul>

            <button @click="openChangePlan(plan)"
              :disabled="isCurrentPlan(plan.slug) || changingPlan"
              :class="[
                'mt-4 w-full py-2 rounded-lg text-sm font-medium transition-colors',
                isCurrentPlan(plan.slug)
                  ? 'bg-[#1BA97F]/10 text-[#1BA97F] cursor-default'
                  : isUpgrade(plan)
                    ? 'bg-[#1BA97F] text-white hover:bg-[#158a68]'
                    : 'bg-[#0A1F44] text-white hover:bg-[#0d2a5e]',
              ]">
              {{ isCurrentPlan(plan.slug) ? 'Ваш план' : isUpgrade(plan) ? 'Перейти' : 'Выбрать' }}
            </button>
          </div>
        </div>
      </section>

      <!-- История -->
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
              <th class="px-6 py-3 font-medium text-gray-500">Тип</th>
              <th class="px-6 py-3 font-medium text-gray-500">Сумма</th>
              <th class="px-6 py-3 font-medium text-gray-500">Статус</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions" :key="tx.id"
              class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
              <td class="px-6 py-3 text-gray-700">{{ formatDate(tx.created_at || tx.paid_at) }}</td>
              <td class="px-6 py-3 font-medium text-gray-900">{{ txTypeLabel(tx.type || tx.plan) }}</td>
              <td class="px-6 py-3 text-gray-700">{{ tx.amount ? fmtMoney(tx.amount) : '--' }}</td>
              <td class="px-6 py-3">
                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full', txStatusClass(tx.status)]">
                  {{ txStatusLabel(tx.status) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </section>

      <!-- Модалка подтверждения смены тарифа -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
          leave-active-class="transition duration-150 ease-in" leave-to-class="opacity-0">
          <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showConfirmModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
              <h3 class="text-lg font-bold text-[#0A1F44]">Смена тарифного плана</h3>

              <div class="mt-4 p-4 bg-gray-50 rounded-xl space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Текущий план</span>
                  <span class="font-medium text-gray-700">{{ currentPlanName }}</span>
                </div>
                <div class="flex items-center justify-center">
                  <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                  </svg>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Новый план</span>
                  <span class="font-bold" :class="isUpgrade(selectedPlan) ? 'text-[#1BA97F]' : 'text-[#0A1F44]'">
                    {{ selectedPlan?.name }}
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Стоимость</span>
                  <span class="font-bold text-gray-900">
                    {{ selectedPlan ? (planPrice(selectedPlan) > 0 ? fmtMoney(planPrice(selectedPlan)) : 'Бесплатно') : '' }}
                    <span v-if="selectedPlan && planPrice(selectedPlan) > 0" class="text-gray-400 font-normal text-xs">
                      / {{ selectedPeriod === 'yearly' ? 'год' : 'мес' }}
                    </span>
                  </span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Период</span>
                  <span class="font-medium text-gray-700">{{ selectedPeriod === 'yearly' ? 'Годовой' : 'Ежемесячный' }}</span>
                </div>
              </div>

              <div v-if="isUpgrade(selectedPlan)" class="mt-3 p-3 bg-green-50 rounded-lg">
                <p class="text-xs text-green-700">Повышение тарифа. Новые лимиты и возможности вступят в силу сразу после подтверждения.</p>
              </div>
              <div v-else class="mt-3 p-3 bg-amber-50 rounded-lg">
                <p class="text-xs text-amber-700">Понижение тарифа. Новые лимиты вступят в силу сразу. Убедитесь, что текущее использование не превышает лимиты нового плана.</p>
              </div>

              <div class="mt-5 flex gap-3">
                <button @click="showConfirmModal = false" :disabled="changingPlan"
                  class="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                  Отмена
                </button>
                <button @click="confirmChangePlan" :disabled="changingPlan"
                  :class="[
                    'flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-white transition-colors flex items-center justify-center gap-2',
                    isUpgrade(selectedPlan)
                      ? 'bg-[#1BA97F] hover:bg-[#158a68]'
                      : 'bg-[#0A1F44] hover:bg-[#0d2a5e]',
                    changingPlan ? 'opacity-70 cursor-wait' : '',
                  ]">
                  <div v-if="changingPlan" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                  {{ changingPlan ? 'Применяем...' : 'Подтвердить' }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/index';

const loading = ref(true);
const toast = ref('');
const toastError = ref(false);

const sub = ref({
  plan_slug: 'trial',
  plan: null,
  status: 'active',
  expires_at: null,
  days_left: null,
  payment_model: null,
  earn_first_progress: null,
  is_in_grace_period: false,
  grace_ends_at: null,
});

const limits = ref(null);
const transactions = ref([]);
const availablePlans = ref([]);
const selectedPeriod = ref('monthly');
const showConfirmModal = ref(false);
const selectedPlan = ref(null);
const changingPlan = ref(false);

const currentPlanName = computed(() => {
  if (sub.value.plan?.name) return sub.value.plan.name;
  const map = { trial: 'Trial', starter: 'Starter', pro: 'Professional', enterprise: 'Enterprise', micro: 'Micro', business: 'Business', franchise: 'Franchise' };
  return map[sub.value.plan_slug] || sub.value.plan_slug || 'Не определен';
});

const currentPlanPrice = computed(() => {
  if (sub.value.plan?.price_uzs > 0) return fmtMoney(sub.value.plan.price_uzs);
  const plan = availablePlans.value.find(p => p.slug === sub.value.plan_slug);
  if (plan?.price_uzs > 0) return fmtMoney(plan.price_uzs);
  return 'Бесплатно';
});

function planPrice(plan) {
  if (!plan) return 0;
  return selectedPeriod.value === 'yearly' ? (plan.price_yearly || 0) : (plan.price_uzs || 0);
}

function isCurrentPlan(slug) {
  return sub.value.plan_slug === slug;
}

function isUpgrade(plan) {
  if (!plan) return false;
  const currentPlan = availablePlans.value.find(p => p.slug === sub.value.plan_slug);
  if (!currentPlan) return true;
  return (plan.price_uzs || 0) > (currentPlan.price_uzs || 0);
}

function statusLabel(s) {
  const map = { active: 'Активна', expired: 'Истекла', cancelled: 'Отменена', trialing: 'Пробный период', past_due: 'Просрочена' };
  return map[s] || s || 'Неизвестно';
}

function statusBadgeClass(s) {
  if (s === 'active') return 'bg-green-100 text-green-700';
  if (s === 'trialing') return 'bg-blue-100 text-blue-700';
  if (s === 'past_due') return 'bg-amber-100 text-amber-700';
  if (s === 'expired') return 'bg-red-100 text-red-700';
  if (s === 'cancelled') return 'bg-gray-100 text-gray-500';
  return 'bg-blue-100 text-blue-700';
}

function txTypeLabel(type) {
  const map = {
    subscription: 'Подписка', activation_fee: 'Взнос за подключение',
    commission: 'Комиссия', earn_first: 'Автосписание',
    payout: 'Выплата', refund: 'Возврат',
  };
  return map[type] || type || '--';
}

function txStatusLabel(s) {
  const map = { succeeded: 'Оплачено', completed: 'Оплачено', pending: 'Ожидает', failed: 'Ошибка', refunded: 'Возврат' };
  return map[s] || s || '--';
}

function txStatusClass(s) {
  if (s === 'succeeded' || s === 'completed') return 'bg-green-100 text-green-700';
  if (s === 'pending') return 'bg-yellow-100 text-yellow-700';
  if (s === 'failed') return 'bg-red-100 text-red-700';
  return 'bg-gray-100 text-gray-500';
}

function fmtMoney(val) {
  if (!val && val !== 0) return '0 сум';
  return Number(val).toLocaleString('ru-RU') + ' сум';
}

function formatDate(dateStr) {
  if (!dateStr) return '--';
  try {
    return new Date(dateStr).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } catch { return dateStr; }
}

const managersPct = computed(() => {
  if (!limits.value?.max_managers) return 0;
  return Math.min(100, Math.round((limits.value.managers_count / limits.value.max_managers) * 100));
});

const casesPct = computed(() => {
  if (!limits.value?.max_cases) return 0;
  return Math.min(100, Math.round((limits.value.cases_count / limits.value.max_cases) * 100));
});

const leadsPct = computed(() => {
  if (!limits.value?.max_leads_per_month) return 0;
  return Math.min(100, Math.round(((limits.value.leads_this_month || 0) / limits.value.max_leads_per_month) * 100));
});

function showToast(msg, isError = false) {
  toast.value = msg;
  toastError.value = isError;
  setTimeout(() => { toast.value = ''; }, 4000);
}

function openChangePlan(plan) {
  if (isCurrentPlan(plan.slug)) return;
  selectedPlan.value = plan;
  showConfirmModal.value = true;
}

async function confirmChangePlan() {
  if (!selectedPlan.value || changingPlan.value) return;
  changingPlan.value = true;

  try {
    const res = await api.post('/billing/change-plan', {
      plan_slug: selectedPlan.value.slug,
      billing_period: selectedPeriod.value,
    });

    const data = res.data?.data ?? res.data;
    showConfirmModal.value = false;
    showToast(`Тариф изменён на ${data.plan_name || selectedPlan.value.name}`);

    // Перезагрузить данные подписки и лимитов
    const [subRes, limRes] = await Promise.allSettled([
      api.get('/billing/subscription'),
      api.get('/billing/limits'),
    ]);

    if (subRes.status === 'fulfilled') {
      const d = subRes.value.data?.data ?? subRes.value.data ?? {};
      sub.value = { ...sub.value, ...d };
    }
    if (limRes.status === 'fulfilled') {
      limits.value = limRes.value.data?.data ?? limRes.value.data ?? null;
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] || 'Ошибка при смене тарифа';
    showToast(msg, true);
  } finally {
    changingPlan.value = false;
  }
}

onMounted(async () => {
  try {
    const [subRes, limRes, txRes, plansRes] = await Promise.allSettled([
      api.get('/billing/subscription'),
      api.get('/billing/limits'),
      api.get('/billing/transactions'),
      api.get('/billing/plans'),
    ]);

    if (subRes.status === 'fulfilled') {
      const data = subRes.value.data?.data ?? subRes.value.data ?? {};
      sub.value = { ...sub.value, ...data };
    }

    if (limRes.status === 'fulfilled') {
      limits.value = limRes.value.data?.data ?? limRes.value.data ?? null;
    }

    if (txRes.status === 'fulfilled') {
      const raw = txRes.value.data?.data ?? txRes.value.data ?? [];
      transactions.value = Array.isArray(raw) ? raw : [];
    }

    if (plansRes.status === 'fulfilled') {
      const raw = plansRes.value.data?.data ?? plansRes.value.data ?? [];
      availablePlans.value = (Array.isArray(raw) ? raw : []).filter(p => p.is_active !== false && p.is_public !== false);
    }
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});
</script>

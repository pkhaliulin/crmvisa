<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Биллинг и тарифы</h1>
      <p class="text-sm text-gray-500 mt-1">Управление тарифными планами, купонами и настройками платформы</p>
    </div>

    <!-- Табы -->
    <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
      <button v-for="t in tabs" :key="t.key"
        @click="activeTab = t.key"
        :class="['px-4 py-2 text-sm font-medium rounded-md transition-colors',
          activeTab === t.key
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-600 hover:text-gray-900']">
        {{ t.label }}
      </button>
    </div>

    <!-- Дашборд -->
    <div v-if="activeTab === 'dashboard'" class="space-y-6">
      <div v-if="dashLoading" class="text-center py-12 text-gray-400">Загрузка...</div>
      <template v-else-if="dash">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-gray-500">Общий доход</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ fmtMoney(dash.revenue?.total_revenue) }}</div>
          </div>
          <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-gray-500">Доход за месяц</div>
            <div class="text-2xl font-bold text-green-600 mt-1">{{ fmtMoney(dash.revenue_this_month) }}</div>
          </div>
          <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-gray-500">НДС к уплате</div>
            <div class="text-2xl font-bold text-orange-600 mt-1">{{ fmtMoney(dash.revenue?.total_vat) }}</div>
          </div>
          <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-gray-500">Просроченные счета</div>
            <div class="text-2xl font-bold text-red-600 mt-1">{{ dash.overdue_invoices }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="bg-white rounded-xl border p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Подписки по статусу</h3>
            <div v-for="(count, status) in dash.subscriptions" :key="status"
              class="flex justify-between py-1 text-sm">
              <span :class="statusColor(status)">{{ statusLabel(status) }}</span>
              <span class="font-medium">{{ count }}</span>
            </div>
          </div>
          <div class="bg-white rounded-xl border p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Распределение по планам</h3>
            <div v-for="(count, plan) in dash.plan_distribution" :key="plan"
              class="flex justify-between py-1 text-sm">
              <span class="text-gray-700 capitalize">{{ plan }}</span>
              <span class="font-medium">{{ count }}</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border p-4">
          <h3 class="text-sm font-semibold text-gray-900 mb-3">Последние события</h3>
          <div class="space-y-2 max-h-80 overflow-y-auto">
            <div v-for="ev in dash.recent_events" :key="ev.id"
              class="flex items-center gap-3 text-sm py-1.5 border-b border-gray-50 last:border-0">
              <span class="text-xs text-gray-400 w-32 shrink-0">{{ formatDate(ev.created_at) }}</span>
              <span class="font-mono text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded">{{ ev.event }}</span>
              <span class="text-gray-500 truncate">{{ ev.agency_id?.slice(0, 8) || 'system' }}</span>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Тарифы -->
    <div v-if="activeTab === 'plans'" class="space-y-4">
      <div class="flex justify-end">
        <button @click="openPlanModal(null)" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
          + Новый тариф
        </button>
      </div>

      <div v-if="plansLoading" class="text-center py-12 text-gray-400">Загрузка...</div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div v-for="p in plans" :key="p.slug"
          :class="['bg-white rounded-xl border-2 p-5 relative',
            p.is_recommended ? 'border-blue-500' : 'border-gray-200',
            !p.is_active ? 'opacity-60' : '']">
          <div v-if="p.is_recommended"
            class="absolute -top-3 left-1/2 -translate-x-1/2 text-[10px] bg-blue-500 text-white px-3 py-0.5 rounded-full font-bold">
            Рекомендуемый
          </div>

          <div class="text-lg font-bold text-gray-900">{{ p.name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ p.slug }}</div>
          <div class="text-2xl font-bold text-gray-900 mt-3">
            {{ p.price_uzs > 0 ? fmtMoney(p.price_uzs) : 'Бесплатно' }}
            <span v-if="p.price_uzs > 0" class="text-sm font-normal text-gray-500">/мес</span>
          </div>

          <div class="mt-3 space-y-1.5 text-xs text-gray-600">
            <div>Менеджеров: {{ p.max_managers === 0 ? 'Без лимита' : p.max_managers }}</div>
            <div>Заявок: {{ p.max_cases === 0 ? 'Без лимита' : p.max_cases }}</div>
            <div>Лидов/мес: {{ p.max_leads_per_month === 0 ? 'Без лимита' : p.max_leads_per_month }}</div>
            <div v-if="p.activation_fee_uzs > 0">Актив. сбор: {{ fmtMoney(p.activation_fee_uzs) }}</div>
            <div v-if="p.earn_first_enabled" class="text-green-700">Earn-first: {{ p.earn_first_deduction_pct }}%</div>
            <div v-if="p.trial_days > 0" class="text-blue-700">Пробный: {{ p.trial_days }} дн.</div>
            <div>Grace: {{ p.grace_period_days }} дн.</div>
          </div>

          <div class="mt-3 flex flex-wrap gap-1">
            <span v-if="p.has_marketplace" class="text-[10px] bg-green-50 text-green-700 px-2 py-0.5 rounded-full">Маркетплейс</span>
            <span v-if="p.has_analytics" class="text-[10px] bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full">Аналитика</span>
            <span v-if="p.has_api_access" class="text-[10px] bg-orange-50 text-orange-700 px-2 py-0.5 rounded-full">API</span>
            <span v-if="p.has_white_label" class="text-[10px] bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">White-label</span>
          </div>

          <div class="text-xs text-gray-400 mt-2">
            Подписчиков: {{ p.subscribers_count || 0 }}
          </div>

          <div class="mt-4 flex gap-2">
            <button @click="openPlanModal(p)" class="flex-1 px-3 py-1.5 text-xs bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
              Редактировать
            </button>
            <button v-if="p.is_active" @click="deactivatePlan(p.slug)" class="px-3 py-1.5 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
              Выкл
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Настройки -->
    <div v-if="activeTab === 'settings'" class="space-y-4">
      <div v-if="settingsLoading" class="text-center py-12 text-gray-400">Загрузка...</div>
      <div v-else class="bg-white rounded-xl border p-6 max-w-xl">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Настройки биллинга</h3>
        <div class="space-y-4">
          <div v-for="s in settings" :key="s.key" class="flex items-center justify-between gap-4">
            <div>
              <div class="text-sm font-medium text-gray-700">{{ s.description || s.key }}</div>
              <div class="text-xs text-gray-400">{{ s.key }}</div>
            </div>
            <div class="w-40">
              <template v-if="s.type === 'boolean'">
                <button @click="s.value = !s.value"
                  :class="['w-12 h-6 rounded-full transition-colors relative',
                    s.value ? 'bg-green-500' : 'bg-gray-300']">
                  <span :class="['absolute top-0.5 w-5 h-5 bg-white rounded-full shadow transition-all',
                    s.value ? 'left-[26px]' : 'left-0.5']" />
                </button>
              </template>
              <input v-else v-model="s.value"
                class="w-full px-3 py-1.5 text-sm border rounded-lg focus:ring-1 focus:ring-blue-500 focus:border-blue-500" />
            </div>
          </div>
        </div>
        <div class="mt-6">
          <button @click="saveSettings" :disabled="settingsSaving"
            class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ settingsSaving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Купоны -->
    <div v-if="activeTab === 'coupons'" class="space-y-4">
      <div class="flex justify-end">
        <button @click="openCouponModal(null)" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
          + Новый купон
        </button>
      </div>

      <div v-if="couponsLoading" class="text-center py-12 text-gray-400">Загрузка...</div>
      <div v-else class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50">
            <tr class="text-left">
              <th class="px-4 py-3 font-medium text-gray-500">Код</th>
              <th class="px-4 py-3 font-medium text-gray-500">Скидка</th>
              <th class="px-4 py-3 font-medium text-gray-500">Использовано</th>
              <th class="px-4 py-3 font-medium text-gray-500">Действует до</th>
              <th class="px-4 py-3 font-medium text-gray-500">Статус</th>
              <th class="px-4 py-3 font-medium text-gray-500 w-20"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="c in coupons" :key="c.id" class="border-t border-gray-100 hover:bg-gray-50">
              <td class="px-4 py-3 font-mono font-bold text-gray-900">{{ c.code }}</td>
              <td class="px-4 py-3">
                {{ c.discount_type === 'percentage' ? c.discount_value + '%' : fmtMoney(c.discount_value) }}
              </td>
              <td class="px-4 py-3 text-gray-500">
                {{ c.used_count }}{{ c.max_uses > 0 ? ' / ' + c.max_uses : '' }}
              </td>
              <td class="px-4 py-3 text-gray-500">{{ c.valid_until || 'Бессрочно' }}</td>
              <td class="px-4 py-3">
                <span :class="['text-xs px-2 py-0.5 rounded-full',
                  c.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500']">
                  {{ c.is_active ? 'Активен' : 'Отключён' }}
                </span>
              </td>
              <td class="px-4 py-3">
                <button @click="openCouponModal(c)" class="text-blue-600 hover:underline text-xs">Ред.</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Модалка тарифа -->
    <div v-if="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showPlanModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6">
        <h2 class="text-lg font-bold mb-4">{{ editPlan ? 'Редактирование тарифа' : 'Новый тариф' }}</h2>
        <div class="grid grid-cols-2 gap-4">
          <div v-if="!editPlan">
            <label class="block text-xs text-gray-500 mb-1">Slug *</label>
            <input v-model="planForm.slug" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="my-plan" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Название *</label>
            <input v-model="planForm.name" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Название (UZ)</label>
            <input v-model="planForm.name_uz" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div class="col-span-2">
            <label class="block text-xs text-gray-500 mb-1">Описание</label>
            <input v-model="planForm.description" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>

          <div>
            <label class="block text-xs text-gray-500 mb-1">Цена / мес (UZS)</label>
            <input v-model.number="planForm.price_uzs" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Актив. сбор (UZS)</label>
            <input v-model.number="planForm.activation_fee_uzs" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Цена / мес (центы USD)</label>
            <input v-model.number="planForm.price_monthly" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Цена / год (центы USD)</label>
            <input v-model.number="planForm.price_yearly" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>

          <div>
            <label class="block text-xs text-gray-500 mb-1">Макс. менеджеров (0 = безлимит)</label>
            <input v-model.number="planForm.max_managers" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Макс. заявок (0 = безлимит)</label>
            <input v-model.number="planForm.max_cases" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Макс. лидов/мес (0 = безлимит)</label>
            <input v-model.number="planForm.max_leads_per_month" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Пробных дней</label>
            <input v-model.number="planForm.trial_days" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Grace-период (дни)</label>
            <input v-model.number="planForm.grace_period_days" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Earn-first %</label>
            <input v-model.number="planForm.earn_first_deduction_pct" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>

          <div class="col-span-2 grid grid-cols-3 gap-3 mt-2">
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.earn_first_enabled" class="rounded" /> Earn-first
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.has_marketplace" class="rounded" /> Маркетплейс
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.has_analytics" class="rounded" /> Аналитика
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.has_api_access" class="rounded" /> API
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.has_white_label" class="rounded" /> White-label
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.has_custom_domain" class="rounded" /> Custom домен
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.has_priority_support" class="rounded" /> Приоритетная поддержка
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.is_active" class="rounded" /> Активен
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.is_public" class="rounded" /> Публичный
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input type="checkbox" v-model="planForm.is_recommended" class="rounded" /> Рекомендуемый
            </label>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button @click="showPlanModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
          <button @click="savePlan" :disabled="planSaving"
            class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ planSaving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Модалка купона -->
    <div v-if="showCouponModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="showCouponModal = false">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h2 class="text-lg font-bold mb-4">{{ editCoupon ? 'Редактирование купона' : 'Новый купон' }}</h2>
        <div class="space-y-3">
          <div>
            <label class="block text-xs text-gray-500 mb-1">Код *</label>
            <input v-model="couponForm.code" :disabled="!!editCoupon" class="w-full px-3 py-2 border rounded-lg text-sm font-mono uppercase" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 mb-1">Описание</label>
            <input v-model="couponForm.description" class="w-full px-3 py-2 border rounded-lg text-sm" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Тип</label>
              <select v-model="couponForm.discount_type" class="w-full px-3 py-2 border rounded-lg text-sm">
                <option value="percentage">Процент</option>
                <option value="fixed">Фиксированная (UZS)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Значение</label>
              <input v-model.number="couponForm.discount_value" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">Макс. использований (0 = безлимит)</label>
              <input v-model.number="couponForm.max_uses" type="number" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">Действует до</label>
              <input v-model="couponForm.valid_until" type="date" class="w-full px-3 py-2 border rounded-lg text-sm" />
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" v-model="couponForm.is_active" class="rounded" /> Активен
          </label>
        </div>
        <div class="flex justify-end gap-3 mt-6">
          <button @click="showCouponModal = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Отмена</button>
          <button @click="saveCoupon" :disabled="couponSaving"
            class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ couponSaving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const tabs = [
  { key: 'dashboard', label: 'Дашборд' },
  { key: 'plans',     label: 'Тарифы' },
  { key: 'settings',  label: 'Настройки' },
  { key: 'coupons',   label: 'Купоны' },
];
const activeTab = ref('dashboard');

// Dashboard
const dash = ref(null);
const dashLoading = ref(false);
async function loadDashboard() {
  dashLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/dashboard');
    dash.value = data.data;
  } catch (e) { console.error(e); }
  dashLoading.value = false;
}

// Plans
const plans = ref([]);
const plansLoading = ref(false);
const showPlanModal = ref(false);
const editPlan = ref(null);
const planSaving = ref(false);
const planForm = ref({});

async function loadPlans() {
  plansLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/plans');
    plans.value = data.data;
  } catch (e) { console.error(e); }
  plansLoading.value = false;
}

function defaultPlanForm() {
  return {
    slug: '', name: '', name_uz: '', description: '',
    price_monthly: 0, price_yearly: 0, price_uzs: 0,
    activation_fee_uzs: 0, earn_first_enabled: false, earn_first_deduction_pct: 0,
    max_managers: 3, max_cases: 50, max_leads_per_month: 30,
    has_marketplace: false, has_priority_support: false, has_api_access: false,
    has_custom_domain: false, has_white_label: false, has_analytics: false,
    trial_days: 0, grace_period_days: 3,
    is_active: true, is_public: true, is_recommended: false, sort_order: 0,
  };
}

function openPlanModal(plan) {
  editPlan.value = plan;
  planForm.value = plan ? { ...plan } : defaultPlanForm();
  showPlanModal.value = true;
}

async function savePlan() {
  planSaving.value = true;
  try {
    if (editPlan.value) {
      await api.patch(`/owner/billing/plans/${editPlan.value.slug}`, planForm.value);
    } else {
      await api.post('/owner/billing/plans', planForm.value);
    }
    showPlanModal.value = false;
    await loadPlans();
  } catch (e) { alert(e.response?.data?.message || 'Ошибка'); }
  planSaving.value = false;
}

async function deactivatePlan(slug) {
  try {
    await api.delete(`/owner/billing/plans/${slug}`);
    await loadPlans();
  } catch (e) { alert(e.response?.data?.message || 'Ошибка'); }
}

// Settings
const settings = ref([]);
const settingsLoading = ref(false);
const settingsSaving = ref(false);

async function loadSettings() {
  settingsLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/settings');
    settings.value = data.data;
  } catch (e) { console.error(e); }
  settingsLoading.value = false;
}

async function saveSettings() {
  settingsSaving.value = true;
  try {
    await api.patch('/owner/billing/settings', {
      settings: settings.value.map(s => ({ key: s.key, value: s.value })),
    });
  } catch (e) { alert('Ошибка сохранения'); }
  settingsSaving.value = false;
}

// Coupons
const coupons = ref([]);
const couponsLoading = ref(false);
const showCouponModal = ref(false);
const editCoupon = ref(null);
const couponSaving = ref(false);
const couponForm = ref({});

async function loadCoupons() {
  couponsLoading.value = true;
  try {
    const { data } = await api.get('/owner/billing/coupons');
    coupons.value = data.data;
  } catch (e) { console.error(e); }
  couponsLoading.value = false;
}

function openCouponModal(coupon) {
  editCoupon.value = coupon;
  couponForm.value = coupon ? { ...coupon } : {
    code: '', description: '', discount_type: 'percentage',
    discount_value: 10, max_uses: 0, valid_until: '', is_active: true,
  };
  showCouponModal.value = true;
}

async function saveCoupon() {
  couponSaving.value = true;
  try {
    if (editCoupon.value) {
      await api.patch(`/owner/billing/coupons/${editCoupon.value.id}`, couponForm.value);
    } else {
      await api.post('/owner/billing/coupons', couponForm.value);
    }
    showCouponModal.value = false;
    await loadCoupons();
  } catch (e) { alert(e.response?.data?.message || 'Ошибка'); }
  couponSaving.value = false;
}

// Helpers
function fmtMoney(val) {
  if (!val && val !== 0) return '0 сум';
  return Number(val).toLocaleString('ru-RU') + ' сум';
}

function formatDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
}

function statusColor(s) {
  const map = { active: 'text-green-700', trialing: 'text-blue-700', past_due: 'text-orange-700', cancelled: 'text-gray-500', expired: 'text-red-700' };
  return map[s] || 'text-gray-700';
}
function statusLabel(s) {
  const map = { active: 'Активные', trialing: 'Пробные', past_due: 'Просроченные', cancelled: 'Отменённые', expired: 'Истёкшие' };
  return map[s] || s;
}

onMounted(() => {
  loadDashboard();
  loadPlans();
  loadSettings();
  loadCoupons();
});
</script>

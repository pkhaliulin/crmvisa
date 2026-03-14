<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.finance.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('crm.finance.subtitle') }}</p>
    </div>

    <!-- Вкладки -->
    <div class="flex gap-1 bg-gray-100 rounded-xl p-1 w-fit">
      <button v-for="tab in tabs" :key="tab.key"
        @click="activeTab = tab.key"
        :class="['px-4 py-1.5 text-sm rounded-lg transition-all font-medium',
          activeTab === tab.key
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700']">
        {{ tab.label }}
      </button>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- ОБЗОР -->
    <div v-else-if="activeTab === 'overview'" class="space-y-4">
      <!-- Метрики -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.finance.totalRevenue') }}</p>
          <p class="text-2xl font-bold text-green-600 mt-1">{{ fmtAmount(overview.total_revenue) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.finance.debtAmount') }}</p>
          <p class="text-2xl font-bold text-orange-500 mt-1">{{ fmtAmount(overview.debt_amount) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.finance.refundsTotal') }}</p>
          <p class="text-2xl font-bold text-red-600 mt-1">{{ fmtAmount(overview.refunds_total) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
          <p class="text-xs text-gray-500 uppercase tracking-wide">{{ t('crm.finance.netRevenue') }}</p>
          <p class="text-2xl font-bold text-blue-600 mt-1">{{ fmtAmount(overview.net_revenue) }}</p>
        </div>
      </div>

      <!-- Предупреждения -->
      <div v-if="overview.overdue_count || overview.blocked_count" class="flex gap-3 flex-wrap">
        <span v-if="overview.overdue_count" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 text-red-700 text-sm font-medium">
          <ExclamationTriangleIcon class="w-4 h-4" />
          {{ t('crm.finance.overdueWarning') }}: {{ overview.overdue_count }}
        </span>
        <span v-if="overview.blocked_count" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-50 text-orange-700 text-sm font-medium">
          <ExclamationTriangleIcon class="w-4 h-4" />
          {{ t('crm.finance.blocked') }}: {{ overview.blocked_count }}
        </span>
      </div>

      <!-- Заявки по статусу оплаты + Выручка по методу -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <!-- По статусу оплаты -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.finance.casesTitle') }}</h3>
          <div class="space-y-3">
            <div v-for="item in paymentStatusItems" :key="item.key" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ item.label }}</span>
                <span class="font-semibold text-gray-900">{{ item.count }}</span>
              </div>
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all" :class="item.barColor"
                  :style="{ width: maxPaymentStatus ? (item.count / maxPaymentStatus * 100) + '%' : '0%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- По методу оплаты -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
          <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.finance.revenueByMethod') }}</h3>
          <div class="space-y-3">
            <div v-for="item in methodItems" :key="item.key" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ item.label }}</span>
                <span class="font-semibold text-gray-900">{{ fmtAmount(item.amount) }}</span>
              </div>
              <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-[#1BA97F] transition-all"
                  :style="{ width: maxMethodAmount ? (item.amount / maxMethodAmount * 100) + '%' : '0%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ПЛАТЕЖИ -->
    <div v-else-if="activeTab === 'payments'" class="space-y-4">
      <!-- Фильтры -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
        <div class="flex flex-wrap gap-3 items-end">
          <div class="w-48">
            <label class="text-xs text-gray-500 mb-1 block">{{ t('crm.finance.filterMethod') }}</label>
            <SearchSelect v-model="filters.method" :items="methodOptions" compact allow-all :all-label="t('crm.finance.filterMethod')" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('crm.finance.filterFrom') }}</label>
            <input v-model="filters.from" type="date" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#1BA97F]/30" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('crm.finance.filterTo') }}</label>
            <input v-model="filters.to" type="date" class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#1BA97F]/30" />
          </div>
          <div class="w-48">
            <label class="text-xs text-gray-500 mb-1 block">{{ t('crm.finance.filterClient') }}</label>
            <input v-model="filters.client" type="text" :placeholder="t('crm.finance.filterClient')"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#1BA97F]/30" />
          </div>
          <button @click="loadPayments" class="px-4 py-1.5 bg-[#1BA97F] text-white text-sm rounded-lg hover:bg-[#158f6b] transition-colors">
            {{ t('crm.finance.filterApply') }}
          </button>
        </div>
      </div>

      <!-- Таблица платежей -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.date') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.caseNumber') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.client') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.amount') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.method') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.manager') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.comment') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in payments.data" :key="p.id" class="border-b border-gray-50 hover:bg-gray-50/50">
                <td class="px-4 py-3 text-gray-700">{{ formatDate(p.paid_at) }}</td>
                <td class="px-4 py-3">
                  <router-link v-if="p.case_id" :to="{ name: 'cases.show', params: { id: p.case_id } }" class="text-blue-600 hover:underline">
                    #{{ p.case_number }}
                  </router-link>
                  <span v-else class="text-gray-400">--</span>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ p.client_name ?? '--' }}</td>
                <td class="px-4 py-3 font-semibold text-gray-900">{{ fmtAmount(p.amount) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ methodLabel(p.method) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ p.manager_name ?? '--' }}</td>
                <td class="px-4 py-3 text-gray-400 max-w-[200px] truncate">{{ p.comment ?? '' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!payments.data?.length" class="py-12 text-center text-gray-400 text-sm">{{ t('crm.finance.noData') }}</div>

        <!-- Пагинация -->
        <div v-if="payments.last_page > 1" class="flex items-center justify-center gap-2 p-4 border-t border-gray-100">
          <button v-for="page in payments.last_page" :key="page"
            @click="loadPayments(page)"
            :class="['w-8 h-8 rounded-lg text-sm font-medium transition-colors',
              page === payments.current_page ? 'bg-[#1BA97F] text-white' : 'text-gray-600 hover:bg-gray-100']">
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- ДОЛГИ -->
    <div v-else-if="activeTab === 'debts'" class="space-y-4">
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.caseNumber') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.client') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.totalPrice') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.paid') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.remaining') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.deadline') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.status') }}</th>
                <th class="px-4 py-3 font-medium text-gray-500">{{ t('crm.finance.manager') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in debts" :key="d.case_id"
                :class="['border-b border-gray-50', d.is_overdue ? 'bg-red-50/50' : 'hover:bg-gray-50/50']">
                <td class="px-4 py-3">
                  <router-link :to="{ name: 'cases.show', params: { id: d.case_id } }" class="text-blue-600 hover:underline">
                    #{{ d.case_number }}
                  </router-link>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ d.client_name }}</td>
                <td class="px-4 py-3 font-semibold text-gray-900">{{ fmtAmount(d.total_price) }}</td>
                <td class="px-4 py-3 text-green-600">{{ fmtAmount(d.paid) }}</td>
                <td class="px-4 py-3 font-semibold text-orange-600">{{ fmtAmount(d.remaining) }}</td>
                <td class="px-4 py-3 text-gray-600">{{ formatDate(d.deadline) }}</td>
                <td class="px-4 py-3">
                  <span v-if="d.payment_blocked" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                    {{ t('crm.finance.blocked') }}
                  </span>
                  <span v-else-if="d.is_overdue" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                    {{ t('crm.finance.overdue') }}
                  </span>
                  <span v-else class="text-xs font-semibold px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                    {{ t('crm.finance.unpaid') }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ d.manager_name ?? '--' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!debts.length" class="py-12 text-center text-gray-400 text-sm">{{ t('crm.finance.noData') }}</div>
      </div>
    </div>

    <!-- АНАЛИТИКА -->
    <div v-else-if="activeTab === 'analytics'" class="space-y-4">
      <!-- По менеджерам -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.finance.byManager') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm mb-4">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.finance.manager') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.finance.casesCount') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.finance.totalAmount') }}</th>
                <th class="pb-2 font-medium text-gray-500 w-1/3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in byManager" :key="row.manager_id" class="border-b border-gray-50">
                <td class="py-2 text-gray-700">{{ row.manager_name }}</td>
                <td class="py-2">{{ row.cases_count }}</td>
                <td class="py-2 font-semibold text-gray-900">{{ fmtAmount(row.total_amount) }}</td>
                <td class="py-2">
                  <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-[#1BA97F] transition-all"
                      :style="{ width: maxManagerAmount ? (row.total_amount / maxManagerAmount * 100) + '%' : '0%' }"></div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!byManager.length" class="text-sm text-gray-400 text-center py-4">{{ t('crm.finance.noData') }}</div>
      </div>

      <!-- По странам -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.finance.byCountry') }}</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm mb-4">
            <thead>
              <tr class="text-left border-b border-gray-100">
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.finance.country') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.finance.casesCount') }}</th>
                <th class="pb-2 font-medium text-gray-500">{{ t('crm.finance.totalAmount') }}</th>
                <th class="pb-2 font-medium text-gray-500 w-1/3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in byCountry" :key="row.country_code" class="border-b border-gray-50">
                <td class="py-2 text-gray-700">
                  <span class="mr-1">{{ countryFlag(row.country_code) }}</span>
                  {{ countryName(row.country_code) }}
                </td>
                <td class="py-2">{{ row.cases_count }}</td>
                <td class="py-2 font-semibold text-gray-900">{{ fmtAmount(row.total_amount) }}</td>
                <td class="py-2">
                  <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-blue-500 transition-all"
                      :style="{ width: maxCountryAmount ? (row.total_amount / maxCountryAmount * 100) + '%' : '0%' }"></div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="!byCountry.length" class="text-sm text-gray-400 text-center py-4">{{ t('crm.finance.noData') }}</div>
      </div>
    </div>

    <!-- === ДОГОВОРЫ === -->
    <div v-else-if="activeTab === 'contracts'" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-gray-700">{{ t('crm.finance.contractsTitle') }}</h3>
        <div class="flex gap-2">
          <SearchSelect v-model="contractFilter.status" :items="contractStatuses" compact allow-all :all-label="t('crm.finance.filterStatus')" class="w-36" />
          <input v-model="contractFilter.client" type="text" :placeholder="t('crm.finance.filterClient')"
            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-40 outline-none focus:border-[#1BA97F]" />
          <button @click="loadContracts" class="text-xs px-3 py-1.5 rounded-lg bg-[#1BA97F] text-white font-medium hover:bg-[#168c69]">
            {{ t('crm.finance.filterApply') }}
          </button>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead><tr class="text-left border-b border-gray-200">
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.contractNumber') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.caseNumber') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.client') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.totalAmount') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.contractStatus') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.date') }}</th>
          </tr></thead>
          <tbody>
            <tr v-for="c in contracts" :key="c.id" class="border-b border-gray-50 hover:bg-gray-50/50">
              <td class="px-3 py-2 font-mono text-xs text-[#1BA97F]">{{ c.contract_number }}</td>
              <td class="px-3 py-2">
                <router-link :to="{ name: 'cases.show', params: { id: c.case_number?.replace('VB-','') } }" class="text-blue-600 hover:underline text-xs">
                  {{ c.case_number }}
                </router-link>
              </td>
              <td class="px-3 py-2 text-gray-700">{{ c.client_name }}</td>
              <td class="px-3 py-2 font-semibold">{{ fmtAmount(c.total_price) }}</td>
              <td class="px-3 py-2">
                <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', contractStatusClass(c.status)]">{{ contractStatusLabel(c.status) }}</span>
              </td>
              <td class="px-3 py-2 text-gray-400 text-xs">{{ c.created_at?.slice(0,10) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!contracts.length" class="text-sm text-gray-400 text-center py-6">{{ t('crm.finance.noData') }}</div>
    </div>

    <!-- === ЖУРНАЛ === -->
    <div v-else-if="activeTab === 'audit'" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-700 mb-4">{{ t('crm.finance.auditTitle') }}</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead><tr class="text-left border-b border-gray-200">
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.date') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.auditAction') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.auditUser') }}</th>
            <th class="px-3 py-2 font-medium text-gray-500">{{ t('crm.finance.auditDetails') }}</th>
          </tr></thead>
          <tbody>
            <tr v-for="log in auditLogs" :key="log.id" class="border-b border-gray-50">
              <td class="px-3 py-2 text-gray-400 text-xs whitespace-nowrap">{{ log.created_at?.slice(0,16)?.replace('T',' ') }}</td>
              <td class="px-3 py-2"><span class="text-xs font-mono bg-gray-100 px-1.5 py-0.5 rounded">{{ log.action }}</span></td>
              <td class="px-3 py-2 text-gray-700 text-xs">{{ log.user_name || '—' }}</td>
              <td class="px-3 py-2 text-gray-500 text-xs">{{ formatAuditContext(log.context) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!auditLogs.length" class="text-sm text-gray-400 text-center py-6">{{ t('crm.finance.noData') }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { useCountries } from '@/composables/useCountries';
import SearchSelect from '@/components/SearchSelect.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const { countryName, countryFlag } = useCountries();

const activeTab = ref('overview');
const loading = ref(false);

// Данные
const overview = ref({
  total_revenue: 0, debt_amount: 0, refunds_total: 0, net_revenue: 0,
  overdue_count: 0, blocked_count: 0,
  by_payment_status: {}, by_method: {},
});
const payments = ref({ data: [], current_page: 1, last_page: 1 });
const debts = ref([]);
const byManager = ref([]);
const byCountry = ref([]);
const contracts = ref([]);
const contractFilter = ref({ status: '', client: '' });
const auditLogs = ref([]);

const contractStatuses = computed(() => [
  { value: 'draft', label: t('crm.finance.csDraft') },
  { value: 'generated', label: t('crm.finance.csGenerated') },
  { value: 'sent', label: t('crm.finance.csSent') },
  { value: 'confirmed', label: t('crm.finance.csConfirmed') },
  { value: 'signed', label: t('crm.finance.csSigned') },
  { value: 'cancelled', label: t('crm.finance.csCancelled') },
  { value: 'terminated', label: t('crm.finance.csTerminated') },
]);

function contractStatusLabel(s) {
  const map = { draft: t('crm.finance.csDraft'), generated: t('crm.finance.csGenerated'), sent: t('crm.finance.csSent'), confirmed: t('crm.finance.csConfirmed'), signed: t('crm.finance.csSigned'), cancelled: t('crm.finance.csCancelled'), terminated: t('crm.finance.csTerminated') };
  return map[s] || s;
}
function contractStatusClass(s) {
  const map = { draft: 'bg-gray-100 text-gray-600', generated: 'bg-blue-50 text-blue-600', sent: 'bg-amber-50 text-amber-600', confirmed: 'bg-emerald-50 text-emerald-600', signed: 'bg-green-100 text-green-700', cancelled: 'bg-red-50 text-red-600', terminated: 'bg-red-100 text-red-700' };
  return map[s] || 'bg-gray-100 text-gray-600';
}
function formatAuditContext(ctx) {
  if (!ctx) return '';
  if (typeof ctx === 'string') try { ctx = JSON.parse(ctx); } catch { return ctx; }
  const parts = [];
  if (ctx.amount) parts.push(fmtAmount(ctx.amount));
  if (ctx.method) parts.push(ctx.method);
  if (ctx.reason) parts.push(ctx.reason);
  if (ctx.case_id) parts.push('case: ' + ctx.case_id.slice(0,8));
  return parts.join(' | ') || JSON.stringify(ctx).slice(0,80);
}

// Фильтры платежей
const filters = ref({ method: '', from: '', to: '', client: '' });

const tabs = computed(() => [
  { key: 'overview',  label: t('crm.finance.tabOverview') },
  { key: 'payments',  label: t('crm.finance.tabPayments') },
  { key: 'contracts', label: t('crm.finance.tabContracts') },
  { key: 'debts',     label: t('crm.finance.tabDebts') },
  { key: 'analytics', label: t('crm.finance.tabAnalytics') },
  { key: 'audit',     label: t('crm.finance.tabAudit') },
]);

const methodOptions = computed(() => [
  { value: 'cash',          label: t('crm.finance.cash') },
  { value: 'terminal',      label: t('crm.finance.terminal') },
  { value: 'bank_transfer', label: t('crm.finance.bankTransfer') },
  { value: 'online',        label: t('crm.finance.online') },
]);

const methodLabelsMap = computed(() => ({
  cash: t('crm.finance.cash'),
  terminal: t('crm.finance.terminal'),
  bank_transfer: t('crm.finance.bankTransfer'),
  online: t('crm.finance.online'),
}));
function methodLabel(m) { return methodLabelsMap.value[m] ?? m ?? '--'; }

// Обзор — статусы оплаты
const paymentStatusItems = computed(() => {
  const s = overview.value.by_payment_status || {};
  return [
    { key: 'paid',      label: t('crm.finance.fullyPaid'),   count: s.paid ?? 0,      barColor: 'bg-green-500' },
    { key: 'partial',   label: t('crm.finance.partialPaid'), count: s.partial ?? 0,   barColor: 'bg-yellow-500' },
    { key: 'unpaid',    label: t('crm.finance.unpaid'),      count: s.unpaid ?? 0,    barColor: 'bg-red-400' },
    { key: 'cancelled', label: t('crm.finance.cancelled'),   count: s.cancelled ?? 0, barColor: 'bg-gray-400' },
  ];
});
const maxPaymentStatus = computed(() => Math.max(...paymentStatusItems.value.map(i => i.count), 1));

// Обзор — методы оплаты
const methodItems = computed(() => {
  const m = overview.value.by_method || {};
  return [
    { key: 'cash',          label: t('crm.finance.cash'),         amount: m.cash ?? 0 },
    { key: 'terminal',      label: t('crm.finance.terminal'),     amount: m.terminal ?? 0 },
    { key: 'bank_transfer', label: t('crm.finance.bankTransfer'), amount: m.bank_transfer ?? 0 },
    { key: 'online',        label: t('crm.finance.online'),       amount: m.online ?? 0 },
  ];
});
const maxMethodAmount = computed(() => Math.max(...methodItems.value.map(i => i.amount), 1));

// Аналитика — максимумы для баров
const maxManagerAmount = computed(() => byManager.value.length ? Math.max(...byManager.value.map(r => r.total_amount)) : 1);
const maxCountryAmount = computed(() => byCountry.value.length ? Math.max(...byCountry.value.map(r => r.total_amount)) : 1);

// Форматирование
function fmtAmount(val) {
  return Number(val || 0).toLocaleString('ru-RU');
}

function formatDate(d) {
  if (!d) return '--';
  return new Date(d).toLocaleDateString('ru-RU');
}

// Загрузка данных
async function loadOverview() {
  const res = await api.get('/finance/overview');
  overview.value = res.data.data;
}

async function loadPayments(page = 1) {
  const params = { page };
  if (filters.value.method) params.method = filters.value.method;
  if (filters.value.from) params.from = filters.value.from;
  if (filters.value.to) params.to = filters.value.to;
  if (filters.value.client) params.client = filters.value.client;
  const res = await api.get('/finance/payments', { params });
  payments.value = res.data.data ?? res.data;
}

async function loadDebts() {
  const res = await api.get('/finance/debts');
  debts.value = res.data.data;
}

async function loadByManager() {
  const res = await api.get('/finance/by-manager');
  byManager.value = res.data.data;
}

async function loadByCountry() {
  const res = await api.get('/finance/by-country');
  byCountry.value = res.data.data;
}

async function loadContracts() {
  const params = {};
  if (contractFilter.value.status) params.status = contractFilter.value.status;
  if (contractFilter.value.client) params.client = contractFilter.value.client;
  const res = await api.get('/finance/contracts', { params });
  contracts.value = res.data.data?.data || res.data.data || [];
}

async function loadAuditLog() {
  const res = await api.get('/finance/audit-log');
  auditLogs.value = res.data.data?.data || res.data.data || [];
}

async function loadTab(tab) {
  loading.value = true;
  try {
    if (tab === 'overview') {
      await loadOverview();
    } else if (tab === 'payments') {
      await loadPayments();
    } else if (tab === 'contracts') {
      await loadContracts();
    } else if (tab === 'debts') {
      await loadDebts();
    } else if (tab === 'analytics') {
      await Promise.all([loadByManager(), loadByCountry()]);
    } else if (tab === 'audit') {
      await loadAuditLog();
    }
  } catch { /* ignore */ } finally {
    loading.value = false;
  }
}

watch(activeTab, (tab) => loadTab(tab));
onMounted(() => loadTab('overview'));
</script>

<template>
  <div class="space-y-5">

    <!-- Фильтры + кнопка создания -->
    <div class="flex flex-wrap gap-3 items-center">
      <input v-model="search" @input="debouncedLoad"
        :placeholder="t('owner.agencies.searchPlaceholder')"
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#1BA97F] w-64" />

      <SearchSelect v-model="filterPlan" @change="load" compact
        :items="planOptions" allow-all :all-label="t('owner.agencies.allPlans')" />

      <SearchSelect v-model="filterStatus" @change="load" compact
        :items="filterStatusOptions" allow-all :all-label="t('owner.agencies.allStatuses')" />

      <button @click="openCreateAgency"
        class="ml-auto px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl
               hover:bg-[#0d2a5e] transition-colors">
        {{ t('owner.agencies.createAgency') }}
      </button>
    </div>

    <!-- Таблица -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
          <tr>
            <th class="px-5 py-3 text-left font-medium">{{ t('owner.agencies.thAgency') }}</th>
            <th class="px-4 py-3 text-left font-medium">{{ t('owner.agencies.thPlan') }}</th>
            <th class="px-4 py-3 text-right font-medium">{{ t('owner.agencies.thManagers') }}</th>
            <th class="px-4 py-3 text-right font-medium">{{ t('owner.agencies.thLeads') }}</th>
            <th class="px-4 py-3 text-right font-medium">{{ t('owner.agencies.thCommission') }}</th>
            <th class="px-4 py-3 text-center font-medium">{{ t('owner.agencies.thStatus') }}</th>
            <th class="px-4 py-3 text-center font-medium">{{ t('owner.agencies.thActions') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading" v-for="i in 5" :key="i">
            <td colspan="7" class="px-5 py-4">
              <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
            </td>
          </tr>
          <tr v-else-if="!agencies.length">
            <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-400">
              {{ t('owner.agencies.notFound') }}
            </td>
          </tr>
          <tr v-else v-for="a in agencies" :key="a.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 bg-[#0A1F44]/10 rounded-lg flex items-center justify-center
                            text-xs font-bold text-[#0A1F44] shrink-0">
                  {{ a.name?.[0]?.toUpperCase() ?? '?' }}
                </div>
                <div>
                  <div class="font-medium text-gray-800">{{ a.name }}</div>
                  <div class="text-xs text-gray-400">{{ a.email }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-1 rounded-full font-medium"
                :class="planClass(a.plan)">{{ a.plan }}</span>
            </td>
            <td class="px-4 py-3 text-right text-gray-700">{{ a.managers_count ?? '—' }}</td>
            <td class="px-4 py-3 text-right text-gray-700">{{ a.leads_count ?? 0 }}</td>
            <td class="px-4 py-3 text-right text-gray-700">{{ a.commission_rate ?? 10 }}%</td>
            <td class="px-4 py-3 text-center">
              <span :class="a.is_active ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50'"
                class="text-xs px-2 py-1 rounded-full font-medium">
                {{ a.is_active ? t('owner.agencies.active') : t('owner.agencies.blocked') }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <button @click="openEdit(a)"
                  class="text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg
                         hover:bg-gray-50 text-gray-600 transition-colors">
                  {{ t('common.edit') }}
                </button>
                <button @click="toggleBlock(a)"
                  class="text-xs px-2.5 py-1.5 rounded-lg transition-colors"
                  :class="a.is_active
                    ? 'border border-red-200 text-red-500 hover:bg-red-50'
                    : 'border border-green-200 text-green-600 hover:bg-green-50'">
                  {{ a.is_active ? t('owner.agencies.block') : t('owner.agencies.unblock') }}
                </button>
                <button @click="confirmDelete(a)"
                  class="text-xs px-2.5 py-1.5 border border-red-200 rounded-lg
                         text-red-500 hover:bg-red-50 transition-colors">
                  {{ t('common.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Пагинация -->
      <div v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
        <span>{{ t('owner.agencies.total', { count: pagination.total }) }}</span>
        <div class="flex gap-1">
          <button v-for="p in pagination.last_page" :key="p"
            @click="page = p; load()"
            class="w-7 h-7 rounded-lg"
            :class="p === pagination.current_page
              ? 'bg-[#0A1F44] text-white font-bold'
              : 'hover:bg-gray-100 text-gray-600'">
            {{ p }}
          </button>
        </div>
      </div>
    </div>

    <!-- Модал редактирования -->
    <div v-if="editingAgency" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-5">{{ editingAgency.name }}</h3>

        <div class="space-y-4">
          <!-- Тариф -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.plan') }}</label>
              <SearchSelect v-model="editForm.plan" :items="editPlanOptions" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.commissionPercent') }}</label>
              <input v-model.number="editForm.commission_rate" type="number" min="0" max="50" step="0.5"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            </div>
          </div>

          <!-- Срок плана -->
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.planExpiry') }}</label>
            <input v-model="editForm.plan_expires_at" type="date"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
          </div>

          <!-- Описание -->
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.description') }}</label>
            <textarea v-model="editForm.description" rows="3" maxlength="1000"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] resize-none"
              :placeholder="t('owner.agencies.descriptionPlaceholder')"></textarea>
            <p class="text-right text-xs text-gray-400 mt-0.5">{{ (editForm.description || '').length }} / 1000</p>
          </div>

          <!-- Флаги -->
          <div class="flex flex-wrap gap-4">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="editForm.is_verified"
                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
              {{ t('owner.agencies.verified') }}
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="editForm.is_active"
                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
              {{ t('owner.agencies.activeFlag') }}
            </label>
          </div>

          <p v-if="editError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ editError }}</p>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="saveEdit" :disabled="saving"
            class="flex-1 py-3 bg-[#0A1F44] text-white font-semibold rounded-xl
                   hover:bg-[#0d2a5e] transition-colors disabled:opacity-60">
            {{ saving ? t('owner.agencies.saving') : t('common.save') }}
          </button>
          <button @click="editingAgency = null"
            class="px-5 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Модал создания агентства -->
    <div v-if="showCreate" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-5">{{ t('owner.agencies.createTitle') }}</h3>

        <div class="space-y-4">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.agencyName') }}</label>
            <input v-model="createForm.agency_name" maxlength="100"
              placeholder="Visa Expert Tashkent"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"
              :class="createErrors.agency_name ? 'border-red-400' : ''" />
            <p v-if="createErrors.agency_name" class="text-xs text-red-600 mt-1">{{ createErrors.agency_name }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.ownerName') }}</label>
            <input v-model="createForm.name" maxlength="80"
              placeholder="Islom Karimov"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"
              :class="createErrors.name ? 'border-red-400' : ''" />
            <p v-if="createErrors.name" class="text-xs text-red-600 mt-1">{{ createErrors.name }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.email') }}</label>
            <input v-model="createForm.email" type="email"
              placeholder="owner@agency.com"
              @blur="validateCreateEmail"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"
              :class="createErrors.email ? 'border-red-400' : ''" />
            <p v-if="createErrors.email" class="text-xs text-red-600 mt-1">{{ createErrors.email }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.phone') }}</label>
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:border-[#1BA97F]">
              <span class="px-3 py-2 bg-gray-50 text-gray-500 text-sm border-r border-gray-200 font-mono">+998</span>
              <input v-model="createPhoneDigits" @input="formatCreatePhone"
                type="tel" inputmode="numeric" maxlength="12"
                placeholder="97 123 45 67"
                class="flex-1 px-3 py-2 text-sm outline-none font-mono tracking-wider" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.plan') }}</label>
              <SearchSelect v-model="createForm.plan" :items="planOptions" />
            </div>
            <div>
              <label class="text-xs text-gray-500 mb-1 block">{{ t('owner.agencies.password') }}</label>
              <input v-model="createForm.password" type="password"
                :placeholder="t('owner.agencies.passwordHint')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"
                :class="createErrors.password ? 'border-red-400' : ''" />
              <p v-if="createErrors.password" class="text-xs text-red-600 mt-1">{{ createErrors.password }}</p>
            </div>
          </div>
          <p v-if="createError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ createError }}</p>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="submitCreate" :disabled="creating"
            class="flex-1 py-3 bg-[#0A1F44] text-white font-semibold rounded-xl
                   hover:bg-[#0d2a5e] transition-colors disabled:opacity-60">
            {{ creating ? t('owner.agencies.creating') : t('owner.agencies.create') }}
          </button>
          <button @click="showCreate = false"
            class="px-5 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            {{ t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const agencies    = ref([]);
const loading     = ref(true);
const saving      = ref(false);
const search      = ref('');
const filterPlan  = ref('');
const filterStatus = ref('');
const page        = ref(1);
const pagination  = ref({ last_page: 1, current_page: 1, total: 0 });

const editingAgency = ref(null);
const editForm      = ref({});
const editError     = ref('');

const showCreate = ref(false);
const creating   = ref(false);
const createError = ref('');
const createErrors = ref({});
const createPhoneDigits = ref('');
const createForm = reactive({
  agency_name: '', name: '', email: '', phone: '', plan: 'trial', password: '',
});

const planOptions = [
  { value: 'trial', label: 'Trial' },
  { value: 'starter', label: 'Starter' },
  { value: 'pro', label: 'Pro' },
  { value: 'enterprise', label: 'Enterprise' },
];

const editPlanOptions = computed(() => [
  { value: 'trial', label: t('owner.agencies.planTrial') },
  { value: 'starter', label: t('owner.agencies.planStarter') },
  { value: 'pro', label: t('owner.agencies.planPro') },
  { value: 'enterprise', label: t('owner.agencies.planEnterprise') },
]);

const filterStatusOptions = computed(() => [
  { value: 'active', label: t('owner.agencies.activeStatus') },
  { value: 'inactive', label: t('owner.agencies.blockedStatus') },
]);

let debounceTimer = null;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { page.value = 1; load(); }, 400);
}

function planClass(plan) {
  return {
    trial:      'bg-gray-100 text-gray-600',
    starter:    'bg-blue-50 text-blue-700',
    pro:        'bg-purple-50 text-purple-700',
    enterprise: 'bg-amber-50 text-amber-700',
  }[plan] ?? 'bg-gray-50 text-gray-500';
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test((email || '').trim());
}

function validateCreateEmail() {
  if (createForm.email && !isValidEmail(createForm.email)) {
    createErrors.value.email = t('owner.agencies.enterValidEmail');
  } else {
    delete createErrors.value.email;
  }
}

function formatCreatePhone(e) {
  const raw = e.target.value.replace(/\D/g, '').slice(0, 9);
  let fmt = '';
  if (raw.length > 0) fmt += raw.slice(0, 2);
  if (raw.length > 2) fmt += ' ' + raw.slice(2, 5);
  if (raw.length > 5) fmt += ' ' + raw.slice(5, 7);
  if (raw.length > 7) fmt += ' ' + raw.slice(7, 9);
  createPhoneDigits.value = fmt;
  createForm.phone = raw.length === 9 ? `+998${raw}` : '';
}

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/owner/agencies', {
      params: { search: search.value, plan: filterPlan.value, status: filterStatus.value, page: page.value },
    });
    agencies.value   = data.data.data;
    pagination.value = { last_page: data.data.last_page, current_page: data.data.current_page, total: data.data.total };
  } finally {
    loading.value = false;
  }
}

function openEdit(a) {
  editingAgency.value = a;
  editError.value = '';
  editForm.value = {
    plan:             a.plan,
    commission_rate:  a.commission_rate ?? 10,
    is_verified:      a.is_verified ?? false,
    is_active:        a.is_active ?? true,
    description:      a.description ?? '',
    plan_expires_at:  a.plan_expires_at ? a.plan_expires_at.slice(0, 10) : '',
  };
}

async function saveEdit() {
  saving.value = true;
  editError.value = '';
  try {
    await api.patch(`/owner/agencies/${editingAgency.value.id}`, editForm.value);
    editingAgency.value = null;
    load();
  } catch (err) {
    editError.value = err.response?.data?.message || t('owner.agencies.saveError');
  } finally {
    saving.value = false;
  }
}

async function toggleBlock(a) {
  await api.patch(`/owner/agencies/${a.id}`, { is_active: !a.is_active });
  a.is_active = !a.is_active;
}

async function confirmDelete(a) {
  if (!confirm(`${t('owner.agencies.confirmDeleteTitle', { name: a.name })}\n\n${t('owner.agencies.confirmDeleteMessage')}`)) return;
  await api.delete(`/owner/agencies/${a.id}`);
  load();
}

function openCreateAgency() {
  Object.assign(createForm, { agency_name: '', name: '', email: '', phone: '', plan: 'trial', password: '' });
  createPhoneDigits.value = '';
  createErrors.value = {};
  createError.value = '';
  showCreate.value = true;
}

async function submitCreate() {
  createErrors.value = {};
  createError.value = '';

  if (!createForm.agency_name.trim()) { createErrors.value.agency_name = t('owner.agencies.enterAgencyName'); return; }
  if (!createForm.name.trim()) { createErrors.value.name = t('owner.agencies.enterOwnerName'); return; }
  if (!isValidEmail(createForm.email)) { createErrors.value.email = t('owner.agencies.enterValidEmail'); return; }
  if (!createForm.password || createForm.password.length < 8) {
    createErrors.value.password = t('owner.agencies.minChars'); return;
  }

  creating.value = true;
  try {
    await api.post('/auth/register', createForm);
    showCreate.value = false;
    load();
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      createErrors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      createError.value = d?.message || t('owner.agencies.createError');
    }
  } finally {
    creating.value = false;
  }
}

onMounted(load);
</script>

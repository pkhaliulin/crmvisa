<template>
  <div class="space-y-5">

    <!-- Фильтры -->
    <div class="flex flex-wrap gap-3 items-center">
      <input v-model="search" @input="debouncedLoad"
        placeholder="Поиск по имени или email..."
        class="border border-gray-200 rounded-xl px-4 py-2 text-sm outline-none focus:border-[#1BA97F] w-72" />

      <select v-model="filterRole" @change="load"
        class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
        <option value="">Все роли</option>
        <option value="owner">Владельцы</option>
        <option value="manager">Менеджеры</option>
        <option value="partner">Партнёры</option>
      </select>

      <select v-model="filterStatus" @change="load"
        class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
        <option value="">Все статусы</option>
        <option value="active">Активные</option>
        <option value="inactive">Заблокированные</option>
      </select>

      <button @click="openCreate"
        class="ml-auto px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl
               hover:bg-[#0d2a5e] transition-colors">
        + Создать пользователя
      </button>
    </div>

    <!-- Таблица -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
          <tr>
            <th class="px-5 py-3 text-left">Пользователь</th>
            <th class="px-4 py-3 text-left">Агентство</th>
            <th class="px-4 py-3 text-center">Роль</th>
            <th class="px-4 py-3 text-center">Статус</th>
            <th class="px-4 py-3 text-left">Дата регистрации</th>
            <th class="px-4 py-3 text-center">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-if="loading" v-for="i in 8" :key="i">
            <td colspan="6" class="px-5 py-4">
              <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
            </td>
          </tr>
          <tr v-else-if="!users.length">
            <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">
              Пользователи не найдены
            </td>
          </tr>
          <tr v-else v-for="u in users" :key="u.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-5 py-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-full bg-[#0A1F44]/10 flex items-center justify-center
                            text-xs font-bold text-[#0A1F44] shrink-0">
                  {{ u.name?.[0]?.toUpperCase() ?? '?' }}
                </div>
                <div>
                  <div class="font-medium text-gray-800">{{ u.name }}</div>
                  <div class="text-xs text-gray-400">{{ u.email }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-600 text-xs">
              {{ u.agency_name || '—' }}
            </td>
            <td class="px-4 py-3 text-center">
              <span class="text-xs px-2 py-1 rounded-full font-medium"
                :class="roleClass(u.role)">
                {{ roleLabel(u.role) }}
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="u.is_active ? 'text-green-700 bg-green-50' : 'text-red-600 bg-red-50'"
                class="text-xs px-2 py-1 rounded-full font-medium">
                {{ u.is_active ? 'Активен' : 'Заблок.' }}
              </span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-400">
              {{ new Date(u.created_at).toLocaleDateString('ru-RU') }}
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-2">
                <button @click="toggleBlock(u)"
                  class="text-xs px-3 py-1.5 rounded-lg transition-colors"
                  :class="u.is_active
                    ? 'border border-red-200 text-red-500 hover:bg-red-50'
                    : 'border border-green-200 text-green-600 hover:bg-green-50'">
                  {{ u.is_active ? 'Заблокировать' : 'Разблокировать' }}
                </button>
                <button @click="resetPassword(u)"
                  class="text-xs px-3 py-1.5 border border-gray-200 rounded-lg
                         hover:bg-gray-50 text-gray-600 transition-colors">
                  Сбросить пароль
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Пагинация -->
      <div v-if="pagination.last_page > 1"
        class="px-5 py-3 border-t border-gray-50 flex items-center justify-between text-xs text-gray-500">
        <span>Всего: {{ pagination.total }}</span>
        <div class="flex gap-1">
          <button v-for="p in Math.min(pagination.last_page, 10)" :key="p"
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

    <!-- Модал создания пользователя -->
    <div v-if="showCreate" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-5">Создать CRM-пользователя</h3>

        <div class="space-y-3">
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Имя *</label>
            <input v-model="createForm.name" maxlength="80" placeholder="Анвар Исмоилов"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            <p v-if="createErrors.name" class="text-xs text-red-600 mt-1">{{ createErrors.name }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Email *</label>
            <input v-model="createForm.email" type="email" placeholder="user@agency.com"
              @blur="validateCreateEmail"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]"
              :class="createErrors.email ? 'border-red-400' : ''" />
            <p v-if="createErrors.email" class="text-xs text-red-600 mt-1">{{ createErrors.email }}</p>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Телефон</label>
            <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden focus-within:border-[#1BA97F]">
              <span class="px-3 py-2 bg-gray-50 text-gray-500 text-sm border-r border-gray-200 font-mono">+998</span>
              <input v-model="phoneDigits" @input="formatPhone"
                type="tel" inputmode="numeric" maxlength="12"
                placeholder="97 123 45 67"
                class="flex-1 px-3 py-2 text-sm outline-none font-mono tracking-wider" />
            </div>
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Роль *</label>
            <select v-model="createForm.role"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
              <option value="manager">Менеджер</option>
              <option value="owner">Владелец агентства</option>
              <option value="superadmin">Суперадмин</option>
            </select>
          </div>
          <div v-if="createForm.role !== 'superadmin'">
            <label class="text-xs text-gray-500 mb-1 block">ID Агентства</label>
            <input v-model="createForm.agency_id" placeholder="UUID агентства (необязательно)"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] font-mono text-xs" />
          </div>
          <div>
            <label class="text-xs text-gray-500 mb-1 block">Пароль *</label>
            <input v-model="createForm.password" type="password" placeholder="Минимум 8 символов"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
            <p v-if="createErrors.password" class="text-xs text-red-600 mt-1">{{ createErrors.password }}</p>
          </div>
          <p v-if="createError" class="text-xs text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ createError }}</p>
        </div>

        <div class="flex gap-3 mt-6">
          <button @click="submitCreate" :disabled="creating"
            class="flex-1 py-3 bg-[#0A1F44] text-white font-semibold rounded-xl
                   hover:bg-[#0d2a5e] transition-colors disabled:opacity-60">
            {{ creating ? 'Создаём...' : 'Создать' }}
          </button>
          <button @click="showCreate = false"
            class="px-5 py-3 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            Отмена
          </button>
        </div>
      </div>
    </div>

    <!-- Модал сброса пароля -->
    <div v-if="resetTarget" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-[#0A1F44] text-lg mb-2">Сбросить пароль</h3>
        <p class="text-sm text-gray-600 mb-4">
          Новый пароль для <strong>{{ resetTarget.name }}</strong>
        </p>
        <input v-model="newPassword" type="text" placeholder="Введите новый пароль"
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] mb-4" />
        <p v-if="resetError" class="text-xs text-red-600 mb-3">{{ resetError }}</p>
        <div class="flex gap-3">
          <button @click="submitReset" :disabled="resetting"
            class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl
                   hover:bg-[#0d2a5e] disabled:opacity-60 transition-colors">
            {{ resetting ? 'Сохраняем...' : 'Сохранить' }}
          </button>
          <button @click="resetTarget = null"
            class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
            Отмена
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/api/index';

const users      = ref([]);
const loading    = ref(true);
const search     = ref('');
const filterRole = ref('');
const filterStatus = ref('');
const page       = ref(1);
const pagination = ref({ last_page: 1, current_page: 1, total: 0 });

// Create
const showCreate   = ref(false);
const creating     = ref(false);
const createError  = ref('');
const createErrors = ref({});
const phoneDigits  = ref('');
const createForm   = reactive({
  name: '', email: '', phone: '', role: 'manager', password: '', agency_id: '',
});

// Reset
const resetTarget  = ref(null);
const newPassword  = ref('');
const resetting    = ref(false);
const resetError   = ref('');

const roleLabels = { owner: 'Владелец', manager: 'Менеджер', partner: 'Партнёр', superadmin: 'Суперадмин' };
const roleLabel  = (r) => roleLabels[r] ?? r;
const roleClass  = (r) => ({
  owner:      'bg-purple-50 text-purple-700',
  manager:    'bg-blue-50 text-blue-700',
  partner:    'bg-gray-100 text-gray-600',
  superadmin: 'bg-red-50 text-red-700',
}[r] ?? 'bg-gray-50 text-gray-500');

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

function validateCreateEmail() {
  if (createForm.email && !isValidEmail(createForm.email)) {
    createErrors.value.email = 'Введите корректный email';
  } else {
    delete createErrors.value.email;
  }
}

function formatPhone(e) {
  const raw = e.target.value.replace(/\D/g, '').slice(0, 9);
  let fmt = '';
  if (raw.length > 0) fmt += raw.slice(0, 2);
  if (raw.length > 2) fmt += ' ' + raw.slice(2, 5);
  if (raw.length > 5) fmt += ' ' + raw.slice(5, 7);
  if (raw.length > 7) fmt += ' ' + raw.slice(7, 9);
  phoneDigits.value = fmt;
  createForm.phone = raw.length === 9 ? `+998${raw}` : '';
}

let debounceTimer = null;
function debouncedLoad() {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => { page.value = 1; load(); }, 400);
}

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/owner/crm-users', {
      params: { search: search.value, role: filterRole.value, status: filterStatus.value, page: page.value },
    });
    users.value      = data.data.data ?? data.data ?? [];
    pagination.value = {
      last_page:    data.data.last_page    ?? 1,
      current_page: data.data.current_page ?? 1,
      total:        data.data.total        ?? users.value.length,
    };
  } finally {
    loading.value = false;
  }
}

function openCreate() {
  Object.assign(createForm, { name: '', email: '', phone: '', role: 'manager', password: '', agency_id: '' });
  phoneDigits.value = '';
  createErrors.value = {};
  createError.value = '';
  showCreate.value = true;
}

async function submitCreate() {
  createErrors.value = {};
  createError.value = '';

  if (!createForm.name.trim()) { createErrors.value.name = 'Введите имя'; return; }
  if (!isValidEmail(createForm.email)) { createErrors.value.email = 'Введите корректный email'; return; }
  if (!createForm.password || createForm.password.length < 8) {
    createErrors.value.password = 'Пароль должен быть минимум 8 символов'; return;
  }

  creating.value = true;
  try {
    await api.post('/owner/crm-users', createForm);
    showCreate.value = false;
    load();
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      createErrors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      createError.value = d?.message || 'Ошибка создания';
    }
  } finally {
    creating.value = false;
  }
}

async function toggleBlock(u) {
  await api.patch(`/owner/crm-users/${u.id}`, { is_active: !u.is_active });
  u.is_active = !u.is_active;
}

function resetPassword(u) {
  resetTarget.value = u;
  newPassword.value = '';
  resetError.value  = '';
}

async function submitReset() {
  if (!newPassword.value || newPassword.value.length < 8) {
    resetError.value = 'Пароль должен быть минимум 8 символов';
    return;
  }
  resetting.value = true;
  try {
    await api.patch(`/owner/crm-users/${resetTarget.value.id}`, { password: newPassword.value });
    resetTarget.value = null;
  } catch (err) {
    resetError.value = err.response?.data?.message || 'Ошибка при сбросе пароля';
  } finally {
    resetting.value = false;
  }
}

onMounted(load);
</script>

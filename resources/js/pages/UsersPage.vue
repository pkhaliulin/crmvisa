<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold text-gray-800">Сотрудники</h2>
      <AppButton @click="openCreate">+ Добавить сотрудника</AppButton>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm" v-if="users.length">
        <thead class="bg-gray-50 border-b text-gray-500 text-xs uppercase tracking-wide">
          <tr>
            <th class="text-left px-4 py-3">Имя</th>
            <th class="text-left px-4 py-3">Email</th>
            <th class="text-left px-4 py-3">Телефон</th>
            <th class="text-left px-4 py-3">Роль</th>
            <th class="text-left px-4 py-3">Статус</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ u.name }}</td>
            <td class="px-4 py-3 text-gray-500">{{ u.email }}</td>
            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ u.phone || '—' }}</td>
            <td class="px-4 py-3">
              <AppBadge :color="roleColor(u.role)">{{ roleLabel(u.role) }}</AppBadge>
            </td>
            <td class="px-4 py-3">
              <AppBadge :color="u.is_active ? 'green' : 'gray'">
                {{ u.is_active ? 'Активен' : 'Деактивирован' }}
              </AppBadge>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="deleteUser(u)" class="text-red-500 hover:text-red-700 text-xs font-medium transition-colors">
                Удалить
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="py-16 text-center text-gray-400">
        <div class="text-4xl mb-3">👥</div>
        <p class="text-sm">Сотрудников нет</p>
        <p class="text-xs mt-1 text-gray-300">Добавьте первого менеджера</p>
      </div>
    </div>
  </div>

  <!-- Модал создания -->
  <AppModal v-model="showCreate" title="Добавить сотрудника">
    <form @submit.prevent="createUser" class="space-y-4">
      <AppInput
        v-model="form.name"
        label="Имя"
        placeholder="Анвар Исмоилов"
        required
        :error="errors.name"
        :maxlength="80"
      />
      <AppInput
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="manager@agency.com"
        required
        :error="errors.email"
        @blur="validateEmail"
      />
      <AppPhoneInput
        v-model="form.phone"
        label="Телефон"
        :error="errors.phone"
      />
      <AppSelect v-model="form.role" label="Роль" :options="roleOptions" />
      <AppInput
        v-model="form.password"
        label="Пароль"
        type="password"
        placeholder="Минимум 8 символов"
        required
        :error="errors.password"
      />
      <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 p-2 rounded-lg">{{ errorMsg }}</p>
      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" type="button" @click="showCreate = false">Отмена</AppButton>
        <AppButton type="submit" :loading="createLoading">Создать</AppButton>
      </div>
    </form>
  </AppModal>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { usersApi } from '@/api/users';
import AppButton from '@/components/AppButton.vue';
import AppBadge from '@/components/AppBadge.vue';
import AppModal from '@/components/AppModal.vue';
import AppInput from '@/components/AppInput.vue';
import AppPhoneInput from '@/components/AppPhoneInput.vue';
import AppSelect from '@/components/AppSelect.vue';

const users         = ref([]);
const showCreate    = ref(false);
const createLoading = ref(false);
const errorMsg      = ref('');
const errors        = ref({});
const form = reactive({ name: '', email: '', phone: '', role: 'manager', password: '' });

const roleOptions = [
  { value: 'manager', label: 'Менеджер' },
  { value: 'partner', label: 'Партнёр (нотариус/переводчик)' },
];

const roleLabels = { owner: 'Владелец', manager: 'Менеджер', partner: 'Партнёр', superadmin: 'Суперадмин' };
const roleColors = { owner: 'purple', manager: 'blue', partner: 'gray', superadmin: 'red' };
const roleLabel  = (r) => roleLabels[r] ?? r;
const roleColor  = (r) => roleColors[r] ?? 'gray';

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

function validateEmail() {
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = 'Введите корректный email (например: name@domain.com)';
  } else {
    delete errors.value.email;
  }
}

function openCreate() {
  Object.assign(form, { name: '', email: '', phone: '', role: 'manager', password: '' });
  errors.value   = {};
  errorMsg.value = '';
  showCreate.value = true;
}

async function fetchUsers() {
  const { data } = await usersApi.list();
  users.value = data.data;
}

async function createUser() {
  errors.value   = {};
  errorMsg.value = '';

  if (!isValidEmail(form.email)) {
    errors.value.email = 'Введите корректный email';
    return;
  }

  createLoading.value = true;
  try {
    await usersApi.create(form);
    showCreate.value = false;
    await fetchUsers();
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = d?.message || 'Ошибка создания';
    }
  } finally {
    createLoading.value = false;
  }
}

async function deleteUser(u) {
  if (!confirm(`Удалить сотрудника "${u.name}"? Это действие нельзя отменить.`)) return;
  await usersApi.remove(u.id);
  await fetchUsers();
}

onMounted(fetchUsers);
</script>

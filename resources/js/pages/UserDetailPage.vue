<template>
  <div class="space-y-4">
    <!-- Назад -->
    <button @click="router.push({ name: 'users' })"
      class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
      </svg>
      {{ t('crm.userDetail.backToUsers') }}
    </button>

    <!-- Загрузка -->
    <div v-if="loading" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 animate-pulse">
      <div class="flex gap-4">
        <div class="w-24 h-24 bg-gray-200 rounded-xl"></div>
        <div class="flex-1 space-y-3">
          <div class="h-6 bg-gray-200 rounded w-48"></div>
          <div class="h-4 bg-gray-100 rounded w-64"></div>
          <div class="h-4 bg-gray-100 rounded w-40"></div>
        </div>
      </div>
    </div>

    <template v-else-if="user">
      <!-- Профиль -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex items-start gap-5">
          <!-- Аватар -->
          <label class="cursor-pointer shrink-0 group">
            <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onAvatarChange"/>
            <div class="relative w-24 h-24 rounded-xl overflow-hidden">
              <img v-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover"/>
              <div v-else class="w-full h-full bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white text-3xl font-bold">
                {{ user.name?.[0]?.toUpperCase() ?? '?' }}
              </div>
              <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
                </svg>
              </div>
            </div>
            <p class="text-[10px] text-gray-400 mt-1 text-center">{{ t('crm.userDetail.photoHint') }}</p>
          </label>

          <!-- Инфо -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 mb-2">
              <h2 class="text-xl font-bold text-[#0A1F44]">{{ user.name }}</h2>
              <AppBadge :color="roleColor(user.role)">{{ roleLabel(user.role) }}</AppBadge>
              <span class="text-xs px-2 py-0.5 rounded-full" :class="user.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                {{ user.is_active ? t('crm.users.active') : t('crm.userDetail.deactivated') }}
              </span>
            </div>
            <div class="space-y-1 text-sm text-gray-500">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
                {{ user.email }}
              </div>
              <div v-if="user.phone" class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                </svg>
                <a :href="`tel:${user.phone}`" class="hover:text-blue-600">{{ formatPhone(user.phone) }}</a>
              </div>
              <div v-if="user.telegram_username" class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#229ED9] shrink-0" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                </svg>
                <a :href="`https://t.me/${user.telegram_username}`" target="_blank" class="text-[#229ED9] hover:underline">@{{ user.telegram_username }}</a>
              </div>
            </div>
            <div class="flex gap-4 mt-3 text-xs text-gray-400">
              <span>Заявок: <strong class="text-[#0A1F44]">{{ user.cases_count ?? 0 }}</strong></span>
              <span>Активных: <strong class="text-[#1BA97F]">{{ user.active_cases_count ?? 0 }}</strong></span>
              <span>Добавлен: {{ user.created_at }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Редактирование -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-[#0A1F44] text-sm mb-4">{{ t('crm.userDetail.editData') }}</h3>
        <form @submit.prevent="saveUser" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.userDetail.fullName') }}</label>
              <input v-model="editForm.name" type="text"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.userDetail.email') }}</label>
              <input v-model="editForm.email" type="email"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.userDetail.phone') }}</label>
              <input v-model="editForm.phone" type="text" placeholder="+998901234567"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.userDetail.telegram') }}</label>
              <input v-model="editForm.telegram_username" type="text" placeholder="username"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
            </div>
            <div v-if="user.role !== 'owner'">
              <SearchSelect
                v-model="editForm.role"
                :items="roleItems"
                :label="t('crm.userDetail.role')"
              />
            </div>
          </div>
          <div class="flex items-center gap-3">
            <button type="submit" :disabled="saving"
              class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-50 transition-colors">
              {{ saving ? t('crm.userDetail.saving') : t('crm.userDetail.save') }}
            </button>
            <span v-if="saveMsg" class="text-xs text-[#1BA97F]">{{ saveMsg }}</span>
          </div>
        </form>
      </div>

      <!-- Сброс пароля -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-bold text-[#0A1F44] text-sm mb-4">{{ t('crm.userDetail.resetPassword') }}</h3>
        <form @submit.prevent="resetPassword" class="space-y-3">
          <div class="max-w-sm">
            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('crm.userDetail.newPassword') }}</label>
            <div class="relative">
              <input v-model="newPassword" :type="showNewPassword ? 'text' : 'password'"
                :placeholder="t('crm.userDetail.minChars')"
                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] pr-10"/>
              <button type="button" @click="showNewPassword = !showNewPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </button>
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm text-gray-500">
            <input type="checkbox" v-model="sendPasswordEmail" class="rounded border-gray-300"/>
            {{ t('crm.userDetail.sendToEmail', { email: user.email }) }}
          </label>
          <div class="flex items-center gap-3">
            <button type="submit" :disabled="resettingPassword || newPassword.length < 8"
              class="px-4 py-2 bg-amber-500 text-white text-sm font-semibold rounded-xl hover:bg-amber-600 disabled:opacity-50 transition-colors">
              {{ resettingPassword ? t('crm.userDetail.saving') : t('crm.userDetail.updatePassword') }}
            </button>
            <span v-if="passwordMsg" class="text-xs text-[#1BA97F]">{{ passwordMsg }}</span>
          </div>
        </form>
      </div>

      <!-- Удаление -->
      <div v-if="user.role !== 'owner'" class="bg-white rounded-2xl border border-red-100 shadow-sm p-5">
        <h3 class="font-bold text-red-600 text-sm mb-2">{{ t('crm.userDetail.deleteUser') }}</h3>
        <p class="text-xs text-gray-500 mb-3">{{ t('crm.userDetail.deleteHint') }}</p>
        <button @click="showDeleteModal = true"
          class="px-4 py-2 bg-red-500 text-white text-sm font-semibold rounded-xl hover:bg-red-600 transition-colors">
          {{ t('crm.userDetail.deleteBtn', { name: user.name }) }}
        </button>
      </div>
    </template>
  </div>

  <!-- Модал удаления -->
  <AppModal v-model="showDeleteModal" :title="t('crm.userDetail.deleteConfirm')">
    <p class="text-sm text-gray-600 mb-4" v-html="t('crm.userDetail.deleteConfirmMsg', { name: user?.name })"></p>
    <div class="flex gap-2 justify-end">
      <AppButton variant="outline" @click="showDeleteModal = false">{{ t('common.cancel') }}</AppButton>
      <AppButton color="red" @click="deleteUser" :loading="deleting">{{ t('common.confirmDeleteBtn') }}</AppButton>
    </div>
  </AppModal>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { usersApi } from '@/api/users';
import AppButton from '@/components/AppButton.vue';
import AppBadge from '@/components/AppBadge.vue';
import AppModal from '@/components/AppModal.vue';
import { formatPhone } from '@/utils/format';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();

const router = useRouter();
const route  = useRoute();

const loading  = ref(true);
const user     = ref(null);
const saving   = ref(false);
const saveMsg  = ref('');

const editForm = reactive({ name: '', email: '', phone: '', telegram_username: '', role: '' });

const newPassword       = ref('');
const showNewPassword   = ref(false);
const sendPasswordEmail = ref(true);
const resettingPassword = ref(false);
const passwordMsg       = ref('');

const showDeleteModal = ref(false);
const deleting        = ref(false);

const roleLabels = computed(() => ({
  owner: t('crm.roles.owner'),
  manager: t('crm.roles.manager'),
  partner: t('crm.roles.partner'),
  superadmin: t('crm.roles.superadmin'),
}));
const roleColors = { owner: 'purple', manager: 'blue', partner: 'gray', superadmin: 'red' };

const roleItems = computed(() => [
  { value: 'manager', label: t('crm.userDetail.manager') },
  { value: 'partner', label: t('crm.userDetail.partner') },
]);
const roleLabel  = (r) => roleLabels.value[r] ?? r;
const roleColor  = (r) => roleColors[r] ?? 'gray';

async function fetchUser() {
  loading.value = true;
  try {
    const { data } = await usersApi.show(route.params.id);
    user.value = data.data;
    Object.assign(editForm, {
      name: user.value.name,
      email: user.value.email,
      phone: user.value.phone || '',
      telegram_username: user.value.telegram_username || '',
      role: user.value.role,
    });
  } finally {
    loading.value = false;
  }
}

async function onAvatarChange(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { alert(t('crm.userDetail.maxSize')); return; }
  const fd = new FormData();
  fd.append('avatar', file);
  try {
    const { data } = await usersApi.updateAvatar(user.value.id, fd);
    user.value.avatar_url = data.data.avatar_url;
  } catch (e) {
    alert(t('crm.userDetail.uploadError'));
  }
}

async function saveUser() {
  saving.value = true;
  saveMsg.value = '';
  try {
    const payload = { ...editForm };
    if (!payload.phone) payload.phone = null;
    if (!payload.telegram_username) payload.telegram_username = null;
    const { data } = await usersApi.update(user.value.id, payload);
    Object.assign(user.value, data.data);
    saveMsg.value = t('crm.userDetail.saved');
    setTimeout(() => saveMsg.value = '', 3000);
  } catch (e) {
    alert(e.response?.data?.message || t('common.error'));
  } finally {
    saving.value = false;
  }
}

async function resetPassword() {
  resettingPassword.value = true;
  passwordMsg.value = '';
  try {
    await usersApi.resetPassword(user.value.id, {
      password: newPassword.value,
      send_email: sendPasswordEmail.value,
    });
    passwordMsg.value = sendPasswordEmail.value
      ? t('crm.userDetail.passwordUpdated')
      : t('crm.userDetail.passwordUpdatedShort');
    newPassword.value = '';
    setTimeout(() => passwordMsg.value = '', 5000);
  } catch (e) {
    alert(e.response?.data?.message || t('common.error'));
  } finally {
    resettingPassword.value = false;
  }
}

async function deleteUser() {
  deleting.value = true;
  try {
    await usersApi.remove(user.value.id);
    router.push({ name: 'users' });
  } catch (e) {
    alert(e.response?.data?.message || t('common.error'));
  } finally {
    deleting.value = false;
  }
}

onMounted(fetchUser);
</script>

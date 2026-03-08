<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <h2 class="text-lg font-semibold text-gray-800">{{ t('crm.users.title') }}</h2>
        <span class="text-sm text-gray-400">{{ t('crm.users.count', { n: users.length }) }}</span>
      </div>
      <AppButton @click="openCreate">{{ t('crm.users.addUser') }}</AppButton>
    </div>

    <!-- Cards grid -->
    <div v-if="users.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
      <div v-for="u in users" :key="u.id"
        class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:shadow-md transition-all cursor-pointer"
        @click="router.push({ name: 'users.show', params: { id: u.id } })">

        <!-- Header -->
        <div class="flex items-center gap-3 mb-3">
          <img v-if="u.avatar_url" :src="u.avatar_url" class="w-10 h-10 rounded-full object-cover shrink-0"/>
          <div v-else class="w-10 h-10 rounded-full bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white font-bold text-sm shrink-0">
            {{ u.name?.[0]?.toUpperCase() ?? '?' }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors truncate">{{ u.name }}</p>
            <p class="text-xs text-gray-400 truncate">{{ u.email }}</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <AppBadge :color="roleColor(u.role)">{{ roleLabel(u.role) }}</AppBadge>
          </div>
        </div>

        <!-- Info -->
        <div class="space-y-1.5">
          <!-- Contact -->
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
            </svg>
            <a v-if="u.phone" :href="`tel:${u.phone}`" @click.stop class="text-gray-600 hover:text-blue-600 text-xs font-mono">{{ formatPhone(u.phone) }}</a>
            <span v-else class="text-gray-300 text-xs">--</span>
          </div>

          <!-- Telegram -->
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
            </svg>
            <a v-if="u.telegram_username" :href="`https://t.me/${u.telegram_username}`" target="_blank" @click.stop
              class="text-[#229ED9] hover:underline text-xs">@{{ u.telegram_username }}</a>
            <span v-else class="text-gray-300 text-xs">--</span>
          </div>
        </div>

        <!-- Status toggle -->
        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between" @click.stop>
          <span class="text-xs" :class="u.is_active ? 'text-green-600' : 'text-gray-400'">
            {{ u.is_active ? t('crm.users.active') : t('crm.users.disabled') }}
          </span>
          <button v-if="u.role !== 'owner'"
            @click="toggleActive(u)"
            :disabled="toggling === u.id"
            class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200 focus:outline-none"
            :class="u.is_active ? 'bg-[#1BA97F]' : 'bg-gray-300'">
            <span class="inline-block h-3.5 w-3.5 rounded-full bg-white shadow-sm transition-transform duration-200"
              :class="u.is_active ? 'translate-x-[18px]' : 'translate-x-[3px]'"></span>
          </button>
          <span v-else class="text-xs text-gray-300">--</span>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="bg-white rounded-xl border border-gray-200 py-16 text-center text-gray-400">
      <svg class="w-12 h-12 mx-auto text-gray-200 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
      </svg>
      <p class="text-sm">{{ t('crm.users.empty') }}</p>
      <p class="text-xs mt-1 text-gray-300">{{ t('crm.users.emptyHint') }}</p>
    </div>
  </div>

  <!-- Модал создания -->
  <AppModal v-model="showCreate" :title="t('crm.users.addTitle')">
    <form @submit.prevent="createUser" class="space-y-4">
      <!-- Фото -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ t('crm.users.photo') }} <span class="text-red-500">*</span></label>
        <div class="flex items-start gap-3">
          <label class="cursor-pointer shrink-0">
            <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="onAvatarSelect"/>
            <div v-if="avatarPreview" class="relative w-20 h-20 rounded-xl overflow-hidden border-2 border-[#1BA97F] group">
              <img :src="avatarPreview" class="w-full h-full object-cover"/>
              <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
              </div>
            </div>
            <div v-else class="w-20 h-20 rounded-xl border-2 border-dashed border-gray-300 hover:border-[#1BA97F] flex flex-col items-center justify-center text-gray-400 hover:text-[#1BA97F] transition-colors">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/>
              </svg>
              <span class="text-[10px] mt-0.5">{{ t('crm.users.uploadPhoto') }}</span>
            </div>
          </label>
          <div class="relative flex-1">
            <div class="absolute -left-[6px] top-4 w-3 h-3 rotate-45 border-l border-b bg-blue-50 border-blue-200 z-10"></div>
            <div class="absolute left-[-1px] top-[17px] h-[10px] w-[2px] bg-blue-50 z-10"></div>
            <div class="relative rounded-2xl p-3 shadow-sm border bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200">
              <div class="flex items-center gap-1.5 mb-1">
                <div class="w-4 h-4 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0">
                  <span class="text-[6px] font-bold text-white">VB</span>
                </div>
                <span class="text-[9px] font-semibold text-blue-500">VisaBor</span>
              </div>
              <p class="text-[11px] text-[#0A1F44] leading-relaxed">{{ t('crm.users.photoTip', 'Фото повышает доверие клиентов на 40%. Рекомендуем деловое фото в белой рубашке -- так команда выглядит профессионально.') }}</p>
            </div>
          </div>
        </div>
        <p class="text-[10px] text-gray-400 mt-1">{{ t('crm.users.photoHint') }}</p>
        <p v-if="errors.avatar" class="text-xs text-red-500 mt-1">{{ errors.avatar }}</p>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.users.firstName') }} <span class="text-red-500">*</span></label>
          <input v-model="form.first_name" type="text" :placeholder="t('crm.users.firstNamePlaceholder')"
            class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
            :class="errors.first_name ? 'border-red-300' : 'border-gray-200 focus:border-[#1BA97F]'"
            @input="validateLatin('first_name')"/>
          <p v-if="errors.first_name" class="text-xs text-red-500 mt-1">{{ errors.first_name }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.users.lastName') }} <span class="text-red-500">*</span></label>
          <input v-model="form.last_name" type="text" :placeholder="t('crm.users.lastNamePlaceholder')"
            class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
            :class="errors.last_name ? 'border-red-300' : 'border-gray-200 focus:border-[#1BA97F]'"
            @input="validateLatin('last_name')"/>
          <p v-if="errors.last_name" class="text-xs text-red-500 mt-1">{{ errors.last_name }}</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.users.email') }} <span class="text-red-500">*</span></label>
        <input v-model="form.email" type="email" :placeholder="t('crm.users.emailPlaceholder')"
          class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
          :class="errors.email ? 'border-red-300' : emailValid === true ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'"
          @blur="validateEmail" @input="onEmailInput"/>
        <div class="flex items-center gap-1 mt-1">
          <svg v-if="emailValid === true" class="w-3.5 h-3.5 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          <p v-if="errors.email" class="text-xs text-red-500">{{ errors.email }}</p>
          <p v-else-if="emailValid === true" class="text-xs text-[#1BA97F]">{{ t('crm.users.emailValid') }}</p>
          <p v-else class="text-xs text-gray-400">{{ t('crm.users.passwordSentToEmail') }}</p>
        </div>
      </div>

      <AppPhoneInput v-model="form.phone" :label="t('crm.userDetail.phone')" :error="errors.phone"/>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.users.telegram') }}</label>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-400">@</span>
          <input v-model="form.telegram_username" type="text" placeholder="username"
            class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
        </div>
        <p v-if="errors.telegram_username" class="text-xs text-red-500 mt-1">{{ errors.telegram_username }}</p>
        <p v-else class="text-xs text-gray-400 mt-1">{{ t('crm.users.telegramHint') }}</p>
      </div>

      <AppSelect v-model="form.role" :label="t('crm.users.role')" :options="roleOptions" />

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('crm.users.password') }} <span class="text-red-500">*</span></label>
        <div class="relative">
          <input v-model="form.password" :type="showPassword ? 'text' : 'password'" :placeholder="t('crm.users.passwordHint')"
            class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors pr-10"
            :class="errors.password ? 'border-red-300' : 'border-gray-200 focus:border-[#1BA97F]'"/>
          <button type="button" @click="showPassword = !showPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
            <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
            </svg>
          </button>
        </div>
        <p v-if="errors.password" class="text-xs text-red-500 mt-1">{{ errors.password }}</p>
        <p v-else class="text-xs text-gray-400 mt-1">{{ t('crm.users.passwordSentHint') }}</p>
      </div>

      <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 p-2 rounded-lg">{{ errorMsg }}</p>

      <div class="flex gap-2 justify-end">
        <AppButton variant="outline" type="button" @click="showCreate = false">{{ t('common.cancel') }}</AppButton>
        <AppButton type="submit" :loading="createLoading">{{ t('crm.users.create') }}</AppButton>
      </div>
    </form>
  </AppModal>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { usersApi } from '@/api/users';
import AppButton from '@/components/AppButton.vue';
import AppBadge from '@/components/AppBadge.vue';
import AppModal from '@/components/AppModal.vue';
import AppPhoneInput from '@/components/AppPhoneInput.vue';
import AppSelect from '@/components/AppSelect.vue';
import { formatPhone } from '@/utils/format';

const { t } = useI18n();

const router = useRouter();
const users         = ref([]);
const showCreate    = ref(false);
const createLoading = ref(false);
const errorMsg      = ref('');
const errors        = ref({});
const showPassword  = ref(false);
const emailValid    = ref(null);
const avatarFile    = ref(null);
const avatarPreview = ref(null);
const toggling      = ref(null);

const form = reactive({
  first_name: '', last_name: '', email: '', phone: '',
  telegram_username: '', role: 'manager', password: '',
});

const roleOptions = computed(() => [
  { value: 'manager', label: t('crm.users.roleManager') },
  { value: 'partner', label: t('crm.users.rolePartner') },
]);

const roleLabels = computed(() => ({
  owner: t('crm.roles.owner'),
  manager: t('crm.roles.manager'),
  partner: t('crm.roles.partner'),
  superadmin: t('crm.roles.superadmin'),
}));
const roleColors = { owner: 'purple', manager: 'blue', partner: 'gray', superadmin: 'red' };
const roleLabel  = (r) => roleLabels.value[r] ?? r;
const roleColor  = (r) => roleColors[r] ?? 'gray';

const latinRegex = /^[A-Za-z\s\-']*$/;

function validateLatin(field) {
  const val = form[field];
  if (val && !latinRegex.test(val)) {
    errors.value[field] = t('crm.users.latinOnly');
    form[field] = val.replace(/[^A-Za-z\s\-']/g, '');
  } else {
    delete errors.value[field];
  }
}

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

function validateEmail() {
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = t('crm.users.invalidEmail');
    emailValid.value = false;
  } else if (form.email) {
    delete errors.value.email;
    emailValid.value = true;
  } else {
    emailValid.value = null;
  }
}

function onEmailInput() {
  if (emailValid.value !== null) {
    emailValid.value = null;
    delete errors.value.email;
  }
}

function onAvatarSelect(e) {
  const file = e.target.files?.[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    errors.value.avatar = t('crm.userDetail.maxSize');
    return;
  }
  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    errors.value.avatar = t('crm.users.photoFormat');
    return;
  }
  delete errors.value.avatar;
  avatarFile.value = file;
  avatarPreview.value = URL.createObjectURL(file);
}

function openCreate() {
  Object.assign(form, {
    first_name: '', last_name: '', email: '', phone: '',
    telegram_username: '', role: 'manager', password: '',
  });
  errors.value = {};
  errorMsg.value = '';
  emailValid.value = null;
  showPassword.value = false;
  avatarFile.value = null;
  avatarPreview.value = null;
  showCreate.value = true;
}

async function fetchUsers() {
  const { data } = await usersApi.list();
  users.value = data.data;
}

async function toggleActive(u) {
  toggling.value = u.id;
  try {
    await usersApi.update(u.id, { is_active: !u.is_active });
    u.is_active = !u.is_active;
  } catch (e) {
    // ignore
  } finally {
    toggling.value = null;
  }
}

async function createUser() {
  errors.value = {};
  errorMsg.value = '';

  if (!form.first_name.trim()) errors.value.first_name = t('crm.users.requiredField');
  if (!form.last_name.trim()) errors.value.last_name = t('crm.users.requiredField');
  if (!latinRegex.test(form.first_name)) errors.value.first_name = t('crm.users.latinOnly');
  if (!latinRegex.test(form.last_name)) errors.value.last_name = t('crm.users.latinOnly');
  if (!isValidEmail(form.email)) errors.value.email = t('crm.users.invalidEmail');
  if (form.password.length < 8) errors.value.password = t('crm.users.minPassword');
  if (!avatarFile.value) errors.value.avatar = t('crm.users.uploadPhotoReq');

  if (Object.keys(errors.value).length) return;

  const fd = new FormData();
  fd.append('first_name', form.first_name.trim());
  fd.append('last_name', form.last_name.trim());
  fd.append('email', form.email.trim());
  if (form.phone) fd.append('phone', form.phone);
  if (form.telegram_username) fd.append('telegram_username', form.telegram_username.replace(/^@/, ''));
  fd.append('role', form.role);
  fd.append('password', form.password);
  if (avatarFile.value) fd.append('avatar', avatarFile.value);

  createLoading.value = true;
  try {
    await usersApi.create(fd);
    showCreate.value = false;
    await fetchUsers();
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = d?.message || t('crm.users.createError');
    }
  } finally {
    createLoading.value = false;
  }
}

onMounted(fetchUsers);
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Настройки</h1>
      <p class="text-sm text-gray-500 mt-1">Профиль агентства и личные данные</p>
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

    <template v-else>
      <!-- ВКЛАДКА: Агентство -->
      <template v-if="activeTab === 'agency'">
        <!-- Общая информация -->
        <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Общая информация</h2>

          <AppTextarea v-model="form.description" label="Описание агентства (RU)"
            placeholder="Расскажите о вашем агентстве..." :maxlength="1000" :rows="4"
            hint="Отображается в профиле на маркетплейсе" />
          <AppTextarea v-model="form.description_uz" label="Agentlik haqida (UZ)"
            placeholder="Agentligingiz haqida aytib bering..." :maxlength="1000" :rows="4"
            hint="O'zbek tilidagi tavsif" />

          <div class="grid grid-cols-2 gap-4">
            <AppInput v-model="form.website_url" label="Сайт" type="url" placeholder="https://example.com" :maxlength="200" />
            <AppInput v-model="form.city" label="Город" type="text" placeholder="Ташкент" :maxlength="80" />
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Опыт работы (лет)</label>
              <input v-model.number="form.experience_years" type="number" min="0" max="100"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400" />
            </div>
          </div>
        </section>

        <!-- Команда -->
        <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Управление командой</h2>

          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
            <div>
              <p class="text-sm font-medium text-gray-700">Менеджеры видят все заявки</p>
              <p class="text-xs text-gray-500 mt-0.5">Если выключено -- каждый менеджер видит только свои заявки</p>
            </div>
            <button @click="form.managers_see_all_cases = !form.managers_see_all_cases"
              :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                form.managers_see_all_cases ? 'bg-blue-600' : 'bg-gray-300']">
              <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
                form.managers_see_all_cases ? 'translate-x-6' : 'translate-x-1']" />
            </button>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Авто-распределение лидов</label>
            <select v-model="form.lead_assignment_mode"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400">
              <option value="manual">Вручную</option>
              <option value="round_robin">По очереди (Round Robin)</option>
              <option value="by_workload">По загрузке</option>
              <option value="by_country">По стране</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Влияет на то, как новые лиды назначаются менеджерам</p>
          </div>
        </section>

        <!-- Рабочие направления -->
        <section class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Рабочие направления</h2>
          <p class="text-sm text-gray-500 mt-2">
            Управление рабочими направлениями перенесено в раздел
            <router-link :to="{ name: 'countries' }" class="text-blue-600 font-medium hover:underline">Страны</router-link>.
          </p>
        </section>

        <!-- Контакты -->
        <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Контакты агентства</h2>

          <div class="grid grid-cols-2 gap-4">
            <AppInput v-model="form.phone" label="Телефон" type="tel" placeholder="+998 90 123-45-67" :maxlength="30" />
            <AppInput v-model="form.email" label="Email агентства" type="email" placeholder="agency@example.com" :maxlength="150" />
          </div>

          <AppInput v-model="form.address" label="Адрес офиса" type="text" placeholder="Ул. Амира Тимура, 1, Ташкент" :maxlength="200" />

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Широта</label>
              <input v-model="form.latitude" type="text" placeholder="41.2995"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Долгота</label>
              <input v-model="form.longitude" type="text" placeholder="69.2401"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
          </div>
          <p class="text-xs text-gray-400">Координаты для отображения на карте в маркетплейсе</p>
        </section>

        <div class="flex items-center justify-between">
          <div>
            <p v-if="successMsg" class="text-sm text-green-600 font-medium">{{ successMsg }}</p>
            <p v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</p>
          </div>
          <button @click="saveAgency" :disabled="saving"
            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </template>

      <!-- ВКЛАДКА: Личный профиль -->
      <template v-if="activeTab === 'profile'">
        <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Личные данные</h2>

          <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white font-bold text-xl shrink-0">
              {{ profile.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <div>
              <p class="font-semibold text-gray-900 text-lg">{{ profile.name }}</p>
              <p class="text-sm text-gray-400">{{ profile.role === 'owner' ? 'Владелец' : profile.role === 'manager' ? 'Менеджер' : profile.role }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <AppInput v-model="profile.email" label="Email" type="email" disabled />
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
              <input v-model="profile.phone" type="tel" placeholder="+998..."
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Telegram</label>
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-400">@</span>
              <input v-model="profile.telegram_username" type="text" placeholder="username"
                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
          </div>
        </section>

        <!-- Смена пароля -->
        <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
          <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Смена пароля</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
            <input v-model="passwords.current" type="password"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
              <input v-model="passwords.new_password" type="password" placeholder="Минимум 8 символов"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Повторите пароль</label>
              <input v-model="passwords.confirm" type="password"
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-400" />
            </div>
          </div>
          <p v-if="passwordError" class="text-xs text-red-500">{{ passwordError }}</p>
          <p v-if="passwordSuccess" class="text-xs text-green-600">{{ passwordSuccess }}</p>
        </section>

        <div class="flex items-center justify-between">
          <div>
            <p v-if="profileSuccess" class="text-sm text-green-600 font-medium">{{ profileSuccess }}</p>
            <p v-if="profileError" class="text-sm text-red-600">{{ profileError }}</p>
          </div>
          <button @click="saveProfile" :disabled="savingProfile"
            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
            {{ savingProfile ? 'Сохранение...' : 'Сохранить профиль' }}
          </button>
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/api/index';
import AppInput from '@/components/AppInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const savingProfile = ref(false);
const successMsg = ref('');
const errorMsg = ref('');
const profileSuccess = ref('');
const profileError = ref('');
const passwordError = ref('');
const passwordSuccess = ref('');
const activeTab = ref('agency');

const tabs = [
  { key: 'agency',  label: 'Агентство' },
  { key: 'profile', label: 'Личный профиль' },
];

const form = ref({
  description: '', description_uz: '', website_url: '', city: '',
  experience_years: null, address: '', managers_see_all_cases: false,
  lead_assignment_mode: 'manual', phone: '', email: '', latitude: '', longitude: '',
});

const profile = ref({
  name: '', email: '', phone: '', telegram_username: '', role: '',
});

const passwords = reactive({ current: '', new_password: '', confirm: '' });

onMounted(async () => {
  try {
    const [settingsRes] = await Promise.all([
      api.get('/agency/settings'),
    ]);
    const data = settingsRes.data.data;
    Object.keys(form.value).forEach(key => {
      if (data[key] !== undefined && data[key] !== null) form.value[key] = data[key];
    });

    // Профиль из auth store
    const user = auth.user;
    if (user) {
      profile.value = {
        name: user.name || '',
        email: user.email || '',
        phone: user.phone || '',
        telegram_username: user.telegram_username || '',
        role: user.role || '',
      };
    }
  } catch { /* ignore */ } finally {
    loading.value = false;
  }
});

async function saveAgency() {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    const payload = Object.fromEntries(
      Object.entries(form.value).map(([k, v]) => [k, v === '' ? null : v])
    );
    await api.patch('/agency/settings', payload);
    successMsg.value = 'Настройки агентства сохранены';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Ошибка при сохранении';
  } finally {
    saving.value = false;
  }
}

async function saveProfile() {
  savingProfile.value = true;
  profileSuccess.value = '';
  profileError.value = '';
  passwordError.value = '';
  passwordSuccess.value = '';

  try {
    // Сохраняем контактные данные через PATCH /users/:id
    const userId = auth.user?.id;
    if (userId) {
      await api.put(`/users/${userId}`, {
        phone: profile.value.phone || null,
        telegram_username: profile.value.telegram_username || null,
      });
    }

    // Смена пароля если заполнены поля
    if (passwords.new_password) {
      if (passwords.new_password.length < 8) {
        passwordError.value = 'Минимум 8 символов';
        return;
      }
      if (passwords.new_password !== passwords.confirm) {
        passwordError.value = 'Пароли не совпадают';
        return;
      }
      try {
        await api.post(`/users/${userId}/password`, {
          password: passwords.new_password,
        });
        passwordSuccess.value = 'Пароль изменён';
        passwords.current = '';
        passwords.new_password = '';
        passwords.confirm = '';
      } catch (e) {
        passwordError.value = e.response?.data?.message || 'Ошибка смены пароля';
        return;
      }
    }

    profileSuccess.value = 'Профиль обновлён';
    setTimeout(() => { profileSuccess.value = ''; }, 3000);
  } catch (e) {
    profileError.value = e.response?.data?.message || 'Ошибка сохранения';
  } finally {
    savingProfile.value = false;
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Настройки агентства</h1>
      <p class="text-sm text-gray-500 mt-1">Профиль, команда и рабочие направления</p>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Общая информация -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Общая информация</h2>

        <AppTextarea
          v-model="form.description"
          label="Описание агентства (RU)"
          placeholder="Расскажите о вашем агентстве: специализация, опыт, ключевые направления..."
          :maxlength="1000"
          :rows="4"
          hint="Описание на русском языке — отображается в профиле на маркетплейсе"
        />
        <AppTextarea
          v-model="form.description_uz"
          label="Agentlik haqida (UZ)"
          placeholder="Agentligingiz haqida aytib bering: ixtisoslashuv, tajriba, asosiy yo'nalishlar..."
          :maxlength="1000"
          :rows="4"
          hint="O'zbek tilidagi tavsif — foydalanuvchi UZ tilini tanlasa ko'rinadi"
        />

        <div class="grid grid-cols-2 gap-4">
          <AppInput
            v-model="form.website_url"
            label="Сайт"
            type="url"
            placeholder="https://example.com"
            :maxlength="200"
          />
          <AppInput
            v-model="form.city"
            label="Город"
            type="text"
            placeholder="Ташкент"
            :maxlength="80"
          />
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Опыт работы (лет)</label>
            <input v-model.number="form.experience_years" type="number" min="0" max="100"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" />
          </div>
        </div>

      </section>

      <!-- Команда -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Управление командой</h2>

        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-100">
          <div>
            <p class="text-sm font-medium text-gray-700">Менеджеры видят все заявки</p>
            <p class="text-xs text-gray-500 mt-0.5">Если выключено — каждый менеджер видит только свои заявки</p>
          </div>
          <button @click="form.managers_see_all_cases = !form.managers_see_all_cases"
            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1',
              form.managers_see_all_cases ? 'bg-blue-600' : 'bg-gray-300']">
            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform',
              form.managers_see_all_cases ? 'translate-x-6' : 'translate-x-1']" />
          </button>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Авто-распределение лидов</label>
          <select v-model="form.lead_assignment_mode"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
            <option value="manual">Вручную (менеджер назначается вручную)</option>
            <option value="round_robin">По очереди (Round Robin — равномерно по кругу)</option>
            <option value="by_workload">По загрузке (менеджер с наименьшим числом активных заявок)</option>
            <option value="by_country">По стране (менеджер с опытом по направлению)</option>
          </select>
          <p class="text-xs text-gray-400 mt-1">Влияет на то, как новые лиды с маркетплейса назначаются менеджерам</p>
        </div>
      </section>

      <!-- Рабочие направления — перенесены в раздел "Страны" -->
      <section class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Рабочие направления</h2>
        <p class="text-sm text-gray-500 mt-2">
          Управление рабочими направлениями перенесено в раздел
          <router-link :to="{ name: 'countries' }" class="text-blue-600 font-medium hover:underline">Страны</router-link>.
        </p>
      </section>

      <!-- Контакты и местоположение -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Контакты и местоположение</h2>

        <div class="grid grid-cols-2 gap-4">
          <AppInput
            v-model="form.phone"
            label="Телефон"
            type="tel"
            placeholder="+998 90 123-45-67"
            :maxlength="30"
          />
          <AppInput
            v-model="form.email"
            label="Email агентства"
            type="email"
            placeholder="agency@example.com"
            :maxlength="150"
          />
        </div>

        <AppInput
          v-model="form.address"
          label="Адрес офиса"
          type="text"
          placeholder="Ул. Амира Тимура, 1, Ташкент"
          :maxlength="200"
        />

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Широта (Latitude)</label>
            <input v-model="form.latitude" type="text" placeholder="41.2995"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Долгота (Longitude)</label>
            <input v-model="form.longitude" type="text" placeholder="69.2401"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" />
          </div>
        </div>
        <p class="text-xs text-gray-400">Координаты используются для отображения агентства на карте в маркетплейсе</p>
      </section>

      <div class="flex items-center justify-between">
        <div>
          <p v-if="successMsg" class="text-sm text-green-600 font-medium">{{ successMsg }}</p>
          <p v-if="errorMsg" class="text-sm text-red-600">{{ errorMsg }}</p>
        </div>
        <button @click="save" :disabled="saving"
          class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
          {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';
import AppInput from '@/components/AppInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
const loading    = ref(true);
const saving     = ref(false);
const successMsg = ref('');
const errorMsg   = ref('');

const form = ref({
  description: '',
  description_uz: '',
  website_url: '',
  city: '',
  experience_years: null,
  address: '',
  managers_see_all_cases: false,
  lead_assignment_mode: 'manual',
  phone: '',
  email: '',
  latitude: '',
  longitude: '',
});


onMounted(async () => {
  try {
    const settingsRes = await api.get('/agency/settings');

    const data = settingsRes.data.data;
    Object.keys(form.value).forEach(key => {
      if (data[key] !== undefined && data[key] !== null) form.value[key] = data[key];
    });
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});

async function save() {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    // Конвертируем пустые строки в null для корректной валидации
    const payload = Object.fromEntries(
      Object.entries(form.value).map(([k, v]) => [k, v === '' ? null : v])
    );
    await api.patch('/agency/settings', payload);

    successMsg.value = 'Настройки успешно сохранены';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Ошибка при сохранении';
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="max-w-3xl mx-auto space-y-8">
    <div>
      <h1 class="text-xl font-bold text-gray-900">Настройки агентства</h1>
      <p class="text-sm text-gray-500 mt-1">Профиль, команда и рабочие направления</p>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <template v-else>
      <!-- Общая информация -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Общая информация</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание агентства</label>
          <textarea v-model="form.description" rows="4"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Расскажите о вашем агентстве..."></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сайт</label>
            <input v-model="form.website_url" type="url"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="https://example.com" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Город</label>
            <input v-model="form.city" type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Ташкент" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Опыт работы (лет)</label>
            <input v-model.number="form.experience_years" type="number" min="0" max="100"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Адрес</label>
          <input v-model="form.address" type="text"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Ул. Амира Тимура, 1" />
        </div>
      </section>

      <!-- Команда -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Управление командой</h2>

        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
          <div>
            <p class="text-sm font-medium text-gray-700">Менеджеры видят все заявки</p>
            <p class="text-xs text-gray-500 mt-0.5">Если выключено, каждый видит только свои заявки</p>
          </div>
          <button @click="form.managers_see_all_cases = !form.managers_see_all_cases"
            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
              form.managers_see_all_cases ? 'bg-blue-600' : 'bg-gray-300']">
            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
              form.managers_see_all_cases ? 'translate-x-6' : 'translate-x-1']" />
          </button>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Авто-распределение лидов</label>
          <select v-model="form.lead_assignment_mode"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="manual">Вручную</option>
            <option value="round_robin">По очереди (Round Robin)</option>
            <option value="by_workload">По загрузке (минимум активных)</option>
            <option value="by_country">По стране (опыт с направлением)</option>
          </select>
        </div>
      </section>

      <!-- Страны работы -->
      <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">Рабочие направления</h2>

        <div class="grid grid-cols-3 gap-3">
          <label v-for="c in allCountries" :key="c.code"
            class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" :value="c.code" v-model="selectedCountries"
              class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" />
            <span class="text-sm text-gray-700">{{ c.flag }} {{ c.name }}</span>
          </label>
        </div>
      </section>

      <div class="flex justify-end">
        <button @click="save" :disabled="saving"
          class="px-6 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
        </button>
      </div>

      <p v-if="successMsg" class="text-sm text-green-600 text-right">{{ successMsg }}</p>
      <p v-if="errorMsg" class="text-sm text-red-600 text-right">{{ errorMsg }}</p>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const loading = ref(true);
const saving  = ref(false);
const successMsg = ref('');
const errorMsg   = ref('');

const form = ref({
  description: '',
  website_url: '',
  city: '',
  experience_years: null,
  address: '',
  managers_see_all_cases: false,
  lead_assignment_mode: 'manual',
});

const selectedCountries = ref([]);

const allCountries = [
  { code: 'US', name: 'США',          flag: '🇺🇸' },
  { code: 'DE', name: 'Германия',     flag: '🇩🇪' },
  { code: 'FR', name: 'Франция',      flag: '🇫🇷' },
  { code: 'GB', name: 'Великобритания', flag: '🇬🇧' },
  { code: 'TR', name: 'Турция',       flag: '🇹🇷' },
  { code: 'AE', name: 'ОАЭ',          flag: '🇦🇪' },
  { code: 'KZ', name: 'Казахстан',    flag: '🇰🇿' },
  { code: 'RU', name: 'Россия',       flag: '🇷🇺' },
  { code: 'CN', name: 'Китай',        flag: '🇨🇳' },
  { code: 'IT', name: 'Италия',       flag: '🇮🇹' },
  { code: 'ES', name: 'Испания',      flag: '🇪🇸' },
];

onMounted(async () => {
  try {
    const res = await api.get('/agency/settings');
    const data = res.data.data;
    Object.keys(form.value).forEach(key => {
      if (data[key] !== undefined && data[key] !== null) form.value[key] = data[key];
    });
    selectedCountries.value = (data.work_countries || [])
      .filter(c => c.is_active)
      .map(c => c.country_code);
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
    await api.patch('/agency/settings', form.value);

    // Синхронизируем рабочие страны
    const currentRes = await api.get('/agency/work-countries');
    const current = (currentRes.data.data || []).map(c => c.country_code);

    const toAdd = selectedCountries.value.filter(c => !current.includes(c));
    const toRemove = current.filter(c => !selectedCountries.value.includes(c));

    await Promise.all([
      ...toAdd.map(c => api.post('/agency/work-countries', { country_code: c })),
      ...toRemove.map(c => api.delete(`/agency/work-countries/${c}`)),
    ]);

    successMsg.value = 'Настройки сохранены';
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Ошибка при сохранении';
  } finally {
    saving.value = false;
  }
}
</script>

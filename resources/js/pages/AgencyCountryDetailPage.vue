<template>
  <div class="space-y-6">
    <!-- Назад -->
    <button @click="router.push({ name: 'countries' })"
      class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
      </svg>
      Все страны
    </button>

    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <template v-else-if="country">
      <!-- Заголовок -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <span class="text-4xl">{{ codeToFlag(country.country_code) }}</span>
            <div>
              <h1 class="text-xl font-bold text-gray-900">{{ localName }}</h1>
              <span class="text-xs font-semibold px-2.5 py-1 rounded-full mt-1 inline-block"
                :class="regimeBadge">
                {{ regimeLabel }}
              </span>
            </div>
          </div>

          <!-- Toggle работаем / не работаем -->
          <button @click="toggleWork" :disabled="toggling"
            :class="[
              'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors border',
              isWorking
                ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-100'
                : 'bg-gray-50 text-gray-600 border-gray-200 hover:bg-gray-100'
            ]">
            <svg v-if="isWorking" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ toggling ? '...' : isWorking ? 'Работаем' : 'Добавить направление' }}
          </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div v-if="country.visa_free_days" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-400">Безвизовые дни</div>
            <div class="text-sm font-semibold text-gray-900">{{ country.visa_free_days }} дней</div>
          </div>
          <div v-if="country.visa_fee" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-400">Стоимость визы</div>
            <div class="text-sm font-semibold text-gray-900">${{ country.visa_fee }}</div>
          </div>
          <div v-if="country.evisa_fee" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-400">eVisa</div>
            <div class="text-sm font-semibold text-gray-900">${{ country.evisa_fee }}</div>
          </div>
          <div v-if="country.avg_flight_usd" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-400">Средний перелёт</div>
            <div class="text-sm font-semibold text-gray-900">${{ country.avg_flight_usd }}</div>
          </div>
          <div v-if="country.avg_hotel_usd" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-400">Средний отель / ночь</div>
            <div class="text-sm font-semibold text-gray-900">${{ country.avg_hotel_usd }}</div>
          </div>
          <div v-if="country.continent" class="p-3 bg-gray-50 rounded-lg">
            <div class="text-xs text-gray-400">Континент</div>
            <div class="text-sm font-semibold text-gray-900">{{ country.continent }}</div>
          </div>
        </div>

        <!-- eVisa URL -->
        <a v-if="country.evisa_url" :href="country.evisa_url" target="_blank" rel="noopener"
          class="mt-4 inline-flex items-center gap-2 text-sm text-blue-600 font-medium hover:underline">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
          eVisa — подать онлайн
        </a>
      </div>

      <!-- Требования -->
      <div v-if="country.requirements?.length" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide mb-3">Требуемые документы</h2>
        <div class="space-y-2">
          <div v-for="r in country.requirements" :key="r" class="flex items-center gap-2 text-sm text-gray-600">
            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            {{ r }}
          </div>
        </div>
      </div>
    </template>

    <div v-else class="bg-white rounded-xl border border-gray-200 p-8 text-center">
      <p class="font-semibold text-gray-900">Страна не найдена</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/api/index';
import { codeToFlag, countryName } from '@/utils/countries';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const country = ref(null);
const isWorking = ref(false);
const toggling = ref(false);

const localName = computed(() => {
  if (!country.value) return '';
  return countryName(country.value.country_code) || country.value.name || country.value.country_code;
});

const regimeBadge = computed(() => ({
  visa_free:       'bg-green-50 text-green-700',
  visa_on_arrival: 'bg-blue-50 text-blue-600',
  evisa:           'bg-cyan-50 text-cyan-700',
  visa_required:   'bg-red-50 text-red-600',
}[country.value?.visa_regime] || 'bg-gray-100 text-gray-500'));

const regimeLabel = computed(() => ({
  visa_free:       'Безвизовый',
  visa_on_arrival: 'По прибытии',
  evisa:           'eVisa',
  visa_required:   'Требуется виза',
}[country.value?.visa_regime] || country.value?.visa_regime));

async function toggleWork() {
  toggling.value = true;
  try {
    const code = country.value.country_code;
    if (isWorking.value) {
      await api.delete(`/agency/work-countries/${code}`);
      isWorking.value = false;
    } else {
      await api.post('/agency/work-countries', { country_code: code });
      isWorking.value = true;
    }
  } catch { /* ignore */ } finally {
    toggling.value = false;
  }
}

onMounted(async () => {
  try {
    const code = route.params.code;
    const [countriesRes, workRes] = await Promise.all([
      api.get('/countries'),
      api.get('/agency/work-countries').catch(() => null),
    ]);
    const list = countriesRes.data.data ?? [];
    country.value = list.find(c => c.country_code === code) || null;
    const workCodes = (workRes?.data?.data ?? []).map(c => c.country_code);
    isWorking.value = workCodes.includes(code);
  } catch { /* ignore */ } finally {
    loading.value = false;
  }
});
</script>

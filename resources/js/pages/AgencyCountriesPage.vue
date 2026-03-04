<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Страны</h1>
        <p class="text-sm text-gray-500 mt-1">Визовая информация и рабочие направления</p>
      </div>
      <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
        <input type="checkbox" v-model="onlyOurs"
          class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500" />
        Только наши направления
      </label>
    </div>

    <!-- Фильтры -->
    <div class="flex flex-wrap gap-2">
      <input v-model="search" type="text" placeholder="Поиск по названию..."
        class="flex-1 min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" />
      <select v-model="filterRegime"
        class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors bg-white">
        <option value="">Все режимы</option>
        <option value="visa_free">Безвизовый</option>
        <option value="visa_on_arrival">По прибытии</option>
        <option value="evisa">eVisa</option>
        <option value="visa_required">Требуется виза</option>
      </select>
      <select v-model="filterContinent"
        class="rounded-lg border border-gray-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors bg-white">
        <option value="">Все континенты</option>
        <option v-for="c in continents" :key="c" :value="c">{{ c }}</option>
      </select>
    </div>

    <!-- Загрузка -->
    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <!-- Таблица -->
    <div v-else-if="filtered.length" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="bg-gray-50 border-b border-gray-200">
            <th class="text-left px-4 py-3 font-medium text-gray-500">Страна</th>
            <th class="text-left px-4 py-3 font-medium text-gray-500">Визовый режим</th>
            <th class="text-left px-4 py-3 font-medium text-gray-500 hidden sm:table-cell">Континент</th>
            <th class="text-left px-4 py-3 font-medium text-gray-500 hidden md:table-cell">Стоимость визы</th>
            <th class="text-center px-4 py-3 font-medium text-gray-500">Наше</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="c in filtered" :key="c.country_code"
            @click="router.push({ name: 'countries.show', params: { code: c.country_code } })"
            class="border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors">
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <span class="text-lg">{{ codeToFlag(c.country_code) }}</span>
                <span class="font-medium text-gray-900">{{ localName(c) }}</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full"
                :class="regimeBadge(c.visa_regime)">
                {{ regimeLabel(c.visa_regime) }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-500 hidden sm:table-cell">{{ c.continent || '—' }}</td>
            <td class="px-4 py-3 text-gray-500 hidden md:table-cell">
              {{ c.visa_fee ? '$' + c.visa_fee : '—' }}
            </td>
            <td class="px-4 py-3 text-center">
              <span v-if="workCodes.includes(c.country_code)" class="text-green-600">
                <svg class="w-5 h-5 inline" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="bg-white rounded-xl border border-gray-200 p-8 text-center">
      <p class="text-sm text-gray-400">Страны не найдены</p>
    </div>

    <p class="text-xs text-gray-400">
      Показано: {{ filtered.length }} из {{ countries.length }}
    </p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/index';
import { codeToFlag, countryName } from '@/utils/countries';

const router = useRouter();
const loading = ref(true);
const countries = ref([]);
const workCodes = ref([]);

const search = ref('');
const filterRegime = ref('');
const filterContinent = ref('');
const onlyOurs = ref(false);

const continents = ['Asia', 'Europe', 'Africa', 'North America', 'South America', 'Oceania'];

function localName(c) {
  return countryName(c.country_code) || c.name || c.country_code;
}

function regimeBadge(regime) {
  return {
    visa_free:       'bg-green-50 text-green-700',
    visa_on_arrival: 'bg-blue-50 text-blue-600',
    evisa:           'bg-cyan-50 text-cyan-700',
    visa_required:   'bg-red-50 text-red-600',
  }[regime] || 'bg-gray-100 text-gray-500';
}

function regimeLabel(regime) {
  return {
    visa_free:       'Безвизовый',
    visa_on_arrival: 'По прибытии',
    evisa:           'eVisa',
    visa_required:   'Требуется виза',
  }[regime] || regime;
}

const filtered = computed(() => {
  let list = countries.value;
  if (onlyOurs.value) list = list.filter(c => workCodes.value.includes(c.country_code));
  if (filterRegime.value) list = list.filter(c => c.visa_regime === filterRegime.value);
  if (filterContinent.value) list = list.filter(c => c.continent === filterContinent.value);
  if (search.value) {
    const q = search.value.toLowerCase();
    list = list.filter(c =>
      (localName(c)).toLowerCase().includes(q) ||
      c.country_code.toLowerCase().includes(q)
    );
  }
  return list;
});

onMounted(async () => {
  try {
    const [countriesRes, workRes] = await Promise.all([
      api.get('/countries'),
      api.get('/agency/work-countries').catch(() => null),
    ]);
    countries.value = (countriesRes.data.data ?? []).sort((a, b) =>
      (localName(a)).localeCompare(localName(b), 'ru')
    );
    workCodes.value = (workRes?.data?.data ?? []).map(c => c.country_code);
  } catch { /* ignore */ } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-3">
      <AppInput v-model="search" placeholder="Поиск по имени, телефону..." class="w-64" @input="debouncedFetch" />
      <div class="ml-auto">
        <RouterLink :to="{ name: 'clients.create' }">
          <AppButton>+ Новый клиент</AppButton>
        </RouterLink>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
      </div>

      <template v-else>
        <table class="w-full text-sm" v-if="clients.length">
          <thead class="bg-gray-50 border-b text-gray-500 text-xs uppercase tracking-wide">
            <tr>
              <th class="text-left px-4 py-3">Клиент</th>
              <th class="text-left px-4 py-3">Телефон</th>
              <th class="text-left px-4 py-3">Гражданство</th>
              <th class="text-left px-4 py-3">Паспорт действует</th>
              <th class="text-left px-4 py-3">Источник</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="c in clients" :key="c.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-3 font-medium text-gray-900">{{ c.name }}</td>
              <td class="px-4 py-3 text-gray-500">{{ c.phone ?? '—' }}</td>
              <td class="px-4 py-3">{{ c.nationality ?? '—' }}</td>
              <td class="px-4 py-3" :class="passportClass(c.passport_expires_at)">
                {{ c.passport_expires_at ? formatDate(c.passport_expires_at) : '—' }}
              </td>
              <td class="px-4 py-3">
                <AppBadge :color="sourceColor(c.source)">{{ sourceLabel(c.source) }}</AppBadge>
              </td>
              <td class="px-4 py-3 text-right">
                <RouterLink :to="{ name: 'clients.show', params: { id: c.id } }" class="text-blue-600 hover:underline text-xs">
                  Открыть
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="py-20 text-center text-gray-400">
          <p class="text-4xl mb-3">👤</p>
          <p>Клиентов не найдено</p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { clientsApi } from '@/api/clients';
import AppInput from '@/components/AppInput.vue';
import AppButton from '@/components/AppButton.vue';
import AppBadge from '@/components/AppBadge.vue';

const clients = ref([]);
const search  = ref('');
const loading = ref(false);

const sourceMap = {
  direct: { label: 'Прямой', color: 'blue' },
  referral: { label: 'Реферал', color: 'purple' },
  marketplace: { label: 'Маркетплейс', color: 'green' },
  other: { label: 'Другое', color: 'gray' },
};
const sourceLabel = (s) => sourceMap[s]?.label ?? s;
const sourceColor = (s) => sourceMap[s]?.color ?? 'gray';

function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU');
}
function passportClass(d) {
  if (!d) return '';
  const days = Math.floor((new Date(d) - new Date()) / 86400000);
  return days < 0 ? 'text-red-600 font-medium' : days <= 90 ? 'text-yellow-600' : 'text-gray-600';
}

let debounce;
function debouncedFetch() {
  clearTimeout(debounce);
  debounce = setTimeout(fetchClients, 300);
}

async function fetchClients() {
  loading.value = true;
  try {
    const params = search.value ? { q: search.value } : {};
    const { data } = await clientsApi.list(params);
    clients.value = data.data?.data ?? data.data ?? [];
  } finally {
    loading.value = false;
  }
}

onMounted(fetchClients);
</script>

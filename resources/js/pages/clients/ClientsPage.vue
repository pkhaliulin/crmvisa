<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-3">
      <div class="relative">
        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
        </svg>
        <input v-model="search" type="text"
          class="pl-8 pr-8 py-2 w-64 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-blue-400 focus:ring-1 focus:ring-blue-400"
          placeholder="Имя, телефон, email..."
          @input="debouncedFetch" />
        <button v-if="search" @click="search = ''; fetchClients()"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <span class="text-sm text-gray-400">
        {{ clients.length }} {{ declension(clients.length, ['клиент', 'клиента', 'клиентов']) }}
      </span>

      <div class="ml-auto">
        <RouterLink :to="{ name: 'clients.create' }">
          <AppButton>+ Новый клиент</AppButton>
        </RouterLink>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-7 h-7 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Cards grid -->
      <div v-if="clients.length" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="c in clients" :key="c.id"
          class="group bg-white rounded-xl border border-gray-200 p-4 hover:border-blue-300 hover:shadow-md transition-all cursor-pointer"
          @click="$router.push({ name: 'clients.show', params: { id: c.id } })">

          <!-- Header: avatar + name -->
          <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shrink-0">
              {{ c.name?.[0]?.toUpperCase() ?? '?' }}
            </div>
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors truncate">{{ c.name }}</p>
              <p v-if="c.email" class="text-xs text-gray-400 truncate">{{ c.email }}</p>
            </div>
            <AppBadge :color="sourceColor(c.source)">{{ sourceLabel(c.source) }}</AppBadge>
          </div>

          <!-- Info rows -->
          <div class="space-y-1.5">
            <!-- Phone -->
            <div class="flex items-center gap-2 text-sm">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
              </svg>
              <a v-if="c.phone" :href="`tel:${c.phone}`" @click.stop
                class="text-gray-600 hover:text-blue-600 font-mono text-xs">{{ formatPhone(c.phone) }}</a>
              <span v-else class="text-gray-300 text-xs">--</span>
            </div>

            <!-- Nationality -->
            <div class="flex items-center gap-2 text-sm">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/>
              </svg>
              <span v-if="c.nationality" class="text-gray-600 text-xs">
                {{ NATIONALITY_FLAGS[c.nationality] ?? '' }} {{ NATIONALITIES[c.nationality] ?? c.nationality }}
              </span>
              <span v-else class="text-gray-300 text-xs">--</span>
            </div>

            <!-- Passport expiry -->
            <div class="flex items-center gap-2 text-sm">
              <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
              </svg>
              <span v-if="c.passport_expires_at" class="text-xs font-medium" :class="passportClass(c.passport_expires_at)">
                {{ formatDate(c.passport_expires_at) }}
                <span v-if="passportDaysLeft(c.passport_expires_at) <= 90" class="text-[10px]">
                  ({{ passportDaysLeft(c.passport_expires_at) < 0 ? 'истёк' : passportDaysLeft(c.passport_expires_at) + ' дн.' }})
                </span>
              </span>
              <span v-else class="text-gray-300 text-xs">паспорт не указан</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="bg-white rounded-xl border border-gray-200 py-20 text-center text-gray-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
        </svg>
        <p class="text-sm">Клиентов не найдено</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { clientsApi } from '@/api/clients';
import { formatPhone } from '@/utils/format';
import AppButton from '@/components/AppButton.vue';
import AppBadge from '@/components/AppBadge.vue';

const router  = useRouter();
const clients = ref([]);
const search  = ref('');
const loading = ref(false);

const NATIONALITIES = {
  UZB: 'Узбекистан', KAZ: 'Казахстан', KGZ: 'Кыргызстан', TJK: 'Таджикистан',
  TKM: 'Туркменистан', RUS: 'Россия', UKR: 'Украина', GEO: 'Грузия',
  AZE: 'Азербайджан', ARM: 'Армения', MDA: 'Молдова', BLR: 'Беларусь',
  GBR: 'Великобритания', DEU: 'Германия', FRA: 'Франция', ITA: 'Италия',
  USA: 'США', CAN: 'Канада', CHN: 'Китай', JPN: 'Япония',
};
const NATIONALITY_FLAGS = {
  UZB: '', KAZ: '', KGZ: '', TJK: '', TKM: '',
  RUS: '', UKR: '', GEO: '', AZE: '', ARM: '',
  MDA: '', BLR: '', GBR: '', DEU: '', FRA: '',
  ITA: '', USA: '', CAN: '', CHN: '', JPN: '',
};

const SOURCE_LABELS = {
  direct: 'Прямой', referral: 'Реферал', marketplace: 'Маркетплейс', other: 'Другой',
};
const SOURCE_COLORS = { direct: 'blue', referral: 'purple', marketplace: 'green', other: 'gray' };
const sourceLabel = (s) => SOURCE_LABELS[s] ?? s ?? '--';
const sourceColor = (s) => SOURCE_COLORS[s] ?? 'gray';

function declension(n, forms) {
  const abs = Math.abs(n) % 100;
  const n1 = abs % 10;
  if (abs > 10 && abs < 20) return forms[2];
  if (n1 > 1 && n1 < 5) return forms[1];
  if (n1 === 1) return forms[0];
  return forms[2];
}

function formatDate(d) {
  return new Date(d).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function passportDaysLeft(d) {
  if (!d) return null;
  return Math.floor((new Date(d) - new Date()) / 86400000);
}

function passportClass(d) {
  if (!d) return '';
  const days = passportDaysLeft(d);
  return days < 0 ? 'text-red-600' : days <= 90 ? 'text-yellow-600' : 'text-gray-600';
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

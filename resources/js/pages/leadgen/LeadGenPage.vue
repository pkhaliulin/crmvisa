<template>
  <div class="space-y-6">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-bold text-gray-900">{{ t('crm.leadgen.catalog.title') }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ t('crm.leadgen.catalog.subtitle') }}</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Stats -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-gray-900">{{ channels.length }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.catalog.totalChannels') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-green-600">{{ availableCount }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.catalog.availableChannels') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-purple-600">{{ enterpriseCount }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.catalog.enterpriseChannels') }}</div>
        </div>
      </div>

      <!-- Filter bar -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search -->
          <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input v-model="search" :placeholder="t('crm.leadgen.catalog.searchPlaceholder')"
              class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>

          <!-- Category -->
          <select v-model="filterCategory"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-blue-400">
            <option value="">{{ t('crm.leadgen.catalog.filterByCategory') }}</option>
            <option v-for="cat in categoryList" :key="cat.key" :value="cat.key">{{ cat.label }}</option>
          </select>

          <!-- Complexity -->
          <select v-model="filterComplexity"
            class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white outline-none focus:border-blue-400">
            <option value="">{{ t('crm.leadgen.catalog.filterByComplexity') }}</option>
            <option value="easy">{{ t('crm.leadgen.catalog.complexity.easy') }}</option>
            <option value="medium">{{ t('crm.leadgen.catalog.complexity.medium') }}</option>
            <option value="hard">{{ t('crm.leadgen.catalog.complexity.hard') }}</option>
          </select>

          <!-- Only available toggle -->
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" v-model="onlyAvailable"
              class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">{{ t('crm.leadgen.catalog.onlyAvailable') }}</span>
          </label>
        </div>
      </div>

      <!-- Channel grid -->
      <div v-if="filteredChannels.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="channel in filteredChannels" :key="channel.id"
          class="bg-white rounded-xl border border-gray-200 hover:shadow-md hover:border-blue-200 transition-all relative overflow-hidden flex flex-col">

          <!-- "СКОРО" overlay for disabled channels -->
          <div v-if="!channel.is_active"
            class="absolute inset-0 bg-white/80 backdrop-blur-[1px] z-10 flex flex-col items-center justify-center">
            <svg class="w-8 h-8 text-amber-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-semibold text-amber-700">{{ t('crm.leadgen.catalog.comingSoon') }}</span>
            <span class="text-xs text-gray-500 mt-1 px-4 text-center">{{ t('crm.leadgen.catalog.comingSoonOverlay') }}</span>
          </div>

          <!-- Lock overlay for plan upgrade -->
          <div v-else-if="!channel.available && !channel.coming_soon"
            class="absolute inset-0 bg-white/70 backdrop-blur-[1px] z-10 flex flex-col items-center justify-center">
            <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            <span class="text-sm font-medium text-gray-600">{{ t('crm.leadgen.catalog.upgradePlan') }}</span>
            <span class="text-xs text-gray-400 mt-0.5">{{ t('crm.leadgen.catalog.upgradeHint', { plan: t('crm.leadgen.catalog.plan.' + channel.min_plan) }) }}</span>
          </div>

          <!-- Card content -->
          <div class="p-5 flex-1 flex flex-col">
            <!-- Icon + Name -->
            <div class="flex items-start gap-3 mb-3">
              <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0"
                :class="categoryBgClass(channel.category)">
                <div v-html="categoryIcon(channel.category)" class="w-5 h-5" :class="categoryIconColor(channel.category)"></div>
              </div>
              <div class="min-w-0 flex-1">
                <h3 class="font-semibold text-gray-900 text-sm leading-snug">{{ channelName(channel) }}</h3>
              </div>
            </div>

            <!-- Description -->
            <p class="text-xs text-gray-500 leading-relaxed mb-3 flex-1">{{ channelDescription(channel) }}</p>

            <!-- Badges -->
            <div class="flex flex-wrap gap-1.5 mb-3">
              <!-- Effectiveness -->
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                :class="effectivenessClass(channel.effectiveness)">
                {{ t('crm.leadgen.catalog.effectiveness.' + channel.effectiveness) }}
              </span>

              <!-- Complexity -->
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                :class="complexityClass(channel.complexity)">
                {{ t('crm.leadgen.catalog.complexity.' + channel.complexity) }}
              </span>

              <!-- Launch speed -->
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                {{ t('crm.leadgen.catalog.launchSpeed.' + channel.launch_speed) }}
              </span>

              <!-- API badge -->
              <span v-if="channel.requires_api !== 'no'"
                class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-700">
                {{ t('crm.leadgen.catalog.apiBadge') }}
              </span>

              <!-- Enterprise badge -->
              <span v-if="channel.enterprise_only"
                class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-purple-50 text-purple-700">
                {{ t('crm.leadgen.catalog.enterpriseOnly') }}
              </span>

              <!-- Coming soon badge -->
              <span v-if="channel.coming_soon"
                class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 animate-pulse">
                {{ t('crm.leadgen.catalog.comingSoon') }}
              </span>

              <!-- Requires budget -->
              <span v-if="channel.requires_budget"
                class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-rose-50 text-rose-600">
                {{ t('crm.leadgen.catalog.requiresBudget') }}
              </span>
            </div>

            <!-- Coming soon hint -->
            <p v-if="channel.coming_soon" class="text-[10px] text-orange-600 mb-3">
              {{ t('crm.leadgen.catalog.comingSoonHint') }}
            </p>
          </div>

          <!-- Footer -->
          <div class="px-5 pb-4">
            <router-link
              :to="{ name: 'leadgen.detail', params: { code: channel.code } }"
              class="block w-full text-center px-4 py-2 text-sm font-medium rounded-lg transition-colors"
              :class="channel.available || channel.coming_soon || !channel.is_active
                ? 'bg-blue-600 text-white hover:bg-blue-700'
                : 'bg-gray-100 text-gray-400 pointer-events-none'">
              {{ t('crm.leadgen.catalog.details') }}
            </router-link>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-xl border border-gray-200 py-16 text-center">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <p class="text-sm text-gray-500">{{ t('crm.leadgen.catalog.noResults') }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ t('crm.leadgen.catalog.noResultsHint') }}</p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import { currentLocale } from '@/i18n';

const { t } = useI18n();

const loading = ref(true);
const channels = ref([]);
const agencyPlan = ref('');

const search = ref('');
const filterCategory = ref('');
const filterComplexity = ref('');
const onlyAvailable = ref(false);

// Stats
const availableCount = computed(() => channels.value.filter(c => c.available).length);
const enterpriseCount = computed(() => channels.value.filter(c => c.enterprise_only).length);

// Category list for filter dropdown
const categoryList = computed(() => [
  { key: 'messenger', label: t('crm.leadgen.catalog.categories.messenger') },
  { key: 'advertising', label: t('crm.leadgen.catalog.categories.advertising') },
  { key: 'web', label: t('crm.leadgen.catalog.categories.web') },
  { key: 'content_seo', label: t('crm.leadgen.catalog.categories.content_seo') },
  { key: 'partnership', label: t('crm.leadgen.catalog.categories.partnership') },
  { key: 'api_automation', label: t('crm.leadgen.catalog.categories.api_automation') },
]);

// Filtered channels
const filteredChannels = computed(() => {
  let list = [...channels.value];

  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase();
    list = list.filter(c =>
      c.name.toLowerCase().includes(q) ||
      (c.name_uz && c.name_uz.toLowerCase().includes(q)) ||
      (c.short_description && c.short_description.toLowerCase().includes(q)) ||
      (c.short_description_uz && c.short_description_uz.toLowerCase().includes(q)) ||
      c.code.toLowerCase().includes(q)
    );
  }

  if (filterCategory.value) {
    list = list.filter(c => c.category === filterCategory.value);
  }

  if (filterComplexity.value) {
    list = list.filter(c => c.complexity === filterComplexity.value);
  }

  if (onlyAvailable.value) {
    list = list.filter(c => c.available);
  }

  return list;
});

// Localized name/description
function channelName(channel) {
  if (currentLocale() === 'uz' && channel.name_uz) return channel.name_uz;
  return channel.name;
}

function channelDescription(channel) {
  if (currentLocale() === 'uz' && channel.short_description_uz) return channel.short_description_uz;
  return channel.short_description;
}

// Effectiveness badge colors
function effectivenessClass(level) {
  switch (level) {
    case 'low': return 'bg-gray-100 text-gray-600';
    case 'medium': return 'bg-blue-50 text-blue-600';
    case 'high': return 'bg-green-50 text-green-700';
    case 'very_high': return 'bg-emerald-100 text-emerald-700';
    default: return 'bg-gray-100 text-gray-600';
  }
}

// Complexity badge colors
function complexityClass(level) {
  switch (level) {
    case 'easy': return 'bg-green-50 text-green-600';
    case 'medium': return 'bg-amber-50 text-amber-600';
    case 'hard': return 'bg-red-50 text-red-600';
    default: return 'bg-gray-100 text-gray-600';
  }
}

// Category styling
function categoryBgClass(category) {
  const map = {
    messenger: 'bg-blue-50',
    advertising: 'bg-red-50',
    web: 'bg-green-50',
    content_seo: 'bg-orange-50',
    partnership: 'bg-purple-50',
    api_automation: 'bg-indigo-50',
  };
  return map[category] || 'bg-gray-50';
}

function categoryIconColor(category) {
  const map = {
    messenger: 'text-blue-600',
    advertising: 'text-red-600',
    web: 'text-green-600',
    content_seo: 'text-orange-600',
    partnership: 'text-purple-600',
    api_automation: 'text-indigo-600',
  };
  return map[category] || 'text-gray-600';
}

function categoryIcon(category) {
  const icons = {
    messenger: '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"/></svg>',
    advertising: '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46"/></svg>',
    web: '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 003 12c0-1.605.42-3.113 1.157-4.418"/></svg>',
    content_seo: '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5"/></svg>',
    partnership: '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>',
    api_automation: '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5"/></svg>',
  };
  return icons[category] || icons.web;
}

// Load data
onMounted(async () => {
  try {
    const res = await api.get('/lead-channels');
    const payload = res.data?.data || res.data;
    channels.value = payload.channels || [];
    agencyPlan.value = payload.agency_plan || '';
  } catch {
    channels.value = [];
  } finally {
    loading.value = false;
  }
});
</script>

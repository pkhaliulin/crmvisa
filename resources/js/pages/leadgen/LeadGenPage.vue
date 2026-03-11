<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full"></div>
    </div>

    <template v-else>
      <!-- Header + Plan -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ t('crm.leadgen.catalog.title') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ t('crm.leadgen.catalog.subtitle') }}</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="text-right">
              <div class="text-xs text-gray-500">{{ t('crm.leadgen.workspace.yourPlan') }}</div>
              <div class="text-sm font-bold" :class="planColor">{{ planLabel }}</div>
            </div>
            <button @click="router.push({ name: 'leadgen.analytics' })"
              class="px-4 py-2 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all whitespace-nowrap">
              {{ t('crm.leadgen.analytics.title') }}
            </button>
            <button @click="router.push({ name: 'leadgen.notifications' })"
              class="px-4 py-2 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all whitespace-nowrap">
              {{ t('crm.leadgen.notifications.title') }}
            </button>
            <button v-if="canUpgrade" @click="router.push({ name: 'billing' })"
              class="px-4 py-2 text-xs font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all whitespace-nowrap">
              {{ t('crm.leadgen.workspace.upgradePlan') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-gray-900">{{ channels.length }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.catalog.totalChannels') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-green-600">{{ availableCount }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.catalog.availableChannels') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-blue-600">{{ activeCount }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.workspace.activeChannels') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 text-center">
          <div class="text-2xl font-bold text-purple-600">{{ enterpriseCount }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.catalog.enterpriseChannels') }}</div>
        </div>
      </div>

      <!-- API Key block -->
      <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-xl border border-blue-100 p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center shrink-0">
              <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-900">{{ t('crm.leadgen.workspace.apiKeyTitle') }}</h3>
              <p class="text-xs text-gray-500 mt-0.5">{{ t('crm.leadgen.workspace.apiKeyDesc') }}</p>
              <div v-if="apiKeyInfo.has_key" class="mt-1.5">
                <span class="text-xs text-green-600 font-medium">{{ t('crm.leadgen.workspace.apiKeyActive') }}</span>
                <span v-if="apiKeyInfo.generated_at" class="text-xs text-gray-400 ml-2">
                  {{ t('crm.leadgen.workspace.apiKeyCreated', { date: formatDate(apiKeyInfo.generated_at) }) }}
                </span>
              </div>
              <!-- Показываем новый ключ -->
              <div v-if="newApiKey" class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-xs text-amber-800 font-semibold mb-1">{{ t('crm.leadgen.workspace.apiKeySaveWarning') }}</p>
                <code class="text-xs text-gray-900 break-all select-all bg-white px-2 py-1 rounded border block">{{ newApiKey }}</code>
              </div>
            </div>
          </div>
          <button @click="generateApiKey" :disabled="generatingKey"
            class="shrink-0 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
            :class="apiKeyInfo.has_key
              ? 'text-gray-600 border border-gray-300 hover:bg-gray-50'
              : 'text-white bg-blue-600 hover:bg-blue-700'">
            <span v-if="generatingKey">...</span>
            <span v-else>{{ apiKeyInfo.has_key ? t('crm.leadgen.workspace.apiKeyRegenerate') : t('crm.leadgen.workspace.apiKeyGenerate') }}</span>
          </button>
        </div>
      </div>

      <!-- Integration help block -->
      <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100 p-5">
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
            </svg>
          </div>
          <div class="flex-1">
            <h3 class="text-sm font-semibold text-gray-900">{{ t('crm.leadgen.workspace.helpTitle') }}</h3>
            <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ t('crm.leadgen.workspace.helpDesc') }}</p>
            <div class="flex flex-wrap gap-2 mt-3">
              <span class="inline-flex items-center gap-1 text-xs bg-white border border-emerald-200 rounded-full px-3 py-1 text-emerald-700">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ t('crm.leadgen.workspace.helpTz') }}
              </span>
              <span class="inline-flex items-center gap-1 text-xs bg-white border border-emerald-200 rounded-full px-3 py-1 text-emerald-700">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ t('crm.leadgen.workspace.helpApi') }}
              </span>
              <span class="inline-flex items-center gap-1 text-xs bg-white border border-emerald-200 rounded-full px-3 py-1 text-emerald-700">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ t('crm.leadgen.workspace.helpWebhook') }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-2 italic">{{ t('crm.leadgen.workspace.helpNote') }}</p>
          </div>
        </div>
      </div>

      <!-- Filter bar -->
      <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative flex-1 min-w-[200px]">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input v-model="search" :placeholder="t('crm.leadgen.catalog.searchPlaceholder')"
              class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
          <SearchSelect
            v-model="filterCategory"
            :items="categorySelectItems"
            allow-all
            :all-label="t('crm.leadgen.catalog.filterByCategory')"
            compact
          />
          <SearchSelect
            v-model="filterComplexity"
            :items="complexityItems"
            allow-all
            :all-label="t('crm.leadgen.catalog.filterByComplexity')"
            compact
          />
          <label class="flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" v-model="onlyAvailable" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">{{ t('crm.leadgen.catalog.onlyAvailable') }}</span>
          </label>
        </div>
      </div>

      <!-- Channel grid -->
      <div v-if="filteredChannels.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="channel in filteredChannels" :key="channel.id"
          class="bg-white rounded-xl border border-gray-200 hover:shadow-md hover:border-blue-200 transition-all relative overflow-hidden flex flex-col cursor-pointer"
          @click="goToDetail(channel)">

          <!-- "СКОРО" overlay -->
          <div v-if="!channel.is_active"
            class="absolute inset-0 bg-white/80 backdrop-blur-[1px] z-10 flex flex-col items-center justify-center pointer-events-none">
            <svg class="w-8 h-8 text-amber-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-semibold text-amber-700">{{ t('crm.leadgen.catalog.comingSoon') }}</span>
          </div>

          <!-- Lock overlay for plan upgrade -->
          <div v-else-if="!channel.available"
            class="absolute inset-0 bg-white/70 backdrop-blur-[1px] z-10 flex flex-col items-center justify-center pointer-events-none">
            <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            <span class="text-sm font-medium text-gray-600">{{ t(`crm.leadgen.catalog.plan.${channel.min_plan}`) }}</span>
          </div>

          <!-- Card content -->
          <div class="p-5 flex-1 flex flex-col">
            <div class="flex items-start gap-3 mb-3">
              <div class="relative">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl shrink-0" :class="categoryBgClass(channel.category)">
                  {{ channel.icon }}
                </div>
                <div v-if="channel.connected" class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-white" :title="t('crm.leadgen.detail.connected')"></div>
              </div>
              <div class="min-w-0 flex-1">
                <h3 class="font-semibold text-gray-900 text-sm leading-snug">{{ channelName(channel) }}</h3>
                <span class="text-[10px] text-gray-400 mt-0.5 block">{{ t(`crm.leadgen.catalog.categories.${channel.category}`) }}</span>
              </div>
            </div>

            <p class="text-xs text-gray-500 leading-relaxed mb-3 flex-1 line-clamp-2">{{ channelDescription(channel) }}</p>

            <!-- Badges -->
            <div class="flex flex-wrap gap-1.5">
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="effectivenessClass(channel.effectiveness)">
                {{ t('crm.leadgen.catalog.effectiveness.' + channel.effectiveness) }}
              </span>
              <span class="text-[10px] font-medium px-2 py-0.5 rounded-full" :class="complexityClass(channel.complexity)">
                {{ t('crm.leadgen.catalog.complexity.' + channel.complexity) }}
              </span>
              <span v-if="channel.requires_budget" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-rose-50 text-rose-600">
                {{ t('crm.leadgen.catalog.requiresBudget') }}
              </span>
              <span v-if="channel.requires_api !== 'no'" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-700">
                API
              </span>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-5 pb-4">
            <div class="w-full text-center px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white">
              {{ t('crm.leadgen.catalog.details') }}
            </div>
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
import { useRouter } from 'vue-router';
import api from '@/api/index';
import { currentLocale } from '@/i18n';
import { formatDate } from '@/utils/format';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const router = useRouter();

const loading = ref(true);
const channels = ref([]);
const agencyPlan = ref('');
const apiKeyInfo = ref({ has_key: false, generated_at: null });
const newApiKey = ref('');
const generatingKey = ref(false);

const search = ref('');
const filterCategory = ref('');
const filterComplexity = ref('');
const onlyAvailable = ref(false);

const planOrder = { trial: 0, starter: 1, pro: 2, enterprise: 3 };

const planLabel = computed(() => {
  const labels = { trial: 'Trial', starter: 'Starter', pro: 'Pro', enterprise: 'Enterprise' };
  return labels[agencyPlan.value] || agencyPlan.value;
});

const planColor = computed(() => {
  const map = { trial: 'text-gray-600', starter: 'text-blue-600', pro: 'text-indigo-600', enterprise: 'text-purple-600' };
  return map[agencyPlan.value] || 'text-gray-600';
});

const canUpgrade = computed(() => (planOrder[agencyPlan.value] ?? 0) < 3);

const availableCount = computed(() => channels.value.filter(c => c.available).length);
const activeCount = computed(() => channels.value.filter(c => c.connected).length);
const enterpriseCount = computed(() => channels.value.filter(c => c.enterprise_only).length);

const categoryList = computed(() => [
  { key: 'messenger', label: t('crm.leadgen.catalog.categories.messenger') },
  { key: 'advertising', label: t('crm.leadgen.catalog.categories.advertising') },
  { key: 'web', label: t('crm.leadgen.catalog.categories.web') },
  { key: 'content_seo', label: t('crm.leadgen.catalog.categories.content_seo') },
  { key: 'partnership', label: t('crm.leadgen.catalog.categories.partnership') },
  { key: 'api_automation', label: t('crm.leadgen.catalog.categories.api_automation') },
]);

const categorySelectItems = computed(() =>
  categoryList.value.map(cat => ({ value: cat.key, label: cat.label }))
);

const complexityItems = computed(() => [
  { value: 'easy', label: t('crm.leadgen.catalog.complexity.easy') },
  { value: 'medium', label: t('crm.leadgen.catalog.complexity.medium') },
  { value: 'hard', label: t('crm.leadgen.catalog.complexity.hard') },
]);

const filteredChannels = computed(() => {
  let list = [...channels.value];
  if (search.value.trim()) {
    const q = search.value.trim().toLowerCase();
    list = list.filter(c =>
      c.name.toLowerCase().includes(q) ||
      (c.name_uz && c.name_uz.toLowerCase().includes(q)) ||
      (c.short_description && c.short_description.toLowerCase().includes(q)) ||
      c.code.toLowerCase().includes(q)
    );
  }
  if (filterCategory.value) list = list.filter(c => c.category === filterCategory.value);
  if (filterComplexity.value) list = list.filter(c => c.complexity === filterComplexity.value);
  if (onlyAvailable.value) list = list.filter(c => c.available);
  return list;
});

function channelName(ch) {
  return currentLocale() === 'uz' && ch.name_uz ? ch.name_uz : ch.name;
}

function channelDescription(ch) {
  return currentLocale() === 'uz' && ch.short_description_uz ? ch.short_description_uz : ch.short_description;
}

function effectivenessClass(level) {
  const map = { low: 'bg-gray-100 text-gray-600', medium: 'bg-blue-50 text-blue-600', high: 'bg-green-50 text-green-700', very_high: 'bg-emerald-100 text-emerald-700' };
  return map[level] || 'bg-gray-100 text-gray-600';
}

function complexityClass(level) {
  const map = { easy: 'bg-green-50 text-green-600', medium: 'bg-amber-50 text-amber-600', hard: 'bg-red-50 text-red-600' };
  return map[level] || 'bg-gray-100 text-gray-600';
}

function categoryBgClass(cat) {
  const map = { messenger: 'bg-blue-50', advertising: 'bg-red-50', web: 'bg-green-50', content_seo: 'bg-orange-50', partnership: 'bg-purple-50', api_automation: 'bg-indigo-50' };
  return map[cat] || 'bg-gray-50';
}

function goToDetail(channel) {
  router.push({ name: 'leadgen.detail', params: { code: channel.code } });
}


async function generateApiKey() {
  generatingKey.value = true;
  try {
    const { data } = await api.post('/agency/api-key');
    const payload = data?.data || data;
    newApiKey.value = payload.api_key;
    apiKeyInfo.value = { has_key: true, generated_at: payload.generated_at };
  } catch {
    // silent
  } finally {
    generatingKey.value = false;
  }
}

onMounted(async () => {
  try {
    const [chRes, keyRes] = await Promise.all([
      api.get('/lead-channels'),
      api.get('/agency/api-key'),
    ]);
    const chPayload = chRes.data?.data || chRes.data;
    channels.value = chPayload.channels || [];
    agencyPlan.value = chPayload.agency_plan || '';

    const keyPayload = keyRes.data?.data || keyRes.data;
    apiKeyInfo.value = keyPayload;
  } catch {
    channels.value = [];
  } finally {
    loading.value = false;
  }
});
</script>

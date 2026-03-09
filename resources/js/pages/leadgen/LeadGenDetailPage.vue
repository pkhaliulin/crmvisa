<template>
  <div class="space-y-6">
    <!-- Загрузка -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
    </div>

    <!-- Ошибка -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="router.push({ name: 'leadgen' })" class="mt-3 text-sm text-red-600 underline">
        {{ t('crm.leadgen.detail.backToList') }}
      </button>
    </div>

    <template v-else-if="channel">
      <!-- Назад -->
      <button @click="router.push({ name: 'leadgen' })"
        class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        {{ t('crm.leadgen.detail.backToList') }}
      </button>

      <!-- "СКОРО" баннер для неактивных каналов -->
      <div v-if="!channel.is_active"
        class="bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start gap-3">
        <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <p class="text-sm font-semibold text-amber-800">{{ t('crm.leadgen.detail.comingSoonTitle') }}</p>
          <p class="text-xs text-amber-700 mt-1">{{ t('crm.leadgen.detail.comingSoonDesc') }}</p>
        </div>
      </div>

      <!-- Coming soon баннер -->
      <div v-else-if="channel.coming_soon"
        class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <p class="text-sm text-amber-800 font-medium">{{ t('crm.leadgen.detail.comingSoonBanner') }}</p>
      </div>

      <!-- Upsell баннер -->
      <div v-if="!channel.available && !channel.coming_soon"
        class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-5 flex items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold text-purple-900">
            {{ t('crm.leadgen.detail.upsellTitle', { plan: channel.min_plan }) }}
          </p>
          <p class="text-xs text-purple-700 mt-1">{{ t('crm.leadgen.detail.upsellDesc') }}</p>
        </div>
        <button @click="handleCta('upgrade')"
          class="shrink-0 px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all">
          {{ t('crm.leadgen.detail.upgradeBtn') }}
        </button>
      </div>

      <!-- Заголовок -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start gap-4">
          <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center text-2xl shrink-0">
            {{ channel.icon }}
          </div>
          <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-gray-900">{{ localizedName }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ t(`crm.leadgen.catalog.categories.${channel.category}`) }}
              </span>
              <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                :class="effectivenessClass(channel.effectiveness)">
                {{ t('crm.leadgen.catalog.effectiveness.label') }}:
                {{ t(`crm.leadgen.catalog.effectiveness.${channel.effectiveness}`) }}
              </span>
              <span class="text-xs font-medium px-2.5 py-1 rounded-full"
                :class="complexityClass(channel.complexity)">
                {{ t('crm.leadgen.catalog.complexity.label') }}:
                {{ t(`crm.leadgen.catalog.complexity.${channel.complexity}`) }}
              </span>
              <span v-if="channel.requires_budget"
                class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">
                {{ t('crm.leadgen.catalog.requiresBudget') }}
              </span>
              <span v-if="channel.requires_api"
                class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-600">
                {{ t('crm.leadgen.catalog.apiBadge') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- О канале -->
      <div v-if="localizedFullDescription" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.aboutChannel') }}</h2>
        <div class="space-y-2">
          <p v-for="(line, i) in splitLines(localizedFullDescription)" :key="i"
            class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
        </div>
      </div>

      <!-- Как это работает -->
      <div v-if="localizedHowItWorks" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.howItWorks') }}</h2>
        <div class="space-y-3">
          <div v-for="(step, i) in splitLines(localizedHowItWorks)" :key="i"
            class="flex items-start gap-3">
            <span
              class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
              {{ i + 1 }}
            </span>
            <p class="text-sm text-gray-700 leading-relaxed">{{ step }}</p>
          </div>
        </div>
      </div>

      <!-- Когда использовать / Когда не использовать -->
      <div v-if="channel.when_to_use || channel.when_not_to_use"
        class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-if="channel.when_to_use" class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="text-base font-semibold text-green-700 mb-3">{{ t('crm.leadgen.detail.whenToUse') }}</h2>
          <ul class="space-y-2">
            <li v-for="(item, i) in splitLines(channel.when_to_use)" :key="i"
              class="flex items-start gap-2 text-sm text-gray-700">
              <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <span>{{ item }}</span>
            </li>
          </ul>
        </div>
        <div v-if="channel.when_not_to_use" class="bg-white rounded-xl border border-gray-200 p-6">
          <h2 class="text-base font-semibold text-red-700 mb-3">{{ t('crm.leadgen.detail.whenNotToUse') }}</h2>
          <ul class="space-y-2">
            <li v-for="(item, i) in splitLines(channel.when_not_to_use)" :key="i"
              class="flex items-start gap-2 text-sm text-gray-700">
              <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
              <span>{{ item }}</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Характеристики -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.detail.characteristics') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">{{ t('crm.leadgen.catalog.effectiveness.label') }}</span>
            <span class="text-sm font-medium" :class="effectivenessTextClass(channel.effectiveness)">
              {{ t(`crm.leadgen.catalog.effectiveness.${channel.effectiveness}`) }}
            </span>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">{{ t('crm.leadgen.catalog.complexity.label') }}</span>
            <span class="text-sm font-medium" :class="complexityTextClass(channel.complexity)">
              {{ t(`crm.leadgen.catalog.complexity.${channel.complexity}`) }}
            </span>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">{{ t('crm.leadgen.catalog.launchSpeed.label') }}</span>
            <span class="text-sm font-medium text-gray-900">
              {{ t(`crm.leadgen.catalog.launchSpeed.${channel.launch_speed}`) }}
            </span>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">{{ t('crm.leadgen.detail.requiresBudgetLabel') }}</span>
            <span class="text-sm font-medium" :class="channel.requires_budget ? 'text-amber-600' : 'text-green-600'">
              {{ channel.requires_budget ? t('crm.leadgen.detail.yes') : t('crm.leadgen.detail.no') }}
            </span>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">{{ t('crm.leadgen.detail.requiresApiLabel') }}</span>
            <span class="text-sm font-medium" :class="channel.requires_api ? 'text-blue-600' : 'text-gray-500'">
              {{ channel.requires_api ? t('crm.leadgen.detail.yes') : t('crm.leadgen.detail.no') }}
            </span>
          </div>
          <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <span class="text-sm text-gray-600">{{ t('crm.leadgen.detail.minPlanLabel') }}</span>
            <span class="text-sm font-medium text-gray-900">
              {{ t(`crm.leadgen.catalog.plan.${channel.min_plan}`) }}
            </span>
          </div>
        </div>
        <!-- Рекомендовано для -->
        <div v-if="channel.recommended_for && channel.recommended_for.length" class="mt-4">
          <span class="text-sm text-gray-600">{{ t('crm.leadgen.detail.recommendedFor') }}:</span>
          <div class="flex flex-wrap gap-2 mt-2">
            <span v-for="(tag, i) in channel.recommended_for" :key="i"
              class="text-xs font-medium px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">
              {{ tag }}
            </span>
          </div>
        </div>
      </div>

      <!-- Варианты использования -->
      <div v-if="channel.use_cases && channel.use_cases.length" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.useCases') }}</h2>
        <ul class="space-y-2">
          <li v-for="(uc, i) in channel.use_cases" :key="i"
            class="flex items-start gap-2 text-sm text-gray-700">
            <span class="text-blue-500 shrink-0 mt-0.5">&#8226;</span>
            <span>{{ uc }}</span>
          </li>
        </ul>
      </div>

      <!-- Что нужно для запуска -->
      <div v-if="channel.required_preparation" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.requiredPreparation') }}</h2>
        <div class="space-y-2">
          <p v-for="(line, i) in splitLines(channel.required_preparation)" :key="i"
            class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
        </div>
      </div>

      <!-- Ожидаемый результат -->
      <div v-if="channel.expected_result" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.expectedResult') }}</h2>
        <div class="space-y-2">
          <p v-for="(line, i) in splitLines(channel.expected_result)" :key="i"
            class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
        </div>
      </div>

      <!-- Факторы эффективности -->
      <div v-if="channel.effectiveness_factors && channel.effectiveness_factors.length"
        class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.effectivenessFactors') }}</h2>
        <ul class="space-y-2">
          <li v-for="(factor, i) in channel.effectiveness_factors" :key="i"
            class="flex items-start gap-2 text-sm text-gray-700">
            <span class="text-blue-500 shrink-0 mt-0.5">&#8226;</span>
            <span>{{ factor }}</span>
          </li>
        </ul>
      </div>

      <!-- Риски и ограничения -->
      <div v-if="channel.risks" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.risks') }}</h2>
        <div class="space-y-2">
          <p v-for="(line, i) in splitLines(channel.risks)" :key="i"
            class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
        </div>
      </div>

      <!-- Лучшие практики -->
      <div v-if="channel.best_practices" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.bestPractices') }}</h2>
        <div class="space-y-2">
          <p v-for="(line, i) in splitLines(channel.best_practices)" :key="i"
            class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
        </div>
      </div>

      <!-- Тренды -->
      <div v-if="channel.trends" class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.trends') }}</h2>
        <div class="space-y-2">
          <p v-for="(line, i) in splitLines(channel.trends)" :key="i"
            class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
        </div>
      </div>

      <!-- CTA блок -->
      <div v-if="channel.cta_actions && channel.cta_actions.length"
        class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-wrap items-center gap-3">
          <template v-for="(action, i) in channel.cta_actions" :key="i">
            <button v-if="action === 'connect'" @click="handleCta('connect')"
              class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
              {{ t('crm.leadgen.detail.ctaConnect') }}
            </button>
            <button v-else-if="action === 'instruction'" @click="handleCta('instruction')"
              class="px-5 py-2.5 text-sm font-medium text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors">
              {{ t('crm.leadgen.detail.ctaInstruction') }}
            </button>
            <button v-else-if="action === 'upgrade'" @click="handleCta('upgrade')"
              class="px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all">
              {{ t('crm.leadgen.detail.ctaUpgrade') }}
            </button>
            <button v-else-if="action === 'learn_more'" @click="handleCta('learn_more')"
              class="text-sm text-blue-600 hover:text-blue-800 underline transition-colors">
              {{ t('crm.leadgen.detail.ctaLearnMore') }}
            </button>
          </template>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { currentLocale } from '@/i18n';
import api from '@/api/index';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const channel = ref(null);
const agencyPlan = ref(null);
const loading = ref(true);
const error = ref(null);

const localizedName = computed(() => {
  if (!channel.value) return '';
  if (currentLocale() === 'uz' && channel.value.name_uz) return channel.value.name_uz;
  return channel.value.name;
});

const localizedFullDescription = computed(() => {
  if (!channel.value) return '';
  if (currentLocale() === 'uz' && channel.value.full_description_uz) return channel.value.full_description_uz;
  return channel.value.full_description;
});

const localizedHowItWorks = computed(() => {
  if (!channel.value) return '';
  if (currentLocale() === 'uz' && channel.value.how_it_works_uz) return channel.value.how_it_works_uz;
  return channel.value.how_it_works;
});

const localizedShortDescription = computed(() => {
  if (!channel.value) return '';
  if (currentLocale() === 'uz' && channel.value.short_description_uz) return channel.value.short_description_uz;
  return channel.value.short_description;
});

function splitLines(text) {
  if (!text) return [];
  return text.split('\n').filter(line => line.trim() !== '');
}

function effectivenessClass(val) {
  const map = {
    low: 'bg-gray-100 text-gray-600',
    medium: 'bg-amber-50 text-amber-600',
    high: 'bg-green-50 text-green-600',
    very_high: 'bg-emerald-50 text-emerald-700',
  };
  return map[val] || 'bg-gray-100 text-gray-600';
}

function effectivenessTextClass(val) {
  const map = {
    low: 'text-gray-500',
    medium: 'text-amber-600',
    high: 'text-green-600',
    very_high: 'text-emerald-700',
  };
  return map[val] || 'text-gray-500';
}

function complexityClass(val) {
  const map = {
    easy: 'bg-green-50 text-green-600',
    medium: 'bg-amber-50 text-amber-600',
    hard: 'bg-red-50 text-red-600',
  };
  return map[val] || 'bg-gray-100 text-gray-600';
}

function complexityTextClass(val) {
  const map = {
    easy: 'text-green-600',
    medium: 'text-amber-600',
    hard: 'text-red-600',
  };
  return map[val] || 'text-gray-500';
}

async function fetchChannel() {
  try {
    loading.value = true;
    const code = route.params.code;
    const { data } = await api.get(`/lead-channels/${code}`);
    const payload = data?.data || data;
    channel.value = payload.channel;
    agencyPlan.value = payload.agency_plan;
  } catch (e) {
    error.value = e.response?.data?.message || t('crm.leadgen.detail.loadError');
  } finally {
    loading.value = false;
  }
}

async function trackAction(action) {
  try {
    const code = route.params.code;
    await api.post(`/lead-channels/${code}/track`, { action });
  } catch {
    // трекинг не должен блокировать UX
  }
}

function handleCta(action) {
  trackAction('click_cta');
  // Можно расширить логику для разных CTA
}

onMounted(async () => {
  await fetchChannel();
  if (channel.value) {
    trackAction('view');
  }
});
</script>

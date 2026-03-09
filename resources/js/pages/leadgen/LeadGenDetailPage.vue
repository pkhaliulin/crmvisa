<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
      <p class="text-red-700 font-medium">{{ error }}</p>
      <button @click="router.push({ name: 'leadgen' })" class="mt-3 text-sm text-red-600 underline">
        {{ t('crm.leadgen.detail.backToList') }}
      </button>
    </div>

    <template v-else-if="channel">
      <!-- Back + Breadcrumb -->
      <div class="flex items-center justify-between">
        <button @click="router.push({ name: 'leadgen' })"
          class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
          {{ t('crm.leadgen.detail.backToList') }}
        </button>
        <!-- Plan badge -->
        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-500">{{ t('crm.leadgen.workspace.yourPlan') }}:</span>
          <span class="text-xs font-bold" :class="planColor">{{ planLabel }}</span>
        </div>
      </div>

      <!-- "СКОРО" banner -->
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

      <!-- Upsell banner -->
      <div v-if="channel.is_active && !channel.available"
        class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <p class="text-sm font-semibold text-purple-900">
            {{ t('crm.leadgen.detail.upsellTitle', { plan: t(`crm.leadgen.catalog.plan.${channel.min_plan}`) }) }}
          </p>
          <p class="text-xs text-purple-700 mt-1">
            {{ t('crm.leadgen.detail.upsellCurrentPlan', { current: planLabel, required: t(`crm.leadgen.catalog.plan.${channel.min_plan}`) }) }}
          </p>
        </div>
        <button @click="router.push({ name: 'billing' })"
          class="shrink-0 px-5 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all">
          {{ t('crm.leadgen.detail.upgradeBtn') }}
        </button>
      </div>

      <!-- Channel header card -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start gap-4">
          <div class="w-14 h-14 rounded-xl bg-gray-50 flex items-center justify-center text-3xl shrink-0">
            {{ channel.icon }}
          </div>
          <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-gray-900">{{ localizedName }}</h1>
            <p v-if="localizedShortDescription" class="text-sm text-gray-500 mt-1">{{ localizedShortDescription }}</p>
            <div class="flex flex-wrap items-center gap-2 mt-3">
              <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                {{ t(`crm.leadgen.catalog.categories.${channel.category}`) }}
              </span>
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="effectivenessClass(channel.effectiveness)">
                {{ t(`crm.leadgen.catalog.effectiveness.${channel.effectiveness}`) }}
              </span>
              <span class="text-xs font-medium px-2.5 py-1 rounded-full" :class="complexityClass(channel.complexity)">
                {{ t(`crm.leadgen.catalog.complexity.${channel.complexity}`) }}
              </span>
              <span v-if="channel.requires_budget" class="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">
                {{ t('crm.leadgen.catalog.requiresBudget') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Two-column layout for main content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column (2/3) -->
        <div class="lg:col-span-2 space-y-5">

          <!-- About channel -->
          <div v-if="localizedFullDescription" class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.aboutChannel') }}</h2>
            <div class="space-y-2">
              <p v-for="(line, i) in toLines(localizedFullDescription)" :key="i"
                class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
            </div>
          </div>

          <!-- How it works -->
          <div v-if="localizedHowItWorks" class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.howItWorks') }}</h2>
            <div class="space-y-3">
              <div v-for="(step, i) in toLines(localizedHowItWorks)" :key="i" class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 text-xs font-bold mt-0.5">
                  {{ i + 1 }}
                </span>
                <p class="text-sm text-gray-700 leading-relaxed">{{ step }}</p>
              </div>
            </div>
          </div>

          <!-- Integration how-to -->
          <div data-section="integration" class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.detail.integrationTitle') }}</h2>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 text-xs font-bold">1</span>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ t('crm.leadgen.detail.integrationStep1Title') }}</p>
                  <p class="text-xs text-gray-600 mt-0.5">{{ t('crm.leadgen.detail.integrationStep1Desc') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 text-xs font-bold">2</span>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ t('crm.leadgen.detail.integrationStep2Title') }}</p>
                  <p class="text-xs text-gray-600 mt-0.5">{{ t('crm.leadgen.detail.integrationStep2Desc') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 text-xs font-bold">3</span>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ t('crm.leadgen.detail.integrationStep3Title') }}</p>
                  <p class="text-xs text-gray-600 mt-0.5">{{ t('crm.leadgen.detail.integrationStep3Desc') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <span class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0 text-xs font-bold">4</span>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ t('crm.leadgen.detail.integrationStep4Title') }}</p>
                  <p class="text-xs text-gray-600 mt-0.5">{{ t('crm.leadgen.detail.integrationStep4Desc') }}</p>
                </div>
              </div>
            </div>

            <!-- API example -->
            <div class="mt-5 bg-white rounded-lg border border-blue-200 p-4">
              <p class="text-xs font-semibold text-gray-700 mb-2">{{ t('crm.leadgen.detail.apiExample') }}</p>
              <pre class="text-xs text-gray-600 bg-gray-50 rounded p-3 overflow-x-auto"><code>POST /api/v1/leads/incoming
Authorization: Bearer vbk_YOUR_API_KEY
Content-Type: application/json

{
  "name": "{{ t('crm.leadgen.detail.apiExampleName') }}",
  "phone": "+998901234567",
  "country": "DE",
  "source": "{{ channel.code }}",
  "message": "{{ t('crm.leadgen.detail.apiExampleMessage') }}"
}</code></pre>
            </div>
          </div>

          <!-- When to use / When not to use -->
          <div v-if="channel.when_to_use || channel.when_not_to_use"
            class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-if="channel.when_to_use" class="bg-white rounded-xl border border-gray-200 p-5">
              <h2 class="text-sm font-semibold text-green-700 mb-3">{{ t('crm.leadgen.detail.whenToUse') }}</h2>
              <ul class="space-y-2">
                <li v-for="(item, i) in toLines(channel.when_to_use)" :key="i"
                  class="flex items-start gap-2 text-sm text-gray-700">
                  <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <span>{{ item }}</span>
                </li>
              </ul>
            </div>
            <div v-if="channel.when_not_to_use" class="bg-white rounded-xl border border-gray-200 p-5">
              <h2 class="text-sm font-semibold text-red-700 mb-3">{{ t('crm.leadgen.detail.whenNotToUse') }}</h2>
              <ul class="space-y-2">
                <li v-for="(item, i) in toLines(channel.when_not_to_use)" :key="i"
                  class="flex items-start gap-2 text-sm text-gray-700">
                  <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                  <span>{{ item }}</span>
                </li>
              </ul>
            </div>
          </div>

          <!-- Use cases -->
          <div v-if="useCaseLines.length" class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.useCases') }}</h2>
            <ul class="space-y-2">
              <li v-for="(uc, i) in useCaseLines" :key="i"
                class="flex items-start gap-2 text-sm text-gray-700">
                <span class="text-blue-500 shrink-0 mt-0.5">&#8226;</span>
                <span>{{ uc }}</span>
              </li>
            </ul>
          </div>

          <!-- Best practices -->
          <div v-if="channel.best_practices" class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.bestPractices') }}</h2>
            <div class="space-y-2">
              <p v-for="(line, i) in toLines(channel.best_practices)" :key="i"
                class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
            </div>
          </div>

          <!-- Risks -->
          <div v-if="channel.risks" class="bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.risks') }}</h2>
            <div class="space-y-2">
              <p v-for="(line, i) in toLines(channel.risks)" :key="i"
                class="text-sm text-gray-700 leading-relaxed">{{ line }}</p>
            </div>
          </div>
        </div>

        <!-- Right sidebar (1/3) -->
        <div class="space-y-5">

          <!-- Characteristics card -->
          <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">{{ t('crm.leadgen.detail.characteristics') }}</h2>
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('crm.leadgen.catalog.effectiveness.label') }}</span>
                <span class="text-xs font-semibold" :class="effectivenessTextClass(channel.effectiveness)">
                  {{ t(`crm.leadgen.catalog.effectiveness.${channel.effectiveness}`) }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('crm.leadgen.catalog.complexity.label') }}</span>
                <span class="text-xs font-semibold" :class="complexityTextClass(channel.complexity)">
                  {{ t(`crm.leadgen.catalog.complexity.${channel.complexity}`) }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('crm.leadgen.catalog.launchSpeed.label') }}</span>
                <span class="text-xs font-semibold text-gray-700">
                  {{ t(`crm.leadgen.catalog.launchSpeed.${channel.launch_speed}`) }}
                </span>
              </div>
              <hr class="border-gray-100">
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('crm.leadgen.detail.requiresBudgetLabel') }}</span>
                <span class="text-xs font-semibold" :class="channel.requires_budget ? 'text-amber-600' : 'text-green-600'">
                  {{ channel.requires_budget ? t('crm.leadgen.detail.yes') : t('crm.leadgen.detail.no') }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('crm.leadgen.detail.requiresApiLabel') }}</span>
                <span class="text-xs font-semibold" :class="channel.requires_api !== 'no' ? 'text-blue-600' : 'text-gray-500'">
                  {{ channel.requires_api !== 'no' ? t('crm.leadgen.detail.yes') : t('crm.leadgen.detail.no') }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('crm.leadgen.detail.minPlanLabel') }}</span>
                <span class="text-xs font-semibold text-gray-700">
                  {{ t(`crm.leadgen.catalog.plan.${channel.min_plan}`) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Effectiveness factors -->
          <div v-if="effectivenessFactorLines.length" class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.effectivenessFactors') }}</h2>
            <ul class="space-y-2">
              <li v-for="(factor, i) in effectivenessFactorLines" :key="i"
                class="flex items-start gap-2 text-xs text-gray-700">
                <span class="text-blue-500 shrink-0 mt-0.5">&#8226;</span>
                <span>{{ factor }}</span>
              </li>
            </ul>
          </div>

          <!-- Recommended for -->
          <div v-if="recommendedForLines.length" class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.recommendedFor') }}</h2>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="(tag, i) in recommendedForLines" :key="i"
                class="text-xs font-medium px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">
                {{ tag }}
              </span>
            </div>
          </div>

          <!-- Preparation -->
          <div v-if="channel.required_preparation" class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.requiredPreparation') }}</h2>
            <div class="space-y-2">
              <p v-for="(line, i) in toLines(channel.required_preparation)" :key="i"
                class="text-xs text-gray-700 leading-relaxed">{{ line }}</p>
            </div>
          </div>

          <!-- Expected result -->
          <div v-if="channel.expected_result" class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.expectedResult') }}</h2>
            <div class="space-y-2">
              <p v-for="(line, i) in toLines(channel.expected_result)" :key="i"
                class="text-xs text-gray-700 leading-relaxed">{{ line }}</p>
            </div>
          </div>

          <!-- Trends -->
          <div v-if="channel.trends" class="bg-white rounded-xl border border-gray-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">{{ t('crm.leadgen.detail.trends') }}</h2>
            <div class="space-y-2">
              <p v-for="(line, i) in toLines(channel.trends)" :key="i"
                class="text-xs text-gray-700 leading-relaxed">{{ line }}</p>
            </div>
          </div>

          <!-- Help with integration -->
          <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl border border-emerald-200 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">{{ t('crm.leadgen.workspace.helpTitle') }}</h2>
            <p class="text-xs text-gray-600 leading-relaxed">{{ t('crm.leadgen.workspace.helpDescShort') }}</p>
            <div class="mt-3 space-y-1.5">
              <div class="flex items-center gap-1.5 text-xs text-emerald-700">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ t('crm.leadgen.workspace.helpTz') }}
              </div>
              <div class="flex items-center gap-1.5 text-xs text-emerald-700">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ t('crm.leadgen.workspace.helpApi') }}
              </div>
              <div class="flex items-center gap-1.5 text-xs text-emerald-700">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                {{ t('crm.leadgen.workspace.helpWebhook') }}
              </div>
            </div>
            <p class="text-[10px] text-gray-500 mt-2 italic">{{ t('crm.leadgen.workspace.helpNote') }}</p>
          </div>

          <!-- CTA -->
          <div v-if="channel.cta_actions && channel.cta_actions.length"
            class="bg-white rounded-xl border border-gray-200 p-5">
            <!-- Connected badge -->
            <div v-if="channel.connected || connectSuccess" class="mb-3 flex items-center gap-2 text-green-600">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-sm font-medium">{{ t('crm.leadgen.detail.connected') }}</span>
            </div>
            <div class="flex flex-col gap-2">
              <template v-for="(action, i) in channel.cta_actions" :key="i">
                <button v-if="action === 'connect'" @click="handleCta('connect')"
                  :disabled="connecting || channel.connected || connectSuccess || !channel.available"
                  class="w-full px-4 py-2.5 text-sm font-medium rounded-lg transition-colors"
                  :class="channel.connected || connectSuccess
                    ? 'bg-green-100 text-green-700 cursor-default'
                    : !channel.available
                      ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                      : 'text-white bg-blue-600 hover:bg-blue-700'">
                  <span v-if="connecting">...</span>
                  <span v-else-if="channel.connected || connectSuccess">{{ t('crm.leadgen.detail.connected') }}</span>
                  <span v-else>{{ t('crm.leadgen.detail.ctaConnect') }}</span>
                </button>
                <button v-else-if="action === 'instruction'" @click="handleCta('instruction')"
                  class="w-full px-4 py-2.5 text-sm font-medium text-blue-600 border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors">
                  {{ t('crm.leadgen.detail.ctaInstruction') }}
                </button>
                <button v-else-if="action === 'upgrade'" @click="router.push({ name: 'billing' })"
                  class="w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all">
                  {{ t('crm.leadgen.detail.ctaUpgrade') }}
                </button>
              </template>
            </div>
          </div>
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
const agencyPlan = ref('');
const loading = ref(true);
const error = ref(null);

// --- Localized fields ---
const localizedName = computed(() => {
  if (!channel.value) return '';
  return currentLocale() === 'uz' && channel.value.name_uz ? channel.value.name_uz : channel.value.name;
});

const localizedShortDescription = computed(() => {
  if (!channel.value) return '';
  return currentLocale() === 'uz' && channel.value.short_description_uz ? channel.value.short_description_uz : channel.value.short_description;
});

const localizedFullDescription = computed(() => {
  if (!channel.value) return '';
  return currentLocale() === 'uz' && channel.value.full_description_uz ? channel.value.full_description_uz : channel.value.full_description;
});

const localizedHowItWorks = computed(() => {
  if (!channel.value) return '';
  return currentLocale() === 'uz' && channel.value.how_it_works_uz ? channel.value.how_it_works_uz : channel.value.how_it_works;
});

// --- CRITICAL FIX: split text strings into arrays by newline ---
// These fields are TEXT/VARCHAR in DB but were iterated with v-for on the string itself,
// causing character-by-character rendering.
function toLines(text) {
  if (!text || typeof text !== 'string') return [];
  return text.split('\n').map(l => l.trim()).filter(l => l.length > 0);
}

// Pre-computed line arrays for fields that could be strings
const effectivenessFactorLines = computed(() => toLines(channel.value?.effectiveness_factors));
const useCaseLines = computed(() => toLines(channel.value?.use_cases));
const recommendedForLines = computed(() => toLines(channel.value?.recommended_for));

// --- Plan info ---
const planOrder = { trial: 0, starter: 1, pro: 2, enterprise: 3 };
const planLabel = computed(() => {
  const labels = { trial: 'Trial', starter: 'Starter', pro: 'Pro', enterprise: 'Enterprise' };
  return labels[agencyPlan.value] || agencyPlan.value;
});
const planColor = computed(() => {
  const map = { trial: 'text-gray-600', starter: 'text-blue-600', pro: 'text-indigo-600', enterprise: 'text-purple-600' };
  return map[agencyPlan.value] || 'text-gray-600';
});

// --- Style helpers ---
function effectivenessClass(val) {
  const map = { low: 'bg-gray-100 text-gray-600', medium: 'bg-amber-50 text-amber-600', high: 'bg-green-50 text-green-600', very_high: 'bg-emerald-50 text-emerald-700' };
  return map[val] || 'bg-gray-100 text-gray-600';
}
function effectivenessTextClass(val) {
  const map = { low: 'text-gray-500', medium: 'text-amber-600', high: 'text-green-600', very_high: 'text-emerald-700' };
  return map[val] || 'text-gray-500';
}
function complexityClass(val) {
  const map = { easy: 'bg-green-50 text-green-600', medium: 'bg-amber-50 text-amber-600', hard: 'bg-red-50 text-red-600' };
  return map[val] || 'bg-gray-100 text-gray-600';
}
function complexityTextClass(val) {
  const map = { easy: 'text-green-600', medium: 'text-amber-600', hard: 'text-red-600' };
  return map[val] || 'text-gray-500';
}

// --- Data loading ---
async function fetchChannel() {
  try {
    loading.value = true;
    const code = route.params.code;
    const { data } = await api.get(`/lead-channels/${code}`);
    const payload = data?.data || data;
    channel.value = payload.channel;
    agencyPlan.value = payload.agency_plan || '';
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
    // tracking should not block UX
  }
}

const connecting = ref(false);
const connectSuccess = ref(false);

async function handleCta(action) {
  trackAction('click_cta');

  if (action === 'connect') {
    if (!channel.value?.available) return;
    connecting.value = true;
    try {
      const code = route.params.code;
      await api.post(`/lead-channels/${code}/connect`);
      connectSuccess.value = true;
      channel.value.connected = true;
    } catch {
      // silently fail
    } finally {
      connecting.value = false;
    }
  } else if (action === 'instruction') {
    // Scroll to integration section
    const el = document.querySelector('[data-section="integration"]');
    if (el) el.scrollIntoView({ behavior: 'smooth' });
  }
}

onMounted(async () => {
  await fetchChannel();
  if (channel.value) trackAction('view');
});
</script>

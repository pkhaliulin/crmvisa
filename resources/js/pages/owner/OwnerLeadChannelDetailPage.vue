<template>
  <div class="space-y-6">
    <!-- Загрузка -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
    </div>

    <template v-else-if="form">
      <!-- Назад + заголовок -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button @click="router.push({ name: 'owner.lead-channels' })"
            class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ form.name || t('owner.leadChannelDetail.editChannel') }}</h1>
            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ form.code }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-xs" :class="form.is_active ? 'text-green-600' : 'text-amber-600'">
            {{ form.is_active ? t('owner.leadChannelDetail.active') : t('owner.leadChannelDetail.disabledSoon') }}
          </span>
          <button @click="form.is_active = !form.is_active"
            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
            :class="form.is_active ? 'bg-green-500' : 'bg-gray-200'">
            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
              :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
          </button>
        </div>
      </div>

      <!-- Основная информация -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('owner.leadChannelDetail.basicInfo') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.codeSlug') }}</label>
            <input v-model="form.code" maxlength="50"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
            <span class="text-xs text-gray-400">{{ form.code.length }}/50</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.iconEmoji') }}</label>
            <input v-model="form.icon" maxlength="10"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.nameRu') }}</label>
            <input v-model="form.name" maxlength="255"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
            <span class="text-xs text-gray-400">{{ form.name.length }}/255</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.nameUz') }}</label>
            <input v-model="form.name_uz" maxlength="255"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
        </div>
      </div>

      <!-- Классификация -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('owner.leadChannelDetail.classification') }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.category') }}</label>
            <SearchSelect v-model="form.category" :items="categoryItems" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.effectiveness') }}</label>
            <SearchSelect v-model="form.effectiveness" :items="effectivenessItems" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.complexity') }}</label>
            <SearchSelect v-model="form.complexity" :items="complexityItems" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.launchSpeed') }}</label>
            <SearchSelect v-model="form.launch_speed" :items="launchSpeedItems" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.minPlan') }}</label>
            <SearchSelect v-model="form.min_plan" :items="minPlanItems" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.api') }}</label>
            <SearchSelect v-model="form.requires_api" :items="apiItems" />
          </div>
        </div>

        <div class="flex flex-wrap gap-6 mt-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.requires_budget" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">{{ t('owner.leadChannelDetail.requiresBudget') }}</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.enterprise_only" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">{{ t('owner.leadChannelDetail.enterpriseOnly') }}</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.coming_soon" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">{{ t('owner.leadChannelDetail.comingSoonBadge') }}</span>
          </label>
        </div>
      </div>

      <!-- Описания (RU) -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('owner.leadChannelDetail.descriptionsRu') }}</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.shortDescription') }}</label>
            <textarea v-model="form.short_description" rows="2" maxlength="500"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-none"></textarea>
            <span class="text-xs text-gray-400">{{ (form.short_description || '').length }}/500</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.fullDescription') }}</label>
            <textarea v-model="form.full_description" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.howItWorks') }}</label>
            <textarea v-model="form.how_it_works" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.whenToUse') }}</label>
              <textarea v-model="form.when_to_use" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.whenNotToUse') }}</label>
              <textarea v-model="form.when_not_to_use" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Описания (UZ) -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('owner.leadChannelDetail.descriptionsUz') }}</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.shortDescriptionUz') }}</label>
            <textarea v-model="form.short_description_uz" rows="2" maxlength="500"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-none"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.fullDescriptionUz') }}</label>
            <textarea v-model="form.full_description_uz" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.howItWorksUz') }}</label>
            <textarea v-model="form.how_it_works_uz" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
        </div>
      </div>

      <!-- Доп. информация -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ t('owner.leadChannelDetail.additionalInfo') }}</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.useCases') }}</label>
            <textarea v-model="form.use_cases" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.effectivenessFactors') }}</label>
            <textarea v-model="form.effectiveness_factors" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.requiredPreparation') }}</label>
            <textarea v-model="form.required_preparation" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.expectedResult') }}</label>
            <textarea v-model="form.expected_result" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.risks') }}</label>
              <textarea v-model="form.risks" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.bestPractices') }}</label>
              <textarea v-model="form.best_practices" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.trends') }}</label>
            <textarea v-model="form.trends" rows="2"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.recommendedFor') }}</label>
            <input v-model="form.recommended_for"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400"
              placeholder="starter, pro, enterprise" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('owner.leadChannelDetail.sortOrder') }}</label>
            <input v-model.number="form.sort_order" type="number" min="0"
              class="w-32 px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
        </div>
      </div>

      <!-- Кнопка сохранения -->
      <div class="flex justify-end gap-3">
        <button @click="router.push({ name: 'owner.lead-channels' })"
          class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
          {{ t('common.cancel') }}
        </button>
        <button @click="handleSave" :disabled="saving"
          class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
          {{ saving ? t('owner.leadChannelDetail.saving') : t('owner.leadChannelDetail.saveChanges') }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const form = ref(null);

const categoryOptions = computed(() => [
  { key: 'messenger', label: t('owner.leadChannelDetail.categories.messenger') },
  { key: 'advertising', label: t('owner.leadChannelDetail.categories.advertising') },
  { key: 'web', label: t('owner.leadChannelDetail.categories.web') },
  { key: 'content_seo', label: t('owner.leadChannelDetail.categories.content_seo') },
  { key: 'partnership', label: t('owner.leadChannelDetail.categories.partnership') },
  { key: 'api_automation', label: t('owner.leadChannelDetail.categories.api_automation') },
]);

const effectivenessOptions = computed(() => [
  { key: 'low', label: t('owner.leadChannelDetail.effectivenessOptions.low') },
  { key: 'medium', label: t('owner.leadChannelDetail.effectivenessOptions.medium') },
  { key: 'high', label: t('owner.leadChannelDetail.effectivenessOptions.high') },
  { key: 'very_high', label: t('owner.leadChannelDetail.effectivenessOptions.very_high') },
]);

const complexityOptions = computed(() => [
  { key: 'easy', label: t('owner.leadChannelDetail.complexities.easy') },
  { key: 'medium', label: t('owner.leadChannelDetail.complexities.medium') },
  { key: 'hard', label: t('owner.leadChannelDetail.complexities.hard') },
]);

const launchSpeedOptions = computed(() => [
  { key: 'instant', label: t('owner.leadChannelDetail.launchSpeeds.instant') },
  { key: 'fast', label: t('owner.leadChannelDetail.launchSpeeds.fast') },
  { key: 'medium', label: t('owner.leadChannelDetail.launchSpeeds.medium') },
  { key: 'slow', label: t('owner.leadChannelDetail.launchSpeeds.slow') },
]);

const apiOptions = computed(() => [
  { key: 'no', label: t('owner.leadChannelDetail.apiOptions.no') },
  { key: 'optional', label: t('owner.leadChannelDetail.apiOptions.optional') },
  { key: 'required', label: t('owner.leadChannelDetail.apiOptions.required') },
]);

const categoryItems = computed(() => categoryOptions.value.map(o => ({ value: o.key, label: o.label })));
const effectivenessItems = computed(() => effectivenessOptions.value.map(o => ({ value: o.key, label: o.label })));
const complexityItems = computed(() => complexityOptions.value.map(o => ({ value: o.key, label: o.label })));
const launchSpeedItems = computed(() => launchSpeedOptions.value.map(o => ({ value: o.key, label: o.label })));
const apiItems = computed(() => apiOptions.value.map(o => ({ value: o.key, label: o.label })));

const minPlanItems = [
  { value: 'trial', label: 'Trial' },
  { value: 'starter', label: 'Starter' },
  { value: 'pro', label: 'Pro' },
  { value: 'enterprise', label: 'Enterprise' },
];

async function fetchChannel() {
  try {
    loading.value = true;
    const { data } = await api.get(`/owner/lead-channels/${route.params.id}`);
    form.value = { ...(data?.data || data) };
  } catch (e) {
    alert(e?.response?.data?.message || t('owner.leadChannelDetail.loadError'));
    router.push({ name: 'owner.lead-channels' });
  } finally {
    loading.value = false;
  }
}

async function handleSave() {
  try {
    saving.value = true;
    await api.patch(`/owner/lead-channels/${route.params.id}`, form.value);
    alert(t('owner.leadChannelDetail.saved'));
    router.push({ name: 'owner.lead-channels' });
  } catch (e) {
    alert(e?.response?.data?.message || t('owner.leadChannelDetail.saveError'));
  } finally {
    saving.value = false;
  }
}

onMounted(fetchChannel);
</script>

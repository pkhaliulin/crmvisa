<template>
  <div class="space-y-6">
    <!-- Большой статус-бейдж -->
    <div v-if="!loading" class="rounded-xl border-2 p-4 flex items-center gap-4"
      :class="settings['site.enabled']
        ? 'border-green-200 bg-green-50'
        : 'border-red-200 bg-red-50'">
      <div class="w-3 h-3 rounded-full shrink-0"
        :class="settings['site.enabled'] ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></div>
      <div class="flex-1">
        <div class="text-sm font-bold"
          :class="settings['site.enabled'] ? 'text-green-800' : 'text-red-800'">
          {{ settings['site.enabled'] ? t('owner.website.siteEnabled') : t('owner.website.siteDisabled') }}
        </div>
        <div class="text-xs mt-0.5"
          :class="settings['site.enabled'] ? 'text-green-600' : 'text-red-600'">
          {{ settings['site.enabled']
            ? t('owner.website.siteEnabledDesc')
            : t('owner.website.siteDisabledDesc') }}
        </div>
      </div>
      <a href="/" target="_blank"
        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors shrink-0"
        :class="settings['site.enabled']
          ? 'text-green-700 border-green-300 hover:bg-green-100'
          : 'text-red-700 border-red-300 hover:bg-red-100'">
        {{ t('owner.website.openSite') }}
      </a>
    </div>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800">{{ t('owner.website.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ t('owner.website.subtitle') }}</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="clearCache" :disabled="clearing"
          class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200
                 rounded-lg hover:bg-amber-100 transition-colors disabled:opacity-50">
          {{ clearing ? t('owner.website.clearing') : t('owner.website.clearCache') }}
        </button>
        <button @click="save" :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-[#1BA97F] rounded-lg
                 hover:bg-[#169B72] transition-colors disabled:opacity-50">
          {{ saving ? t('owner.website.saving') : t('common.save') }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="w-6 h-6 border-2 border-gray-200 border-t-[#1BA97F] rounded-full animate-spin"></div>
    </div>

    <template v-else>
      <!-- Состояние сайта -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">🔴</span> {{ t('owner.website.siteState') }}
          </h2>
        </div>
        <div class="divide-y divide-gray-50">
          <SettingToggle :label="t('owner.website.publicSiteEnabled')" :desc="t('owner.website.publicSiteEnabledDesc')"
            v-model="settings['site.enabled']" />
          <SettingToggle :label="t('owner.website.maintenanceBanner')" :desc="t('owner.website.maintenanceBannerDesc')"
            v-model="settings['site.maintenance_banner']" />
          <SettingInput :label="t('owner.website.maintenanceText')" v-model="settings['site.maintenance_text']"
            v-if="settings['site.maintenance_banner']" />
        </div>
      </div>

      <!-- SEO -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">🔍</span> {{ t('owner.website.seoParams') }}
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <SettingInput :label="t('owner.website.pageTitle')" v-model="settings['seo.title']" />
          <SettingTextarea :label="t('owner.website.metaDescription')" v-model="settings['seo.description']" />
          <SettingInput :label="t('owner.website.keywords')" v-model="settings['seo.keywords']" />
        </div>
      </div>

      <!-- Контент -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">✏️</span> {{ t('owner.website.mainContent') }}
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <SettingInput :label="t('owner.website.heroTitle')" v-model="settings['hero.title']" />
          <SettingTextarea :label="t('owner.website.heroSubtitle')" v-model="settings['hero.subtitle']" />
          <SettingInput :label="t('owner.website.ctaText')" v-model="settings['hero.cta_text']" />
        </div>
      </div>

      <!-- Контакты -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">📞</span> {{ t('owner.website.contacts') }}
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <SettingInput :label="t('owner.website.phone')" v-model="settings['contact.phone']" />
          <SettingInput :label="t('owner.website.email')" v-model="settings['contact.email']" />
          <SettingInput :label="t('owner.website.telegram')" v-model="settings['contact.telegram']" />
          <SettingInput :label="t('owner.website.address')" v-model="settings['contact.address']" />
        </div>
      </div>

      <!-- Видимость блоков -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">👁️</span> {{ t('owner.website.sectionsVisibility') }}
          </h2>
        </div>
        <div class="divide-y divide-gray-50">
          <SettingToggle :label="t('owner.website.sectionHero')" v-model="settings['sections.hero']" />
          <SettingToggle :label="t('owner.website.sectionScoring')" v-model="settings['sections.scoring']" />
          <SettingToggle :label="t('owner.website.sectionDestinations')" v-model="settings['sections.destinations']" />
          <SettingToggle :label="t('owner.website.sectionAgencies')" v-model="settings['sections.agencies']" />
          <SettingToggle :label="t('owner.website.sectionTrust')" v-model="settings['sections.trust']" />
          <SettingToggle :label="t('owner.website.sectionCompare')" v-model="settings['sections.compare']" />
          <SettingToggle :label="t('owner.website.sectionApp')" v-model="settings['sections.app']" />
          <SettingToggle :label="t('owner.website.sectionTelegram')" v-model="settings['sections.telegram']" />
          <SettingToggle :label="t('owner.website.sectionCabinet')" v-model="settings['sections.cabinet']" />
          <SettingToggle :label="t('owner.website.sectionFaq')" v-model="settings['sections.faq']" />
        </div>
      </div>

      <!-- SEO-страницы -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">📄</span> {{ t('owner.website.seoPages') }}
          </h2>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-500 mb-4">{{ t('owner.website.seoPagesDesc') }}</p>
          <div class="space-y-2">
            <a href="/about" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">{{ t('owner.website.aboutPlatform') }}</div>
                <div class="text-xs text-gray-400">/about</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">{{ t('owner.website.pageActive') }}</span>
            </a>
            <a href="/privacy" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">{{ t('owner.website.privacyPolicy') }}</div>
                <div class="text-xs text-gray-400">/privacy</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">{{ t('owner.website.pageActive') }}</span>
            </a>
            <a href="/terms" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">{{ t('owner.website.termsOfService') }}</div>
                <div class="text-xs text-gray-400">/terms</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">{{ t('owner.website.pageActive') }}</span>
            </a>
            <a href="/sitemap.xml" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">{{ t('owner.website.sitemapXml') }}</div>
                <div class="text-xs text-gray-400">/sitemap.xml</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">{{ t('owner.website.pageActive') }}</span>
            </a>
          </div>
          <p class="text-xs text-gray-400 mt-4">
            {{ t('owner.website.countryPagesNote') }}
          </p>
        </div>
      </div>
    </template>

    <!-- Toast -->
    <transition name="slide-up">
      <div v-if="toast" class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-sm font-medium z-50"
        :class="toast.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'">
        {{ toast.text }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

// Inline sub-components
const SettingToggle = {
  props: ['label', 'desc', 'modelValue'],
  emits: ['update:modelValue'],
  template: `
    <div class="flex items-center justify-between px-6 py-4">
      <div>
        <div class="text-sm font-medium text-gray-700">{{ label }}</div>
        <div v-if="desc" class="text-xs text-gray-400 mt-0.5">{{ desc }}</div>
      </div>
      <button @click="$emit('update:modelValue', !modelValue)"
        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
        :class="modelValue ? 'bg-[#1BA97F]' : 'bg-gray-200'">
        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
          :class="modelValue ? 'translate-x-6' : 'translate-x-1'" />
      </button>
    </div>
  `
};

const SettingInput = {
  props: ['label', 'modelValue'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
      <input :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1
               focus:ring-[#1BA97F] focus:border-[#1BA97F] outline-none" />
    </div>
  `
};

const SettingTextarea = {
  props: ['label', 'modelValue'],
  emits: ['update:modelValue'],
  template: `
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
      <textarea :value="modelValue" @input="$emit('update:modelValue', $event.target.value)"
        rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-1
               focus:ring-[#1BA97F] focus:border-[#1BA97F] outline-none resize-none" />
    </div>
  `
};

const loading = ref(true);
const saving = ref(false);
const clearing = ref(false);
const toast = ref(null);
const settings = reactive({});

function showToast(text, type = 'success') {
  toast.value = { text, type };
  setTimeout(() => { toast.value = null; }, 3000);
}

async function load() {
  loading.value = true;
  try {
    const { data } = await api.get('/owner/website-settings');
    Object.assign(settings, data.data.settings);
  } catch (e) {
    showToast(t('owner.website.loadError'), 'error');
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  try {
    await api.put('/owner/website-settings', { settings });
    showToast(t('owner.website.settingsSaved'));
  } catch (e) {
    showToast(t('owner.website.saveError'), 'error');
  } finally {
    saving.value = false;
  }
}

async function clearCache() {
  clearing.value = true;
  try {
    await api.post('/owner/website-settings/clear-cache');
    showToast(t('owner.website.cacheCleared'));
  } catch (e) {
    showToast(t('owner.website.cacheClearError'), 'error');
  } finally {
    clearing.value = false;
  }
}

onMounted(load);
</script>

<style scoped>
.slide-up-enter-active, .slide-up-leave-active { transition: all 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(20px); opacity: 0; }
</style>

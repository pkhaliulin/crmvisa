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
          {{ settings['site.enabled'] ? 'Сайт включён и доступен' : 'Сайт отключён — только форма входа' }}
        </div>
        <div class="text-xs mt-0.5"
          :class="settings['site.enabled'] ? 'text-green-600' : 'text-red-600'">
          {{ settings['site.enabled']
            ? 'Все посетители видят полную версию visabor.uz'
            : 'Неавторизованные посетители видят только экран входа. Авторизованные работают как обычно.' }}
        </div>
      </div>
      <a href="/" target="_blank"
        class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors shrink-0"
        :class="settings['site.enabled']
          ? 'text-green-700 border-green-300 hover:bg-green-100'
          : 'text-red-700 border-red-300 hover:bg-red-100'">
        Открыть сайт
      </a>
    </div>

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Сайт VisaBor</h1>
        <p class="text-sm text-gray-500 mt-1">Управление контентом и настройками visabor.uz</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="clearCache" :disabled="clearing"
          class="px-4 py-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200
                 rounded-lg hover:bg-amber-100 transition-colors disabled:opacity-50">
          {{ clearing ? 'Очистка...' : 'Очистить кэш' }}
        </button>
        <button @click="save" :disabled="saving"
          class="px-4 py-2 text-sm font-medium text-white bg-[#1BA97F] rounded-lg
                 hover:bg-[#169B72] transition-colors disabled:opacity-50">
          {{ saving ? 'Сохранение...' : 'Сохранить' }}
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
            <span class="text-base">🔴</span> Состояние сайта
          </h2>
        </div>
        <div class="divide-y divide-gray-50">
          <SettingToggle label="Публичный сайт включён" desc="Если выключить — виден только экран входа"
            v-model="settings['site.enabled']" />
          <SettingToggle label="Баннер технических работ" desc="Показать предупреждение вверху страницы"
            v-model="settings['site.maintenance_banner']" />
          <SettingInput label="Текст баннера" v-model="settings['site.maintenance_text']"
            v-if="settings['site.maintenance_banner']" />
        </div>
      </div>

      <!-- SEO -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">🔍</span> SEO-параметры
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <SettingInput label="Title страницы" v-model="settings['seo.title']" />
          <SettingTextarea label="Meta Description" v-model="settings['seo.description']" />
          <SettingInput label="Keywords" v-model="settings['seo.keywords']" />
        </div>
      </div>

      <!-- Контент -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">✏️</span> Контент главной
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <SettingInput label="Главный заголовок H1" v-model="settings['hero.title']" />
          <SettingTextarea label="Подзаголовок Hero" v-model="settings['hero.subtitle']" />
          <SettingInput label="Текст CTA кнопки" v-model="settings['hero.cta_text']" />
        </div>
      </div>

      <!-- Контакты -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">📞</span> Контакты
          </h2>
        </div>
        <div class="p-6 space-y-4">
          <SettingInput label="Телефон" v-model="settings['contact.phone']" />
          <SettingInput label="Email" v-model="settings['contact.email']" />
          <SettingInput label="Telegram" v-model="settings['contact.telegram']" />
          <SettingInput label="Адрес" v-model="settings['contact.address']" />
        </div>
      </div>

      <!-- Видимость блоков -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">👁️</span> Видимость блоков
          </h2>
        </div>
        <div class="divide-y divide-gray-50">
          <SettingToggle label="Hero-секция" v-model="settings['sections.hero']" />
          <SettingToggle label="AI Scoring / Анкета" v-model="settings['sections.scoring']" />
          <SettingToggle label="Направления" v-model="settings['sections.destinations']" />
          <SettingToggle label="Агентства" v-model="settings['sections.agencies']" />
          <SettingToggle label="Trust / Отзывы" v-model="settings['sections.trust']" />
          <SettingToggle label="Сравнение" v-model="settings['sections.compare']" />
          <SettingToggle label="Мобильное приложение" v-model="settings['sections.app']" />
          <SettingToggle label="Telegram-бот" v-model="settings['sections.telegram']" />
          <SettingToggle label="Личный кабинет" v-model="settings['sections.cabinet']" />
          <SettingToggle label="FAQ" v-model="settings['sections.faq']" />
        </div>
      </div>

      <!-- SEO-страницы -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
          <h2 class="font-semibold text-gray-700 flex items-center gap-2">
            <span class="text-base">📄</span> SEO-страницы
          </h2>
        </div>
        <div class="p-6">
          <p class="text-sm text-gray-500 mb-4">Публичные страницы для поисковых систем и AI-агрегаторов</p>
          <div class="space-y-2">
            <a href="/about" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">О платформе</div>
                <div class="text-xs text-gray-400">/about</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Активна</span>
            </a>
            <a href="/privacy" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">Политика конфиденциальности</div>
                <div class="text-xs text-gray-400">/privacy</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Активна</span>
            </a>
            <a href="/terms" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">Пользовательское соглашение</div>
                <div class="text-xs text-gray-400">/terms</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Активна</span>
            </a>
            <a href="/sitemap.xml" target="_blank" class="flex items-center justify-between p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
              <div>
                <div class="font-medium text-sm text-gray-700">Sitemap XML</div>
                <div class="text-xs text-gray-400">/sitemap.xml</div>
              </div>
              <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Активна</span>
            </a>
          </div>
          <p class="text-xs text-gray-400 mt-4">
            Страницы стран (/country/de, /country/jp и т.д.) генерируются автоматически для каждой активной страны в разделе "Страны и веса".
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
import api from '@/api/index';

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
    showToast('Ошибка загрузки настроек', 'error');
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  try {
    await api.put('/owner/website-settings', { settings });
    showToast('Настройки сохранены');
  } catch (e) {
    showToast('Ошибка сохранения', 'error');
  } finally {
    saving.value = false;
  }
}

async function clearCache() {
  clearing.value = true;
  try {
    await api.post('/owner/website-settings/clear-cache');
    showToast('Кэш очищен');
  } catch (e) {
    showToast('Ошибка очистки кэша', 'error');
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

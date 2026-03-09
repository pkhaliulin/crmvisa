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
            <h1 class="text-xl font-bold text-gray-900">{{ form.name || 'Редактирование канала' }}</h1>
            <p class="text-xs text-gray-400 font-mono mt-0.5">{{ form.code }}</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-xs" :class="form.is_active ? 'text-green-600' : 'text-amber-600'">
            {{ form.is_active ? 'Активен' : 'Отключён (СКОРО)' }}
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
        <h2 class="text-base font-semibold text-gray-900 mb-4">Основная информация</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Код (slug)</label>
            <input v-model="form.code" maxlength="50"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
            <span class="text-xs text-gray-400">{{ form.code.length }}/50</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Иконка (emoji)</label>
            <input v-model="form.icon" maxlength="10"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название (RU)</label>
            <input v-model="form.name" maxlength="255"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
            <span class="text-xs text-gray-400">{{ form.name.length }}/255</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название (UZ)</label>
            <input v-model="form.name_uz" maxlength="255"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
        </div>
      </div>

      <!-- Классификация -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Классификация</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
            <select v-model="form.category"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
              <option value="messenger">Мессенджеры</option>
              <option value="advertising">Реклама</option>
              <option value="web">Веб</option>
              <option value="content_seo">Контент и SEO</option>
              <option value="partnership">Партнёрства</option>
              <option value="api_automation">API и автоматизация</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Эффективность</label>
            <select v-model="form.effectiveness"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
              <option value="low">Низкая</option>
              <option value="medium">Средняя</option>
              <option value="high">Высокая</option>
              <option value="very_high">Очень высокая</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сложность</label>
            <select v-model="form.complexity"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
              <option value="easy">Легко</option>
              <option value="medium">Средне</option>
              <option value="hard">Сложно</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Скорость запуска</label>
            <select v-model="form.launch_speed"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
              <option value="instant">Мгновенно</option>
              <option value="fast">Быстро</option>
              <option value="medium">Средне</option>
              <option value="slow">Медленно</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Мин. план</label>
            <select v-model="form.min_plan"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
              <option value="trial">Trial</option>
              <option value="starter">Starter</option>
              <option value="pro">Pro</option>
              <option value="enterprise">Enterprise</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">API</label>
            <select v-model="form.requires_api"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white outline-none focus:border-blue-400">
              <option value="no">Не нужен</option>
              <option value="optional">Опционально</option>
              <option value="required">Обязателен</option>
            </select>
          </div>
        </div>

        <div class="flex flex-wrap gap-6 mt-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.requires_budget" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">Требует бюджет</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.enterprise_only" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">Только Enterprise</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.coming_soon" class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <span class="text-sm text-gray-700">Coming soon (бейдж)</span>
          </label>
        </div>
      </div>

      <!-- Описания (RU) -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Описания (RU)</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Краткое описание</label>
            <textarea v-model="form.short_description" rows="2" maxlength="500"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-none"></textarea>
            <span class="text-xs text-gray-400">{{ (form.short_description || '').length }}/500</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Полное описание</label>
            <textarea v-model="form.full_description" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Как это работает (шаги, по строкам)</label>
            <textarea v-model="form.how_it_works" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Когда использовать</label>
              <textarea v-model="form.when_to_use" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Когда НЕ использовать</label>
              <textarea v-model="form.when_not_to_use" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Описания (UZ) -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Описания (UZ)</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Qisqa tavsif</label>
            <textarea v-model="form.short_description_uz" rows="2" maxlength="500"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-none"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To'liq tavsif</label>
            <textarea v-model="form.full_description_uz" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Qanday ishlaydi</label>
            <textarea v-model="form.how_it_works_uz" rows="4"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
        </div>
      </div>

      <!-- Доп. информация -->
      <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Дополнительная информация</h2>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Варианты использования</label>
            <textarea v-model="form.use_cases" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Факторы эффективности</label>
            <textarea v-model="form.effectiveness_factors" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Подготовка к запуску</label>
            <textarea v-model="form.required_preparation" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Ожидаемый результат</label>
            <textarea v-model="form.expected_result" rows="3"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Риски</label>
              <textarea v-model="form.risks" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Лучшие практики</label>
              <textarea v-model="form.best_practices" rows="3"
                class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тренды</label>
            <textarea v-model="form.trends" rows="2"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400 resize-y"></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Рекомендовано для (через запятую)</label>
            <input v-model="form.recommended_for"
              class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400"
              placeholder="starter, pro, enterprise" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Порядок сортировки</label>
            <input v-model.number="form.sort_order" type="number" min="0"
              class="w-32 px-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-blue-400" />
          </div>
        </div>
      </div>

      <!-- Кнопка сохранения -->
      <div class="flex justify-end gap-3">
        <button @click="router.push({ name: 'owner.lead-channels' })"
          class="px-5 py-2.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
          Отмена
        </button>
        <button @click="handleSave" :disabled="saving"
          class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
          {{ saving ? 'Сохранение...' : 'Сохранить изменения' }}
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '@/api/index';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const form = ref(null);

async function fetchChannel() {
  try {
    loading.value = true;
    const { data } = await api.get(`/owner/lead-channels/${route.params.id}`);
    form.value = { ...data };
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка загрузки');
    router.push({ name: 'owner.lead-channels' });
  } finally {
    loading.value = false;
  }
}

async function handleSave() {
  try {
    saving.value = true;
    await api.patch(`/owner/lead-channels/${route.params.id}`, form.value);
    alert('Канал сохранён');
    router.push({ name: 'owner.lead-channels' });
  } catch (e) {
    alert(e?.response?.data?.message || 'Ошибка сохранения');
  } finally {
    saving.value = false;
  }
}

onMounted(fetchChannel);
</script>

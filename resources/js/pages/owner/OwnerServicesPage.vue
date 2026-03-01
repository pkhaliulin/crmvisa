<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Каталог услуг</h1>
        <p class="text-sm text-gray-500 mt-1">Глобальный справочник услуг платформы</p>
      </div>
      <button @click="openCreate"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        + Добавить услугу
      </button>
    </div>

    <!-- Фильтр по категории -->
    <div class="flex gap-2 flex-wrap">
      <button v-for="cat in ['all', ...categories]" :key="cat"
        @click="filterCat = cat"
        :class="['px-3 py-1 text-xs rounded-full border transition-colors',
          filterCat === cat
            ? 'bg-blue-600 text-white border-blue-600'
            : 'text-gray-600 border-gray-300 hover:bg-gray-50']">
        {{ cat === 'all' ? 'Все' : categoryLabel(cat) }}
      </button>
    </div>

    <div v-if="loading" class="text-center py-12 text-gray-400">Загрузка...</div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50">
          <tr class="text-left">
            <th class="px-4 py-3 font-medium text-gray-500">Название</th>
            <th class="px-4 py-3 font-medium text-gray-500">Slug</th>
            <th class="px-4 py-3 font-medium text-gray-500">Категория</th>
            <th class="px-4 py-3 font-medium text-gray-500">Статус</th>
            <th class="px-4 py-3 font-medium text-gray-500 w-24">Действия</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="svc in filteredServices" :key="svc.id"
            class="border-t border-gray-100 hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3 font-medium text-gray-900">{{ svc.name }}</td>
            <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ svc.slug }}</td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full">
                {{ categoryLabel(svc.category) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <button @click="toggleActive(svc)"
                :class="['text-xs px-2 py-0.5 rounded-full font-medium transition-colors',
                  svc.is_active
                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                    : 'bg-gray-100 text-gray-500 hover:bg-gray-200']">
                {{ svc.is_active ? 'Активна' : 'Неактивна' }}
              </button>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <button @click="openEdit(svc)"
                  class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                  Изменить
                </button>
                <button @click="deleteService(svc.id)"
                  class="text-xs text-red-500 hover:text-red-700 font-medium">
                  Удалить
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="filteredServices.length === 0">
            <td colspan="5" class="px-4 py-8 text-center text-gray-400">Нет услуг</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Модал -->
    <div v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-5 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">
            {{ editingId ? 'Редактировать услугу' : 'Новая услуга' }}
          </h2>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Slug *</label>
            <input v-model="form.slug" type="text" :disabled="!!editingId"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-gray-50"
              placeholder="consultation-initial" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
            <input v-model="form.name" type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Категория *</label>
            <select v-model="form.category"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option v-for="cat in categories" :key="cat" :value="cat">{{ categoryLabel(cat) }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
            <textarea v-model="form.description" rows="2"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_active"
                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
              Активна
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_combinable"
                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
              Комбинируемая
            </label>
            <label class="flex items-center gap-2 text-sm cursor-pointer">
              <input type="checkbox" v-model="form.is_optional"
                class="w-4 h-4 text-blue-600 rounded border-gray-300" />
              Опциональная
            </label>
          </div>
        </div>

        <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
          <button @click="showModal = false"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            Отмена
          </button>
          <button @click="saveService" :disabled="saving"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '@/api/index';

const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);
const filterCat = ref('all');

const services = ref([]);

const categories = ['consultation', 'documents', 'translation', 'visa_center', 'financial', 'other'];

const categoryLabels = {
  consultation: 'Консультация',
  documents: 'Документы',
  translation: 'Перевод',
  visa_center: 'Визовый центр',
  financial: 'Финансы',
  other: 'Прочее',
};

function categoryLabel(cat) { return categoryLabels[cat] || cat; }

const filteredServices = computed(() => {
  if (filterCat.value === 'all') return services.value;
  return services.value.filter(s => s.category === filterCat.value);
});

const defaultForm = () => ({
  slug: '', name: '', category: 'consultation', description: '',
  is_active: true, is_combinable: true, is_optional: true, sort_order: 0,
});

const form = ref(defaultForm());

onMounted(async () => {
  try {
    const res = await api.get('/owner/services');
    services.value = res.data.data || [];
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
});

function openCreate() {
  editingId.value = null;
  form.value = defaultForm();
  showModal.value = true;
}

function openEdit(svc) {
  editingId.value = svc.id;
  form.value = { ...svc };
  showModal.value = true;
}

async function saveService() {
  saving.value = true;
  try {
    if (editingId.value) {
      const res = await api.patch(`/owner/services/${editingId.value}`, form.value);
      const idx = services.value.findIndex(s => s.id === editingId.value);
      if (idx !== -1) services.value[idx] = res.data.data;
    } else {
      const res = await api.post('/owner/services', form.value);
      services.value.unshift(res.data.data);
    }
    showModal.value = false;
  } catch {
    // ignore
  } finally {
    saving.value = false;
  }
}

async function toggleActive(svc) {
  svc.is_active = !svc.is_active;
  await api.patch(`/owner/services/${svc.id}`, { is_active: svc.is_active }).catch(() => {
    svc.is_active = !svc.is_active;
  });
}

async function deleteService(id) {
  if (!confirm('Удалить услугу?')) return;
  await api.delete(`/owner/services/${id}`);
  services.value = services.value.filter(s => s.id !== id);
}
</script>

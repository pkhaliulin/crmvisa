<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Услуги агентства</h1>
        <p class="text-sm text-gray-500 mt-1">Пакеты услуг по направлениям</p>
      </div>
      <button @click="openCreate"
        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        + Новый пакет
      </button>
    </div>

    <!-- Каталог услуг -->
    <div v-if="loadingServices" class="text-sm text-gray-400">Загрузка каталога...</div>
    <div v-else class="bg-white rounded-xl border border-gray-200 p-4">
      <h2 class="text-sm font-semibold text-gray-600 mb-3">Глобальный каталог услуг</h2>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <div v-for="svc in globalServices" :key="svc.id"
          class="flex items-center gap-2 text-sm text-gray-700 p-2 bg-gray-50 rounded-lg">
          <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
          <span class="truncate">{{ svc.name }}</span>
          <span class="text-xs text-gray-400 ml-auto">{{ categoryLabel(svc.category) }}</span>
        </div>
      </div>
    </div>

    <!-- Мои пакеты -->
    <div v-if="loadingPackages" class="text-center py-8 text-gray-400">Загрузка пакетов...</div>

    <div v-else-if="packages.length === 0" class="text-center py-12 text-gray-400">
      <p>Пакетов ещё нет. Создайте первый!</p>
    </div>

    <div v-else class="grid gap-4">
      <div v-for="pkg in packages" :key="pkg.id"
        class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-start justify-between">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="font-semibold text-gray-900">{{ pkg.name }}</h3>
              <span v-if="!pkg.is_active"
                class="text-xs px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full">Неактивен</span>
            </div>
            <p v-if="pkg.description" class="text-sm text-gray-500 mt-1">{{ pkg.description }}</p>
            <div class="flex gap-4 mt-2 text-xs text-gray-400">
              <span v-if="pkg.country_code">{{ pkg.country_code }}</span>
              <span v-if="pkg.visa_type">{{ pkg.visa_type }}</span>
              <span v-if="pkg.price">{{ pkg.price }} {{ pkg.currency }}</span>
              <span v-if="pkg.processing_days">{{ pkg.processing_days }} дн.</span>
            </div>
          </div>
          <div class="flex gap-2">
            <button @click="openEdit(pkg)"
              class="text-xs px-3 py-1.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              Изменить
            </button>
            <button @click="deletePackage(pkg.id)"
              class="text-xs px-3 py-1.5 border border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
              Удалить
            </button>
          </div>
        </div>

        <div v-if="pkg.items?.length" class="mt-3 flex flex-wrap gap-1">
          <span v-for="item in pkg.items" :key="item.id"
            class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full">
            {{ item.service?.name }}
          </span>
        </div>
      </div>
    </div>

    <!-- Модал создания/редактирования -->
    <div v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-5 border-b border-gray-100">
          <h2 class="font-semibold text-gray-900">
            {{ editingId ? 'Редактировать пакет' : 'Новый пакет' }}
          </h2>
        </div>

        <div class="p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
            <input v-model="modalForm.name" type="text"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Страна (ISO)</label>
              <input v-model="modalForm.country_code" type="text" maxlength="2" placeholder="DE"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Тип визы</label>
              <input v-model="modalForm.visa_type" type="text" placeholder="tourist"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Цена</label>
              <input v-model.number="modalForm.price" type="number" min="0"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Срок (дней)</label>
              <input v-model.number="modalForm.processing_days" type="number" min="1"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
            <textarea v-model="modalForm.description" rows="2"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Включённые услуги</label>
            <div class="max-h-48 overflow-y-auto space-y-1 border border-gray-200 rounded-lg p-2">
              <label v-for="svc in globalServices" :key="svc.id"
                class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 p-1 rounded">
                <input type="checkbox" :value="svc.id" v-model="modalForm.service_ids"
                  class="w-4 h-4 text-blue-600 rounded border-gray-300" />
                <span>{{ svc.name }}</span>
                <span class="text-xs text-gray-400 ml-auto">{{ categoryLabel(svc.category) }}</span>
              </label>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input type="checkbox" v-model="modalForm.is_active" id="pkg-active"
              class="w-4 h-4 text-blue-600 rounded border-gray-300" />
            <label for="pkg-active" class="text-sm text-gray-700">Активный пакет</label>
          </div>
        </div>

        <div class="p-5 border-t border-gray-100 flex justify-end gap-3">
          <button @click="showModal = false"
            class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
            Отмена
          </button>
          <button @click="savePackage" :disabled="saving"
            class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
            {{ saving ? 'Сохранение...' : 'Сохранить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/index';

const loadingPackages = ref(true);
const loadingServices = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingId = ref(null);

const packages = ref([]);
const globalServices = ref([]);

const defaultForm = () => ({
  name: '',
  country_code: '',
  visa_type: '',
  description: '',
  price: null,
  currency: 'USD',
  processing_days: null,
  is_active: true,
  service_ids: [],
});

const modalForm = ref(defaultForm());

const categoryLabels = {
  consultation: 'Консультация',
  documents: 'Документы',
  translation: 'Перевод',
  visa_center: 'Визовый центр',
  financial: 'Финансы',
  other: 'Прочее',
};

function categoryLabel(cat) {
  return categoryLabels[cat] || cat;
}

onMounted(async () => {
  const [pkgRes, svcRes] = await Promise.all([
    api.get('/agency/packages').catch(() => ({ data: { data: [] } })),
    api.get('/services').catch(() => ({ data: { data: [] } })),
  ]);
  packages.value = pkgRes.data.data || [];
  globalServices.value = svcRes.data.data || [];
  loadingPackages.value = false;
  loadingServices.value = false;
});

function openCreate() {
  editingId.value = null;
  modalForm.value = defaultForm();
  showModal.value = true;
}

function openEdit(pkg) {
  editingId.value = pkg.id;
  modalForm.value = {
    name: pkg.name || '',
    country_code: pkg.country_code || '',
    visa_type: pkg.visa_type || '',
    description: pkg.description || '',
    price: pkg.price,
    currency: pkg.currency || 'USD',
    processing_days: pkg.processing_days,
    is_active: pkg.is_active,
    service_ids: (pkg.items || []).map(i => i.service_id),
  };
  showModal.value = true;
}

async function savePackage() {
  saving.value = true;
  try {
    const payload = { ...modalForm.value };
    if (!payload.country_code) delete payload.country_code;
    if (!payload.visa_type) delete payload.visa_type;

    if (editingId.value) {
      const res = await api.patch(`/agency/packages/${editingId.value}`, payload);
      const idx = packages.value.findIndex(p => p.id === editingId.value);
      if (idx !== -1) packages.value[idx] = res.data.data;
    } else {
      const res = await api.post('/agency/packages', payload);
      packages.value.unshift(res.data.data);
    }
    showModal.value = false;
  } catch {
    // ignore
  } finally {
    saving.value = false;
  }
}

async function deletePackage(id) {
  if (!confirm('Удалить пакет?')) return;
  await api.delete(`/agency/packages/${id}`);
  packages.value = packages.value.filter(p => p.id !== id);
}
</script>

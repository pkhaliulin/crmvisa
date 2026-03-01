<template>
  <div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-bold text-gray-900 mb-6">Новая заявка</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Client search -->
        <div class="relative">
          <label class="text-sm font-medium text-gray-700 block mb-1">
            Клиент <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="clientSearch"
              @input="onClientInput"
              @focus="onClientFocus"
              placeholder="Начните вводить имя или телефон..."
              :class="[
                'w-full border rounded-lg px-3 py-2 text-sm outline-none pr-8',
                form.client_id
                  ? 'border-green-500 bg-green-50 text-green-800 font-medium'
                  : errors.client_id ? 'border-red-400' : 'border-gray-300 focus:border-blue-500'
              ]"
            />
            <!-- Иконка очистки если клиент выбран -->
            <button v-if="form.client_id" type="button" @click="clearClient"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">
              ×
            </button>
          </div>
          <!-- Дропдаун результатов -->
          <div v-if="clientResults.length"
            class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg divide-y text-sm max-h-48 overflow-y-auto">
            <button v-for="c in clientResults" :key="c.id" type="button"
              class="w-full text-left px-3 py-2.5 hover:bg-blue-50 flex items-center gap-3"
              @click="selectClient(c)">
              <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                {{ c.name?.[0] }}
              </div>
              <div>
                <div class="font-medium text-gray-900">{{ c.name }}</div>
                <div class="text-xs text-gray-400">{{ c.phone }}</div>
              </div>
            </button>
          </div>
          <!-- Нет результатов -->
          <div v-if="showNoResults"
            class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg px-3 py-2.5 text-sm text-gray-400">
            Клиент не найден. <RouterLink :to="{ name: 'clients.create' }" class="text-blue-600 hover:underline">Создать нового</RouterLink>
          </div>
          <p v-if="errors.client_id" class="text-xs text-red-600 mt-1">{{ errors.client_id }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.country_code" label="Страна (ISO)" placeholder="DE" required :error="errors.country_code" />
          <AppInput v-model="form.visa_type" label="Тип визы" placeholder="tourist" required :error="errors.visa_type" />
        </div>

        <AppSelect v-model="form.priority" label="Приоритет" :options="priorityOptions" />
        <AppInput v-model="form.travel_date" label="Дата поездки" type="date" :error="errors.travel_date" />
        <AppInput v-model="form.critical_date" label="Дедлайн (необязательно, рассчитается автоматически)" type="date" />
        <AppInput v-model="form.notes" label="Заметки" placeholder="Дополнительная информация..." />

        <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

        <div class="flex gap-3 pt-2">
          <AppButton type="submit" :loading="loading">Создать заявку</AppButton>
          <RouterLink :to="{ name: 'cases' }">
            <AppButton type="button" variant="outline">Отмена</AppButton>
          </RouterLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { casesApi } from '@/api/cases';
import { clientsApi } from '@/api/clients';
import AppInput from '@/components/AppInput.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';

const router = useRouter();
const form   = reactive({
  client_id: '', country_code: '', visa_type: '', priority: 'normal',
  travel_date: '', critical_date: '', notes: '',
});
const errors = ref({});
const errorMsg = ref('');
const loading  = ref(false);
const clientSearch  = ref('');
const clientResults = ref([]);
const searched      = ref(false);

const showNoResults = computed(() =>
  searched.value && !clientResults.value.length && clientSearch.value.length >= 2 && !form.client_id
);

const priorityOptions = [
  { value: 'low', label: 'Низкий' },
  { value: 'normal', label: 'Обычный' },
  { value: 'high', label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
];

// Автоматически uppercase + ограничить 2 символами
watch(() => form.country_code, (val) => {
  const u = val.toUpperCase().slice(0, 2);
  if (u !== val) form.country_code = u;
});

let searchDebounce;

function onClientInput() {
  // Если пользователь редактирует поле — сбросить выбранного клиента
  if (form.client_id) {
    form.client_id = '';
    clientResults.value = [];
    searched.value = false;
    return;
  }
  clearTimeout(searchDebounce);
  if (clientSearch.value.length < 2) {
    clientResults.value = [];
    searched.value = false;
    return;
  }
  searchDebounce = setTimeout(async () => {
    const { data } = await clientsApi.list({ q: clientSearch.value });
    clientResults.value = data.data?.data ?? data.data ?? [];
    searched.value = true;
  }, 300);
}

function onClientFocus() {
  if (!form.client_id && clientSearch.value.length >= 2 && !clientResults.value.length) {
    onClientInput();
  }
}

function selectClient(c) {
  form.client_id      = c.id;
  clientSearch.value  = c.name;
  clientResults.value = [];
  searched.value      = false;
  if (errors.value.client_id) delete errors.value.client_id;
}

function clearClient() {
  form.client_id      = '';
  clientSearch.value  = '';
  clientResults.value = [];
  searched.value      = false;
}

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  // Клиентская валидация
  if (!form.client_id) {
    errors.value.client_id = 'Выберите клиента из списка';
    return;
  }
  const cc = form.country_code.trim().toUpperCase();
  if (!cc || cc.length !== 2) {
    errors.value.country_code = 'Введите двухбуквенный код страны (например: DE, FR, US)';
    return;
  }
  form.country_code = cc;

  loading.value = true;

  const payload = { ...form };
  if (!payload.critical_date) delete payload.critical_date;
  if (!payload.travel_date)   delete payload.travel_date;
  if (!payload.notes)         delete payload.notes;

  try {
    const { data } = await casesApi.create(payload);
    router.push({ name: 'cases.show', params: { id: data.data.id } });
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = d?.message || 'Ошибка создания заявки';
    }
  } finally {
    loading.value = false;
  }
}
</script>

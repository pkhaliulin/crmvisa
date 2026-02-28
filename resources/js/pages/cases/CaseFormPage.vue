<template>
  <div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-bold text-gray-900 mb-6">Новая заявка</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Client search -->
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">
            Клиент <span class="text-red-500">*</span>
          </label>
          <input
            v-model="clientSearch"
            @input="searchClients"
            placeholder="Начните вводить имя..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm outline-none focus:border-blue-500"
          />
          <div v-if="clientResults.length" class="mt-1 border rounded-lg bg-white shadow-sm divide-y text-sm">
            <button v-for="c in clientResults" :key="c.id" type="button"
              class="w-full text-left px-3 py-2 hover:bg-blue-50"
              @click="selectClient(c)"
            >
              {{ c.name }} <span class="text-gray-400">{{ c.phone }}</span>
            </button>
          </div>
          <p v-if="form.client_id" class="text-xs text-green-600 mt-1">Выбран: {{ selectedClientName }}</p>
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
import { ref, reactive } from 'vue';
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
const clientSearch      = ref('');
const clientResults     = ref([]);
const selectedClientName = ref('');

const priorityOptions = [
  { value: 'low', label: 'Низкий' },
  { value: 'normal', label: 'Обычный' },
  { value: 'high', label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
];

let searchDebounce;
async function searchClients() {
  clearTimeout(searchDebounce);
  if (!clientSearch.value.length) { clientResults.value = []; return; }
  searchDebounce = setTimeout(async () => {
    const { data } = await clientsApi.list({ q: clientSearch.value });
    clientResults.value = data.data?.data ?? data.data ?? [];
  }, 300);
}

function selectClient(c) {
  form.client_id       = c.id;
  selectedClientName.value = c.name;
  clientSearch.value   = '';
  clientResults.value  = [];
}

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';
  loading.value  = true;

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

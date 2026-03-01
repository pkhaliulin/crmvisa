<template>
  <div class="max-w-xl space-y-4">
    <button @click="$router.back()"
        class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-700 transition-colors">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
      </svg>
      Назад
    </button>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-bold text-gray-900 mb-6">Новая заявка</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">

        <!-- Клиент -->
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
                'w-full border rounded-lg px-3 py-2 text-sm outline-none pr-8 transition-colors',
                form.client_id
                  ? 'border-green-500 bg-green-50 text-green-800 font-medium'
                  : errors.client_id ? 'border-red-400' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
              ]"
            />
            <button v-if="form.client_id" type="button" @click="clearClient"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">
              ×
            </button>
          </div>
          <!-- Дропдаун клиентов -->
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
          <div v-if="showNoClientResults"
            class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg px-3 py-2.5 text-sm text-gray-400">
            Клиент не найден.
            <RouterLink :to="{ name: 'clients.create' }" class="text-blue-600 hover:underline">Создать нового</RouterLink>
          </div>
          <p v-if="errors.client_id" class="text-xs text-red-600 mt-1">{{ errors.client_id }}</p>
        </div>

        <!-- Страна + Тип визы -->
        <div class="grid grid-cols-2 gap-4">

          <!-- Страна с автодроп-дауном -->
          <div class="relative">
            <label class="text-sm font-medium text-gray-700 block mb-1">
              Страна <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input
                v-model="countrySearch"
                @input="onCountryInput"
                @focus="onCountryFocus"
                @blur="onCountryBlur"
                placeholder="Испания, DE..."
                :class="[
                  'w-full border rounded-lg px-3 py-2 text-sm outline-none pr-7 transition-colors',
                  form.country_code
                    ? 'border-green-500 bg-green-50 text-green-800 font-medium'
                    : errors.country_code ? 'border-red-400' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500'
                ]"
              />
              <button v-if="form.country_code" type="button" @click="clearCountry"
                class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 text-lg leading-none">
                ×
              </button>
            </div>
            <!-- Дропдаун стран -->
            <div v-if="countryDropdownVisible && filteredCountries.length"
              class="absolute z-20 w-full mt-1 border border-gray-200 rounded-lg bg-white shadow-lg text-sm max-h-48 overflow-y-auto">
              <button v-for="c in filteredCountries" :key="c.country_code" type="button"
                class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center gap-2"
                @mousedown.prevent="selectCountry(c)">
                <span class="text-base">{{ c.flag_emoji }}</span>
                <span class="text-gray-900">{{ c.name }}</span>
                <span class="ml-auto font-mono text-xs text-gray-400">{{ c.country_code }}</span>
              </button>
            </div>
            <p v-if="errors.country_code" class="text-xs text-red-600 mt-1">{{ errors.country_code }}</p>
          </div>

          <!-- Тип визы -->
          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">
              Тип визы <span class="text-red-500">*</span>
            </label>
            <select v-model="form.visa_type"
              :disabled="!form.country_code"
              :class="[
                'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors',
                errors.visa_type ? 'border-red-400' : 'border-gray-300 focus:border-blue-500 focus:ring-1 focus:ring-blue-500',
                !form.country_code ? 'bg-gray-50 text-gray-400' : '',
              ]">
              <option value="">{{ form.country_code ? '— выберите —' : '— сначала страну —' }}</option>
              <option v-for="slug in selectedCountryVisaTypes" :key="slug" :value="slug">
                {{ visaTypeName(slug) }}
              </option>
            </select>
            <p v-if="errors.visa_type" class="text-xs text-red-600">{{ errors.visa_type }}</p>
          </div>
        </div>

        <AppSelect v-model="form.priority" label="Приоритет" :options="priorityOptions" />
        <AppInput v-model="form.travel_date" label="Дата поездки" type="date" :error="errors.travel_date" />
        <AppInput v-model="form.critical_date" label="Дедлайн (необязательно, рассчитается автоматически)" type="date" />

        <AppTextarea
          v-model="form.notes"
          label="Заметки"
          placeholder="Дополнительная информация о заявке, особые пожелания клиента..."
          :maxlength="1000"
          :rows="3"
        />

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
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { casesApi } from '@/api/cases';
import { clientsApi } from '@/api/clients';
import { countriesApi } from '@/api/countries';
import AppInput from '@/components/AppInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';

const allVisaTypes = ref([]);
function visaTypeName(slug) {
  return allVisaTypes.value.find(t => t.slug === slug)?.name_ru ?? slug;
}

const router = useRouter();
const form   = reactive({
  client_id: '', country_code: '', visa_type: '', priority: 'normal',
  travel_date: '', critical_date: '', notes: '',
});
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

// ── Клиент ──────────────────────────────────────────────────────────────────
const clientSearch   = ref('');
const clientResults  = ref([]);
const clientSearched = ref(false);

const showNoClientResults = computed(() =>
  clientSearched.value && !clientResults.value.length && clientSearch.value.length >= 2 && !form.client_id
);

let clientDebounce;
function onClientInput() {
  if (form.client_id) {
    form.client_id = '';
    clientResults.value = [];
    clientSearched.value = false;
    return;
  }
  clearTimeout(clientDebounce);
  if (clientSearch.value.length < 2) {
    clientResults.value = [];
    clientSearched.value = false;
    return;
  }
  clientDebounce = setTimeout(async () => {
    const { data } = await clientsApi.list({ q: clientSearch.value });
    clientResults.value = data.data?.data ?? data.data ?? [];
    clientSearched.value = true;
  }, 300);
}

function onClientFocus() {
  if (!form.client_id && clientSearch.value.length >= 2 && !clientResults.value.length) {
    onClientInput();
  }
}

function selectClient(c) {
  form.client_id       = c.id;
  clientSearch.value   = `${c.name} — ${c.phone}`;
  clientResults.value  = [];
  clientSearched.value = false;
  if (errors.value.client_id) delete errors.value.client_id;
}

function clearClient() {
  form.client_id       = '';
  clientSearch.value   = '';
  clientResults.value  = [];
  clientSearched.value = false;
}

// ── Страна ──────────────────────────────────────────────────────────────────
const allCountries = ref([]);
const countrySearch = ref('');
const countryDropdownVisible = ref(false);

const selectedCountryVisaTypes = computed(() => {
  if (!form.country_code) return [];
  const c = allCountries.value.find(c => c.country_code === form.country_code);
  return c?.visa_types ?? ['tourist', 'student', 'business'];
});

onMounted(async () => {
  try {
    const [cRes, vtRes] = await Promise.all([
      countriesApi.list(),
      countriesApi.visaTypes(),
    ]);
    allCountries.value = cRes.data.data ?? [];
    allVisaTypes.value = vtRes.data.data ?? [];
  } catch {
    // fallback
  }
});

const filteredCountries = computed(() => {
  const q = countrySearch.value.trim().toLowerCase();
  if (!q) return allCountries.value;
  return allCountries.value.filter(c =>
    c.name.toLowerCase().includes(q) ||
    c.country_code.toLowerCase().startsWith(q)
  );
});

function onCountryInput() {
  if (form.country_code) {
    form.country_code = '';
    if (errors.value.country_code) delete errors.value.country_code;
  }
  countryDropdownVisible.value = true;
}

function onCountryFocus() {
  countryDropdownVisible.value = true;
}

function onCountryBlur() {
  setTimeout(() => { countryDropdownVisible.value = false; }, 150);
}

function selectCountry(c) {
  form.country_code    = c.country_code;
  form.visa_type       = '';
  countrySearch.value  = c.name;
  countryDropdownVisible.value = false;
  if (errors.value.country_code) delete errors.value.country_code;
}

function clearCountry() {
  form.country_code    = '';
  form.visa_type       = '';
  countrySearch.value  = '';
  countryDropdownVisible.value = false;
}

// ── Форма ────────────────────────────────────────────────────────────────────
const priorityOptions = [
  { value: 'low',    label: 'Низкий' },
  { value: 'normal', label: 'Обычный' },
  { value: 'high',   label: 'Высокий' },
  { value: 'urgent', label: 'Срочный' },
];

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  if (!form.client_id) {
    errors.value.client_id = 'Выберите клиента из списка';
    return;
  }
  if (!form.country_code) {
    errors.value.country_code = 'Выберите страну из списка';
    return;
  }
  if (!form.visa_type) {
    errors.value.visa_type = 'Выберите тип визы';
    return;
  }

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

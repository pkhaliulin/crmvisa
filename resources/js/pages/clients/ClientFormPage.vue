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
      <h2 class="text-lg font-bold text-gray-900 mb-6">{{ isEdit ? 'Редактировать клиента' : 'Новый клиент' }}</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">

        <AppInput
          v-model="form.name"
          label="ФИО"
          placeholder="Ислом Каримов"
          required
          :error="errors.name"
          :maxlength="120"
        />

        <div class="grid grid-cols-2 gap-4">
          <AppPhoneInput
            v-model="form.phone"
            label="Телефон"
            :error="errors.phone"
            @blur="validatePhone"
          />
          <AppInput
            v-model="form.email"
            label="Email"
            type="email"
            placeholder="client@mail.com"
            :error="errors.email"
            @blur="validateEmail"
          />
        </div>

        <AppInput
          v-model="form.telegram_chat_id"
          label="Telegram Chat ID"
          placeholder="123456789"
        />

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Гражданство</label>
            <div class="relative">
              <select v-model="form.nationality"
                :class="[
                  'w-full border rounded-lg px-3 py-2 text-sm outline-none transition-colors',
                  errors.nationality ? 'border-red-300 focus:border-red-500' : 'border-gray-300 focus:border-blue-500',
                ]">
                <option value="">— не указано —</option>
                <option value="UZB">Узбекистан (UZB)</option>
                <option value="KAZ">Казахстан (KAZ)</option>
                <option value="KGZ">Кыргызстан (KGZ)</option>
                <option value="TJK">Таджикистан (TJK)</option>
                <option value="TKM">Туркменистан (TKM)</option>
                <option value="RUS">Россия (RUS)</option>
                <option value="UKR">Украина (UKR)</option>
                <option value="AZE">Азербайджан (AZE)</option>
                <option value="GEO">Грузия (GEO)</option>
                <option value="ARM">Армения (ARM)</option>
              </select>
            </div>
            <p v-if="errors.nationality" class="text-xs text-red-600 mt-1">{{ errors.nationality }}</p>
          </div>
          <AppInput
            v-model="form.date_of_birth"
            label="Дата рождения"
            type="date"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <AppInput
            v-model="form.passport_number"
            label="Номер паспорта"
            placeholder="AA1234567"
            :maxlength="20"
          />
          <AppInput
            v-model="form.passport_expires_at"
            label="Срок действия паспорта"
            type="date"
          />
        </div>

        <AppSelect v-model="form.source" label="Источник" :options="sourceOptions" />

        <AppTextarea
          v-model="form.notes"
          label="Заметки"
          placeholder="Любая дополнительная информация о клиенте..."
          :maxlength="500"
          :rows="3"
        />

        <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

        <div class="flex gap-3 pt-2">
          <AppButton type="submit" :loading="loading">
            {{ isEdit ? 'Сохранить' : 'Создать клиента' }}
          </AppButton>
          <RouterLink :to="{ name: 'clients' }">
            <AppButton type="button" variant="outline">Отмена</AppButton>
          </RouterLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { clientsApi } from '@/api/clients';
import AppInput from '@/components/AppInput.vue';
import AppPhoneInput from '@/components/AppPhoneInput.vue';
import AppTextarea from '@/components/AppTextarea.vue';
import AppSelect from '@/components/AppSelect.vue';
import AppButton from '@/components/AppButton.vue';

const router = useRouter();
const route  = useRoute();
const isEdit = computed(() => !!route.params.id);

const form = reactive({
  name: '', phone: '', email: '', telegram_chat_id: '',
  nationality: '', date_of_birth: '', passport_number: '',
  passport_expires_at: '', source: 'direct', notes: '',
});
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

const sourceOptions = [
  { value: 'direct',      label: 'Прямой (агентство)' },
  { value: 'referral',    label: 'Реферал' },
  { value: 'marketplace', label: 'Маркетплейс CRMBOR' },
  { value: 'other',       label: 'Другое' },
];

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

function isValidPhone(phone) {
  const digits = phone.replace(/\D/g, '');
  return digits.startsWith('998') && digits.length === 12;
}

function validateEmail() {
  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = 'Введите корректный email (например: name@domain.com)';
  } else {
    delete errors.value.email;
  }
}

function validatePhone() {
  if (form.phone && !isValidPhone(form.phone)) {
    errors.value.phone = 'Введите полный номер: XX XXX XX XX';
  } else {
    delete errors.value.phone;
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await clientsApi.get(route.params.id);
    Object.assign(form, data.data);
  }
});

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  if (form.email && !isValidEmail(form.email)) {
    errors.value.email = 'Введите корректный email';
    return;
  }
  if (form.phone && !isValidPhone(form.phone)) {
    errors.value.phone = 'Введите полный номер: XX XXX XX XX';
    return;
  }

  loading.value = true;
  try {
    if (isEdit.value) {
      await clientsApi.update(route.params.id, form);
      router.push({ name: 'clients.show', params: { id: route.params.id } });
    } else {
      const { data } = await clientsApi.create(form);
      router.push({ name: 'clients.show', params: { id: data.data.id } });
    }
  } catch (err) {
    const d = err.response?.data;
    if (d?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(d.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = d?.message || 'Ошибка сохранения';
    }
  } finally {
    loading.value = false;
  }
}
</script>

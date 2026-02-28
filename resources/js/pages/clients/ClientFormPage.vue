<template>
  <div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
      <h2 class="text-lg font-bold text-gray-900 mb-6">{{ isEdit ? 'Редактировать клиента' : 'Новый клиент' }}</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <AppInput v-model="form.name" label="ФИО" placeholder="Ислом Каримов" required :error="errors.name" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.phone" label="Телефон" placeholder="+998901234567" :error="errors.phone" />
          <AppInput v-model="form.email" label="Email" type="email" placeholder="client@mail.com" :error="errors.email" />
        </div>
        <AppInput v-model="form.telegram_chat_id" label="Telegram Chat ID" placeholder="123456789" />
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.nationality" label="Гражданство (ISO3)" placeholder="UZB" :error="errors.nationality" />
          <AppInput v-model="form.date_of_birth" label="Дата рождения" type="date" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <AppInput v-model="form.passport_number" label="Номер паспорта" placeholder="AA1234567" />
          <AppInput v-model="form.passport_expires_at" label="Срок действия паспорта" type="date" />
        </div>
        <AppSelect v-model="form.source" label="Источник" :options="sourceOptions" />
        <AppInput v-model="form.notes" label="Заметки" placeholder="Любая дополнительная информация..." />

        <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

        <div class="flex gap-3 pt-2">
          <AppButton type="submit" :loading="loading">{{ isEdit ? 'Сохранить' : 'Создать клиента' }}</AppButton>
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

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await clientsApi.get(route.params.id);
    Object.assign(form, data.data);
  }
});

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';
  loading.value  = true;

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

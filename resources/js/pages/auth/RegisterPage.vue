<template>
  <AuthLayout>
    <h2 class="text-xl font-bold text-gray-900 mb-6">Регистрация агентства</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <AppInput
        v-model="form.agency_name"
        label="Название агентства"
        placeholder="Визовый центр Ташкент"
        required
        :error="errors.agency_name"
        :maxlength="100"
      />
      <AppInput
        v-model="form.name"
        label="Ваше имя (руководитель)"
        placeholder="Ислом Каримов"
        required
        :error="errors.name"
        :maxlength="80"
      />
      <AppInput
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="director@agency.com"
        required
        :error="errors.email"
        @blur="validateEmail"
      />
      <AppPhoneInput
        v-model="form.phone"
        label="Телефон"
        :error="errors.phone"
        @blur="validatePhone"
      />
      <AppInput
        v-model="form.password"
        label="Пароль"
        type="password"
        placeholder="Минимум 8 символов"
        required
        :error="errors.password"
      />

      <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

      <AppButton type="submit" class="w-full" :loading="loading">
        Создать аккаунт — 30 дней бесплатно
      </AppButton>
    </form>

    <p class="text-center text-xs text-gray-400 mt-4">
      Нажимая «Создать аккаунт», вы соглашаетесь с условиями использования
    </p>

    <p class="text-center text-sm text-gray-500 mt-4">
      Уже есть аккаунт?
      <RouterLink :to="{ name: 'login' }" class="text-blue-600 hover:underline font-medium">Войти</RouterLink>
    </p>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AppInput from '@/components/AppInput.vue';
import AppPhoneInput from '@/components/AppPhoneInput.vue';
import AppButton from '@/components/AppButton.vue';

const auth   = useAuthStore();
const router = useRouter();

const form = ref({ agency_name: '', name: '', email: '', phone: '', password: '' });
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

function isValidPhone(phone) {
  const digits = phone.replace(/\D/g, '');
  return digits.startsWith('998') && digits.length === 12;
}

function validateEmail() {
  if (form.value.email && !isValidEmail(form.value.email)) {
    errors.value.email = 'Введите корректный email (например: name@domain.com)';
  } else {
    delete errors.value.email;
  }
}

function validatePhone() {
  if (form.value.phone && !isValidPhone(form.value.phone)) {
    errors.value.phone = 'Введите полный номер: XX XXX XX XX';
  } else {
    delete errors.value.phone;
  }
}

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  if (!isValidEmail(form.value.email)) {
    errors.value.email = 'Введите корректный email';
    return;
  }
  if (form.value.phone && !isValidPhone(form.value.phone)) {
    errors.value.phone = 'Введите полный номер: XX XXX XX XX';
    return;
  }

  loading.value = true;
  try {
    await auth.register(form.value);
    router.push({ name: 'dashboard' });
  } catch (err) {
    const data = err.response?.data;
    if (data?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = data?.message || 'Ошибка регистрации';
    }
  } finally {
    loading.value = false;
  }
}
</script>

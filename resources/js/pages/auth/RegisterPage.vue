<template>
  <AuthLayout>
    <h2 class="text-xl font-bold text-gray-900 mb-6">Регистрация агентства</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <AppInput
        v-model="form.agency_name"
        label="Название агентства"
        placeholder="Silk Road Visa Center"
        required
        :error="errors.agency_name"
        :maxlength="100"
        hint="Только латиница. Это название будет отображаться в системе и маркетплейсе"
        @input="onLatinInput('agency_name')"
      />
      <AppInput
        v-model="form.owner_name"
        label="Ваше имя (руководитель)"
        placeholder="Islom Karimov"
        required
        :error="errors.owner_name"
        :maxlength="80"
        hint="Только латиница, каждое слово с заглавной буквы"
        @blur="form.owner_name = titleCase(form.owner_name)"
        @input="onLatinInput('owner_name')"
      />
      <AppInput
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="director@agency.com"
        required
        :error="errors.email"
        hint="На этот адрес придёт письмо для подтверждения и данные для входа"
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
        hint="Используйте буквы, цифры и спецсимволы для надёжного пароля"
      />
      <AppInput
        v-model="form.password_confirmation"
        label="Подтверждение пароля"
        type="password"
        placeholder="Повторите пароль"
        required
        :error="errors.password_confirmation"
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
import { titleCase } from '@/utils/format';

const auth   = useAuthStore();
const router = useRouter();

const form = ref({ agency_name: '', owner_name: '', email: '', phone: '', password: '', password_confirmation: '' });
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

const latinRegex = /^[A-Za-z0-9\s\-'&.,()]+$/;

function onLatinInput(field) {
  const val = form.value[field];
  if (val && !latinRegex.test(val)) {
    errors.value[field] = 'Только латинские буквы';
    form.value[field] = val.replace(/[^A-Za-z0-9\s\-'&.,()]/g, '');
  } else {
    delete errors.value[field];
  }
}

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

  if (!form.value.agency_name.trim()) {
    errors.value.agency_name = 'Обязательное поле';
    return;
  }
  if (!latinRegex.test(form.value.agency_name)) {
    errors.value.agency_name = 'Только латинские буквы';
    return;
  }
  if (!form.value.owner_name.trim()) {
    errors.value.owner_name = 'Обязательное поле';
    return;
  }
  if (!latinRegex.test(form.value.owner_name)) {
    errors.value.owner_name = 'Только латинские буквы';
    return;
  }
  if (!isValidEmail(form.value.email)) {
    errors.value.email = 'Введите корректный email';
    return;
  }
  if (form.value.phone && !isValidPhone(form.value.phone)) {
    errors.value.phone = 'Введите полный номер: XX XXX XX XX';
    return;
  }
  if (!form.value.password || form.value.password.length < 8) {
    errors.value.password = 'Минимум 8 символов';
    return;
  }
  if (form.value.password !== form.value.password_confirmation) {
    errors.value.password_confirmation = 'Пароли не совпадают';
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

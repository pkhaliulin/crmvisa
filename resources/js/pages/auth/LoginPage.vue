<template>
  <AuthLayout>
    <h2 class="text-xl font-bold text-gray-900 mb-6">Вход в систему</h2>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <AppInput
        v-model="form.email"
        label="Email"
        type="email"
        placeholder="you@agency.com"
        required
        :error="errors.email"
        @blur="validateEmail"
      />
      <div class="relative">
        <AppInput
          v-model="form.password"
          label="Пароль"
          :type="showPassword ? 'text' : 'password'"
          placeholder="••••••••"
          required
          :error="errors.password"
        />
        <button
          type="button"
          class="absolute right-3 top-[38px] text-gray-400 hover:text-gray-600"
          @click="showPassword = !showPassword"
        >
          <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l18 18" />
          </svg>
        </button>
      </div>

      <p v-if="errorMsg" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errorMsg }}</p>

      <AppButton type="submit" class="w-full" :loading="loading">
        Войти
      </AppButton>
    </form>

    <p class="text-center text-sm text-gray-500 mt-6">
      Нет аккаунта?
      <RouterLink :to="{ name: 'register' }" class="text-blue-600 hover:underline font-medium">
        Зарегистрировать агентство
      </RouterLink>
    </p>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute, RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AuthLayout from '@/layouts/AuthLayout.vue';
import AppInput from '@/components/AppInput.vue';
import AppButton from '@/components/AppButton.vue';

const auth    = useAuthStore();
const router  = useRouter();
const route   = useRoute();

const form     = ref({ email: '', password: '' });
const errors   = ref({});
const errorMsg = ref('');
const loading      = ref(false);
const showPassword = ref(false);

function isValidEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email.trim());
}

function validateEmail() {
  if (form.value.email && !isValidEmail(form.value.email)) {
    errors.value.email = 'Введите корректный email (например: name@domain.com)';
  } else {
    delete errors.value.email;
  }
}

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';

  if (!isValidEmail(form.value.email)) {
    errors.value.email = 'Введите корректный email';
    return;
  }

  loading.value = true;
  try {
    await auth.login(form.value);
    if (route.query.redirect) {
      router.push(route.query.redirect);
    } else {
      router.replace(auth.user?.role === 'superadmin'
        ? { name: 'owner.dashboard' }
        : { name: 'dashboard' });
    }
  } catch (err) {
    const data = err.response?.data;
    if (data?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(data.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
      );
    } else {
      errorMsg.value = data?.message || 'Неверный email или пароль';
    }
  } finally {
    loading.value = false;
  }
}
</script>

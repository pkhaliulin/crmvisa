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
      />
      <AppInput
        v-model="form.password"
        label="Пароль"
        type="password"
        placeholder="••••••••"
        required
        :error="errors.password"
      />

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

const form = ref({ email: '', password: '' });
const errors   = ref({});
const errorMsg = ref('');
const loading  = ref(false);

async function handleSubmit() {
  errors.value   = {};
  errorMsg.value = '';
  loading.value  = true;

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
      errorMsg.value = data?.message || 'Ошибка входа';
    }
  } finally {
    loading.value = false;
  }
}
</script>

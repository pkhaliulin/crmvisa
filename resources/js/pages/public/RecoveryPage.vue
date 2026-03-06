<template>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-[#0A1F44]">VisaBor</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $t('recovery.title') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <!-- Шаг 1: Ввод номера телефона -->
                <div v-if="!sent">
                    <p class="text-sm text-gray-600 mb-4">{{ $t('recovery.enterPhone') }}</p>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('recovery.phone') }}</label>
                        <div class="flex items-center border rounded-xl overflow-hidden">
                            <span class="px-3 py-2.5 bg-gray-50 text-sm text-gray-500 border-r">+998</span>
                            <input v-model="phoneDigits" type="text" inputmode="numeric" maxlength="12"
                                placeholder="90 123 45 67"
                                class="flex-1 px-3 py-2.5 text-sm outline-none"
                                @keyup.enter="submit" />
                        </div>
                    </div>
                    <button @click="submit" :disabled="loading || phoneDigits.replace(/\D/g,'').length < 9"
                        class="w-full py-2.5 rounded-xl text-sm font-semibold transition-colors"
                        :class="phoneDigits.replace(/\D/g,'').length >= 9
                            ? 'bg-[#1BA97F] text-white hover:bg-[#169B72]'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                        <span v-if="!loading">{{ $t('recovery.sendLink') }}</span>
                        <span v-else>{{ $t('common.loading') }}</span>
                    </button>
                    <p v-if="error" class="text-xs text-red-500 mt-2">{{ error }}</p>
                </div>

                <!-- Шаг 2: Ссылка отправлена -->
                <div v-else class="text-center">
                    <div class="w-16 h-16 bg-[#1BA97F]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#1BA97F]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="font-bold text-[#0A1F44] mb-2">{{ $t('recovery.linkSent') }}</h3>
                    <p class="text-sm text-gray-500">{{ $t('recovery.checkEmail') }}</p>
                </div>
            </div>

            <div class="text-center mt-4">
                <router-link to="/" class="text-xs text-gray-400 hover:text-gray-600">{{ $t('recovery.backToHome') }}</router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { publicPortalApi } from '@/api/public';

const phoneDigits = ref('');
const loading = ref(false);
const sent = ref(false);
const error = ref('');

async function submit() {
    const digits = phoneDigits.value.replace(/\D/g, '');
    if (digits.length < 9) return;
    loading.value = true;
    error.value = '';
    try {
        const phone = '+998' + digits;
        await publicPortalApi.recoveryRequest(phone);
        sent.value = true;
    } catch (e) {
        // Всегда показываем успех (не раскрываем существование аккаунта)
        sent.value = true;
    } finally {
        loading.value = false;
    }
}
</script>

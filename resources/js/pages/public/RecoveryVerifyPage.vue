<template>
    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-[#0A1F44]">VisaBor</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $t('recovery.title') }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <!-- Загрузка / проверка токена -->
                <div v-if="checking" class="text-center py-8">
                    <p class="text-sm text-gray-500">{{ $t('common.loading') }}</p>
                </div>

                <!-- Токен невалидный -->
                <div v-else-if="tokenError" class="text-center py-4">
                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <h3 class="font-bold text-[#0A1F44] mb-2">{{ $t('recovery.linkExpired') }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ $t('recovery.linkExpiredHint') }}</p>
                    <router-link to="/recovery" class="text-sm text-[#1BA97F] hover:text-[#169B72] font-medium">{{ $t('recovery.tryAgain') }}</router-link>
                </div>

                <!-- Успех — доступ восстановлен -->
                <div v-else-if="done" class="text-center py-4">
                    <div class="w-16 h-16 bg-[#1BA97F]/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-[#1BA97F]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="font-bold text-[#0A1F44] mb-2">{{ $t('recovery.success') }}</h3>
                    <p class="text-sm text-gray-500 mb-4">{{ $t('recovery.successHint') }}</p>
                    <router-link to="/me" class="inline-block w-full py-2.5 bg-[#1BA97F] text-white rounded-xl text-sm font-semibold text-center hover:bg-[#169B72]">{{ $t('recovery.goToProfile') }}</router-link>
                </div>

                <!-- Шаг 1: Ввод нового номера -->
                <div v-else-if="step === 'phone'">
                    <p class="text-sm text-gray-600 mb-1">{{ $t('recovery.hi', { name: userName }) }}</p>
                    <p class="text-sm text-gray-500 mb-4">{{ $t('recovery.enterNewPhone') }}</p>
                    <div class="mb-4">
                        <div class="flex items-center border rounded-xl overflow-hidden">
                            <span class="px-3 py-2.5 bg-gray-50 text-sm text-gray-500 border-r">+998</span>
                            <input v-model="phoneDigits" type="text" inputmode="numeric" maxlength="12"
                                placeholder="90 123 45 67"
                                class="flex-1 px-3 py-2.5 text-sm outline-none"
                                @keyup.enter="sendOtp" />
                        </div>
                    </div>
                    <button @click="sendOtp" :disabled="loading || phoneDigits.replace(/\D/g,'').length < 9"
                        class="w-full py-2.5 rounded-xl text-sm font-semibold transition-colors"
                        :class="phoneDigits.replace(/\D/g,'').length >= 9
                            ? 'bg-[#1BA97F] text-white hover:bg-[#169B72]'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                        <span v-if="!loading">{{ $t('recovery.sendSms') }}</span>
                        <span v-else>{{ $t('common.loading') }}</span>
                    </button>
                    <p v-if="error" class="text-xs text-red-500 mt-2">{{ error }}</p>
                </div>

                <!-- Шаг 2: Ввод OTP кода -->
                <div v-else-if="step === 'otp'">
                    <p class="text-sm text-gray-600 mb-4">{{ $t('recovery.enterOtp') }}</p>
                    <div class="flex justify-center gap-2 mb-4">
                        <input v-for="i in 4" :key="i" :ref="el => { if (el) otpInputs[i-1] = el }"
                            type="text" inputmode="numeric" maxlength="1" :autocomplete="i === 1 ? 'one-time-code' : 'off'"
                            class="w-12 h-12 text-center text-xl font-bold border-2 rounded-xl outline-none transition-colors"
                            :class="otpCode[i-1] ? 'border-[#1BA97F]' : 'border-gray-300'"
                            :value="otpCode[i-1] || ''"
                            @input="onOtpInput($event, i-1)"
                            @keydown.backspace="onOtpBack($event, i-1)"
                            @paste="onOtpPaste" />
                    </div>
                    <button @click="confirmRecovery" :disabled="loading || otpCode.join('').length < 4"
                        class="w-full py-2.5 rounded-xl text-sm font-semibold transition-colors"
                        :class="otpCode.join('').length === 4
                            ? 'bg-[#1BA97F] text-white hover:bg-[#169B72]'
                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                        <span v-if="!loading">{{ $t('recovery.confirm') }}</span>
                        <span v-else>{{ $t('common.loading') }}</span>
                    </button>
                    <p v-if="error" class="text-xs text-red-500 mt-2">{{ error }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';

const route = useRoute();
const publicAuth = usePublicAuthStore();

const token = ref('');
const checking = ref(true);
const tokenError = ref(false);
const userName = ref('');
const step = ref('phone'); // phone | otp
const done = ref(false);
const loading = ref(false);
const error = ref('');

const phoneDigits = ref('');
const otpCode = ref(['', '', '', '']);
const otpInputs = ref([]);

onMounted(async () => {
    token.value = route.query.token || '';
    if (!token.value) { tokenError.value = true; checking.value = false; return; }
    try {
        const { data } = await publicPortalApi.recoveryVerifyToken(token.value);
        userName.value = data?.data?.user_name || '';
        checking.value = false;
    } catch {
        tokenError.value = true;
        checking.value = false;
    }
});

async function sendOtp() {
    const digits = phoneDigits.value.replace(/\D/g, '');
    if (digits.length < 9) return;
    loading.value = true;
    error.value = '';
    try {
        const phone = '+998' + digits;
        await publicPortalApi.recoverySendOtp(token.value, phone);
        step.value = 'otp';
        nextTick(() => { if (otpInputs.value[0]) otpInputs.value[0].focus(); });
    } catch (e) {
        error.value = e.response?.data?.message || 'Ошибка';
    } finally {
        loading.value = false;
    }
}

function onOtpInput(e, idx) {
    const val = e.target.value.replace(/\D/g, '');
    if (val.length >= 4) {
        val.slice(0, 4).split('').forEach((d, i) => { otpCode.value[i] = d; });
        e.target.value = otpCode.value[idx];
        nextTick(() => { if (otpInputs.value[3]) otpInputs.value[3].focus(); confirmRecovery(); });
        return;
    }
    otpCode.value[idx] = val.charAt(0) || '';
    e.target.value = otpCode.value[idx];
    if (val && idx < 3 && otpInputs.value[idx + 1]) otpInputs.value[idx + 1].focus();
    if (otpCode.value.join('').length === 4) confirmRecovery();
}

function onOtpBack(e, idx) {
    if (!otpCode.value[idx] && idx > 0) otpInputs.value[idx - 1].focus();
}

function onOtpPaste(e) {
    const text = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 4);
    for (let i = 0; i < 4; i++) otpCode.value[i] = text[i] || '';
    e.preventDefault();
    if (text.length === 4) confirmRecovery();
}

async function confirmRecovery() {
    const code = otpCode.value.join('');
    if (code.length < 4) return;
    loading.value = true;
    error.value = '';
    try {
        const phone = '+998' + phoneDigits.value.replace(/\D/g, '');
        const { data } = await publicPortalApi.recoveryConfirm(token.value, phone, code);
        const result = data?.data;
        if (result?.token) {
            localStorage.setItem('public_token', result.token);
            if (result.user) {
                localStorage.setItem('public_user', JSON.stringify(result.user));
                publicAuth.user = result.user;
                publicAuth.isAuthenticated = true;
            }
        }
        done.value = true;
    } catch (e) {
        error.value = e.response?.data?.message || 'Ошибка';
        otpCode.value = ['', '', '', ''];
        nextTick(() => { if (otpInputs.value[0]) otpInputs.value[0].focus(); });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <!-- Оверлей: на мобильном снизу, на десктопе по центру -->
    <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
         @click.self="$emit('close')">

        <!-- Карточка: на мобильном — bottom sheet, на десктопе — обычный попап -->
        <div class="w-full sm:max-w-sm bg-white sm:rounded-3xl rounded-t-3xl shadow-2xl overflow-hidden
                    max-h-[92vh] overflow-y-auto">
            <!-- Шапка -->
            <div class="px-6 sm:px-8 pt-5 sm:pt-8 pb-4">
                <!-- Ручка на мобильном -->
                <div class="w-12 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>

                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
                            <path d="M2 8L10 20L14 14L18 20L26 8" stroke="#1BA97F" stroke-width="3"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="font-bold text-[#0A1F44]">VisaBor</span>
                    </div>
                    <button @click="$emit('close')"
                        class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100
                               text-gray-400 active:bg-gray-200 transition-colors">
                        ✕
                    </button>
                </div>

                <!-- Заголовок по шагу -->
                <h2 class="text-2xl font-bold text-[#0A1F44] mb-1">
                    <template v-if="step === 'phone'">Войти</template>
                    <template v-if="step === 'otp'">Введите код</template>
                    <template v-if="step === 'pin'">Ваш PIN-код</template>
                    <template v-if="step === 'login-pin'">Войти по PIN</template>
                </h2>
                <p class="text-gray-400 text-sm">
                    <template v-if="step === 'phone'">Введите номер телефона</template>
                    <template v-if="step === 'otp'">Отправили SMS на {{ phone }}</template>
                    <template v-if="step === 'pin'">Запомните его — это ваш ключ</template>
                    <template v-if="step === 'login-pin'">Введите PIN для входа</template>
                </p>
            </div>

            <!-- Форма -->
            <div class="px-6 sm:px-8 pb-8">
                <!-- ШАГ 1: Телефон -->
                <template v-if="step === 'phone'">
                    <div class="mt-4">
                        <div class="flex items-center border border-gray-200 rounded-xl focus-within:border-[#1BA97F] transition-colors">
                            <span class="pl-4 text-gray-400 select-none text-lg">+</span>
                            <input
                                v-model="phone"
                                type="tel"
                                placeholder="998 90 123 45 67"
                                maxlength="20"
                                class="flex-1 px-3 py-4 text-[#0A1F44] font-medium placeholder-gray-300
                                       outline-none bg-transparent text-base"
                                @keydown.enter="sendOtp"
                                autocomplete="tel"
                            />
                        </div>
                        <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
                    </div>
                    <button @click="sendOtp" :disabled="loading"
                        class="mt-4 w-full py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] active:scale-[0.98] transition-all disabled:opacity-60
                               text-base">
                        {{ loading ? 'Отправляем...' : 'Получить код' }}
                    </button>
                    <p class="mt-4 text-center text-sm text-gray-400">
                        Уже есть PIN?
                        <button @click="step = 'login-pin'" class="text-[#0A1F44] font-medium hover:underline">
                            Войти
                        </button>
                    </p>
                </template>

                <!-- ШАГ 2: OTP -->
                <template v-if="step === 'otp'">
                    <p class="mt-3 text-sm text-gray-500 text-center">
                        Мы отправили вам SMS с ПИН-кодом.<br>
                        Это ваш ПИН-код для входа в личный кабинет.
                    </p>
                    <div class="mt-4 flex gap-3 justify-center">
                        <input
                            v-for="(_, i) in otp"
                            :key="i"
                            :ref="el => otpRefs[i] = el"
                            v-model="otp[i]"
                            type="tel"
                            maxlength="1"
                            inputmode="numeric"
                            class="w-14 h-16 text-center text-2xl font-bold text-[#0A1F44]
                                   border border-gray-200 rounded-xl outline-none
                                   focus:border-[#1BA97F] transition-colors"
                            @input="onOtpInput(i)"
                            @keydown.backspace="onOtpBackspace(i)"
                        />
                    </div>
                    <p v-if="error" class="mt-2 text-sm text-red-500 text-center">{{ error }}</p>
                    <button @click="verifyOtp" :disabled="loading || otpCode.length < 4"
                        class="mt-6 w-full py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] active:scale-[0.98] transition-all disabled:opacity-60
                               text-base">
                        {{ loading ? 'Проверяем...' : 'Подтвердить' }}
                    </button>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <button @click="step = 'phone'" class="text-gray-400 hover:text-gray-600 py-2">
                            Изменить номер
                        </button>
                        <button v-if="canResend" @click="sendOtp" class="text-[#0A1F44] font-medium hover:underline py-2">
                            Отправить снова
                        </button>
                        <span v-else class="text-gray-400 py-2">Снова через {{ resendTimer }}с</span>
                    </div>
                </template>

                <!-- ШАГ 3: Установить PIN -->
                <template v-if="step === 'pin'">
                    <p class="mt-3 text-sm text-gray-500 mb-4">
                        Установите 4-значный PIN для быстрого входа в следующий раз.
                    </p>
                    <input
                        v-model="pin"
                        type="tel"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="• • • •"
                        class="w-full px-4 py-4 border border-gray-200 rounded-xl text-center
                               text-3xl tracking-[0.6em] font-bold text-[#0A1F44] outline-none
                               focus:border-[#1BA97F] transition-colors"
                    />
                    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
                    <button @click="setPin" :disabled="pin.length < 4 || loading"
                        class="mt-4 w-full py-4 bg-[#1BA97F] text-white font-semibold rounded-xl
                               hover:bg-[#17956f] active:scale-[0.98] transition-all disabled:opacity-60
                               text-base">
                        {{ loading ? 'Сохраняем...' : 'Установить PIN и продолжить' }}
                    </button>
                    <button @click="finish" class="mt-2 w-full py-3 text-sm text-gray-400 hover:text-gray-600">
                        Пропустить
                    </button>
                </template>

                <!-- ШАГ: Войти по PIN -->
                <template v-if="step === 'login-pin'">
                    <div class="mt-4 space-y-3">
                        <input
                            v-model="phone"
                            type="tel"
                            inputmode="tel"
                            placeholder="+998901234567"
                            class="w-full px-4 py-4 border border-gray-200 rounded-xl outline-none
                                   focus:border-[#1BA97F] transition-colors text-[#0A1F44] text-base"
                        />
                        <input
                            v-model="pin"
                            type="tel"
                            inputmode="numeric"
                            maxlength="4"
                            placeholder="• • • •"
                            class="w-full px-4 py-4 border border-gray-200 rounded-xl text-center
                                   text-3xl tracking-[0.6em] font-bold text-[#0A1F44] outline-none
                                   focus:border-[#1BA97F] transition-colors"
                        />
                    </div>
                    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
                    <button @click="loginPin" :disabled="loading"
                        class="mt-4 w-full py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] active:scale-[0.98] transition-all disabled:opacity-60
                               text-base">
                        {{ loading ? 'Входим...' : 'Войти' }}
                    </button>
                    <button @click="step = 'phone'" class="mt-2 w-full py-3 text-sm text-gray-400 hover:text-gray-600">
                        Войти через SMS
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';

const props = defineProps({ preselectedCountry: String });
const emit  = defineEmits(['close', 'success']);

const publicAuth = usePublicAuthStore();

const step    = ref('phone');
const phone   = ref('');
const otp     = ref(['', '', '', '']);
const pin     = ref('');
const loading = ref(false);
const error   = ref('');

const otpRefs    = ref([]);
const otpCode    = computed(() => otp.value.join(''));
const canResend  = ref(false);
const resendTimer = ref(60);
let timerInterval = null;

function startResendTimer() {
    canResend.value = false;
    resendTimer.value = 60;
    timerInterval = setInterval(() => {
        resendTimer.value--;
        if (resendTimer.value <= 0) {
            canResend.value = true;
            clearInterval(timerInterval);
        }
    }, 1000);
}

onUnmounted(() => clearInterval(timerInterval));

async function sendOtp() {
    if (!phone.value.trim()) { error.value = 'Введите номер телефона'; return; }
    error.value = '';
    loading.value = true;
    try {
        await publicPortalApi.sendOtp(phone.value);
        step.value = 'otp';
        startResendTimer();
        nextTick(() => otpRefs.value[0]?.focus());
    } catch (e) {
        error.value = e.response?.data?.message || 'Ошибка отправки SMS';
    } finally {
        loading.value = false;
    }
}

function onOtpInput(i) {
    if (otp.value[i] && i < 3) {
        nextTick(() => otpRefs.value[i + 1]?.focus());
    }
    if (otpCode.value.length === 4) verifyOtp();
}

function onOtpBackspace(i) {
    if (!otp.value[i] && i > 0) {
        otp.value[i - 1] = '';
        nextTick(() => otpRefs.value[i - 1]?.focus());
    }
}

async function verifyOtp() {
    if (otpCode.value.length < 4) return;
    error.value = '';
    loading.value = true;
    try {
        const { data } = await publicPortalApi.verifyOtp(phone.value, otpCode.value);
        const { user, token, is_new } = data.data;
        publicAuth.setSession(user, token);
        step.value = is_new ? 'pin' : 'done';
        if (!is_new) finish();
    } catch (e) {
        error.value = e.response?.data?.message || 'Неверный код';
        otp.value = ['', '', '', ''];
        nextTick(() => otpRefs.value[0]?.focus());
    } finally {
        loading.value = false;
    }
}

async function setPin() {
    if (pin.value.length < 4) return;
    loading.value = true;
    try {
        await publicPortalApi.setPin(pin.value);
        finish();
    } catch {
        finish();
    } finally {
        loading.value = false;
    }
}

async function loginPin() {
    error.value = '';
    loading.value = true;
    try {
        const { data } = await publicPortalApi.loginPin(phone.value, pin.value);
        publicAuth.setSession(data.data.user, data.data.token);
        finish();
    } catch (e) {
        error.value = e.response?.data?.message || 'Неверный PIN';
    } finally {
        loading.value = false;
    }
}

function finish() {
    emit('success');
}
</script>

<template>
    <!-- Оверлей -->
    <div class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center sm:p-4 bg-black/40 backdrop-blur-sm"
         @click.self="$emit('close')">

        <!-- Карточка -->
        <div class="w-full sm:max-w-sm bg-white sm:rounded-3xl rounded-t-3xl shadow-2xl overflow-hidden
                    max-h-[92vh] overflow-y-auto">
            <!-- Шапка -->
            <div class="px-6 sm:px-8 pt-5 sm:pt-8 pb-4">
                <div class="w-12 h-1 bg-gray-200 rounded-full mx-auto mb-5 sm:hidden"></div>

                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <LogoBrand size="1.1rem" />
                    </div>
                    <button @click="$emit('close')"
                        class="w-9 h-9 flex items-center justify-center rounded-full hover:bg-gray-100
                               text-gray-400 active:bg-gray-200 transition-colors">
                        ✕
                    </button>
                </div>

                <h2 class="text-2xl font-bold text-[#0A1F44] mb-1">
                    <template v-if="step === 'phone'">{{ $t('auth.loginTitle') }}</template>
                    <template v-if="step === 'otp'">{{ $t('auth.enterCode') }}</template>
                    <template v-if="step === 'pin'">{{ $t('auth.yourPin') }}</template>
                    <template v-if="step === 'login-pin'">{{ $t('auth.loginByPin') }}</template>
                </h2>
                <p class="text-gray-400 text-sm">
                    <template v-if="step === 'phone'">{{ $t('auth.enterPhone') }}</template>
                    <template v-if="step === 'otp'">{{ $t('auth.smsSentTo', { phone }) }}</template>
                    <template v-if="step === 'pin'">{{ $t('auth.rememberPin') }}</template>
                    <template v-if="step === 'login-pin'">{{ $t('auth.enterPinLogin') }}</template>
                </p>
            </div>

            <!-- Форма -->
            <div class="px-6 sm:px-8 pb-8">

                <!-- ШАГ 1: Телефон -->
                <template v-if="step === 'phone'">
                    <div class="mt-4">
                        <!-- Поле с фиксированным +998 -->
                        <div :class="[
                            'flex items-center border rounded-xl transition-colors overflow-hidden',
                            phoneFocused ? 'border-[#1BA97F] ring-2 ring-[#1BA97F]/20' : 'border-gray-200',
                            error ? 'border-red-400' : '',
                        ]">
                            <span class="px-4 py-4 text-[#0A1F44] font-semibold bg-gray-50
                                         border-r border-gray-200 select-none text-base shrink-0">
                                +998
                            </span>
                            <input
                                ref="phoneInputRef"
                                :value="phoneDisplay"
                                @input="onPhoneInput"
                                @keydown="onPhoneKeydown"
                                @focus="phoneFocused = true"
                                @blur="phoneFocused = false"
                                @keydown.enter="sendOtp"
                                type="tel"
                                inputmode="numeric"
                                placeholder="97 123 45 67"
                                maxlength="12"
                                class="flex-1 px-4 py-4 text-[#0A1F44] font-medium
                                       outline-none bg-transparent text-base tracking-wider"
                            />
                        </div>
                        <p class="mt-1.5 text-xs text-gray-400">{{ $t('auth.phoneExample') }}</p>
                        <p v-if="error" class="mt-1 text-sm text-red-500">{{ error }}</p>
                    </div>
                    <button @click="sendOtp" :disabled="loading || phoneDigits.length < 9"
                        class="mt-4 w-full py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] active:scale-[0.98] transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed text-base">
                        {{ loading ? $t('auth.sending') : $t('auth.getCode') }}
                    </button>
                    <p class="mt-4 text-center text-sm text-gray-400">
                        {{ $t('auth.havePin') }}
                        <button @click="step = 'login-pin'" class="text-[#0A1F44] font-medium hover:underline">
                            {{ $t('common.login') }}
                        </button>
                    </p>
                    <p class="mt-2 text-center">
                        <router-link to="/recovery" class="text-xs text-gray-400 hover:text-[#1BA97F]" @click="$emit('close')">
                            {{ $t('auth.lostPhone') }}
                        </router-link>
                    </p>
                </template>

                <!-- ШАГ 2: OTP (4 бокса) -->
                <template v-if="step === 'otp'">
                    <p class="mt-3 text-sm text-gray-500 text-center">
                        {{ $t('auth.smsSentConfirm') }}
                    </p>
                    <!-- Скрытый input для iOS/Android автоподстановки OTP -->
                    <input
                        ref="hiddenOtpRef"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        class="absolute opacity-0 w-0 h-0"
                        tabindex="-1"
                        @input="onHiddenOtpInput"
                    />
                    <div class="mt-5 flex gap-3 justify-center">
                        <input
                            v-for="(_, i) in otp"
                            :key="i"
                            :ref="el => otpRefs[i] = el"
                            v-model="otp[i]"
                            type="tel"
                            maxlength="1"
                            inputmode="numeric"
                            autocomplete="off"
                            :class="[
                                'w-16 h-18 text-center text-2xl font-bold text-[#0A1F44]',
                                'border-2 rounded-xl outline-none transition-colors',
                                otp[i] ? 'border-[#1BA97F] bg-[#1BA97F]/5' : 'border-gray-200 focus:border-[#1BA97F]',
                            ]"
                            style="height: 4.5rem"
                            @input="onOtpInput(i)"
                            @keydown.backspace="onOtpBackspace(i)"
                            @keydown="onOtpKeydown($event)"
                        />
                    </div>
                    <p v-if="error" class="mt-3 text-sm text-red-500 text-center">{{ error }}</p>
                    <button @click="verifyOtp" :disabled="loading || otpCode.length < 4"
                        class="mt-6 w-full py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] active:scale-[0.98] transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed text-base">
                        {{ loading ? $t('auth.checking') : $t('auth.confirm') }}
                    </button>
                    <div class="mt-4 flex items-center justify-between text-sm">
                        <button @click="step = 'phone'" class="text-gray-400 hover:text-gray-600 py-2">
                            {{ $t('auth.changeNumber') }}
                        </button>
                        <button v-if="canResend" @click="sendOtp" class="text-[#0A1F44] font-medium hover:underline py-2">
                            {{ $t('auth.resend') }}
                        </button>
                        <span v-else class="text-gray-400 py-2">{{ $t('auth.resendIn', { seconds: resendTimer }) }}</span>
                    </div>
                </template>

                <!-- ШАГ 3: Установить PIN -->
                <template v-if="step === 'pin'">
                    <p class="mt-3 text-sm text-gray-500 mb-4">
                        {{ $t('auth.setPinDesc') }}
                    </p>
                    <input
                        v-model="pin"
                        type="tel"
                        inputmode="numeric"
                        maxlength="4"
                        placeholder="• • • •"
                        class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl text-center
                               text-3xl tracking-[0.6em] font-bold text-[#0A1F44] outline-none
                               focus:border-[#1BA97F] transition-colors"
                        @input="pin = pin.replace(/\D/g, '').slice(0, 4)"
                    />
                    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
                    <button @click="setPin" :disabled="pin.length < 4 || loading"
                        class="mt-4 w-full py-4 bg-[#1BA97F] text-white font-semibold rounded-xl
                               hover:bg-[#17956f] active:scale-[0.98] transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed text-base">
                        {{ loading ? $t('auth.saving') : $t('auth.setPinBtn') }}
                    </button>
                    <button @click="finish" class="mt-2 w-full py-3 text-sm text-gray-400 hover:text-gray-600">
                        {{ $t('auth.skip') }}
                    </button>
                </template>

                <!-- ШАГ: Войти по PIN -->
                <template v-if="step === 'login-pin'">
                    <div class="mt-4 space-y-3">
                        <!-- Телефон с маской -->
                        <div :class="[
                            'flex items-center border rounded-xl transition-colors overflow-hidden',
                            loginPhoneFocused ? 'border-[#1BA97F] ring-2 ring-[#1BA97F]/20' : 'border-gray-200',
                        ]">
                            <span class="px-4 py-4 text-[#0A1F44] font-semibold bg-gray-50
                                         border-r border-gray-200 select-none text-base shrink-0">
                                +998
                            </span>
                            <input
                                :value="loginPhoneDisplay"
                                @input="onLoginPhoneInput"
                                @keydown="onPhoneKeydown"
                                @focus="loginPhoneFocused = true"
                                @blur="loginPhoneFocused = false"
                                type="tel"
                                inputmode="numeric"
                                placeholder="97 123 45 67"
                                maxlength="12"
                                class="flex-1 px-4 py-4 text-[#0A1F44] font-medium
                                       outline-none bg-transparent text-base tracking-wider"
                            />
                        </div>
                        <!-- PIN -->
                        <input
                            v-model="pin"
                            type="tel"
                            inputmode="numeric"
                            maxlength="4"
                            placeholder="• • • •"
                            class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl text-center
                                   text-3xl tracking-[0.6em] font-bold text-[#0A1F44] outline-none
                                   focus:border-[#1BA97F] transition-colors"
                            @input="pin = pin.replace(/\D/g, '').slice(0, 4)"
                        />
                    </div>
                    <p v-if="error" class="mt-2 text-sm text-red-500">{{ error }}</p>
                    <button @click="loginPin" :disabled="loading || loginPhoneDigits.length < 9 || pin.length < 4"
                        class="mt-4 w-full py-4 bg-[#0A1F44] text-white font-semibold rounded-xl
                               hover:bg-[#0d2a5e] active:scale-[0.98] transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed text-base">
                        {{ loading ? $t('auth.entering') : $t('common.login') }}
                    </button>
                    <button @click="step = 'phone'" class="mt-2 w-full py-3 text-sm text-gray-400 hover:text-gray-600">
                        {{ $t('auth.loginViaSms') }}
                    </button>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';
import LogoBrand from '@/components/LogoBrand.vue';

const { t } = useI18n();

const props = defineProps({ preselectedCountry: String });
const emit  = defineEmits(['close', 'success']);

const publicAuth = usePublicAuthStore();

const step    = ref('phone');
const loading = ref(false);
const error   = ref('');

// ── Телефон (шаг 1) ───────────────────────────────────────────────────────────
const phoneDigits    = ref('');   // 9 цифр после +998
const phoneFocused   = ref(false);
const phoneInputRef  = ref(null);

// Форматируем 9 цифр → "XX XXX XX XX"
function formatDigits(digits) {
    const d = digits.slice(0, 9);
    let r = '';
    if (d.length > 0) r += d.slice(0, 2);
    if (d.length > 2) r += ' ' + d.slice(2, 5);
    if (d.length > 5) r += ' ' + d.slice(5, 7);
    if (d.length > 7) r += ' ' + d.slice(7, 9);
    return r;
}

const phoneDisplay = computed(() => formatDigits(phoneDigits.value));

// Полный номер для API: +998XXXXXXXXX
const phone = computed(() => phoneDigits.value.length === 9 ? `+998${phoneDigits.value}` : `+998${phoneDigits.value}`);

function onPhoneInput(e) {
    const raw = e.target.value.replace(/\D/g, '').slice(0, 9);
    phoneDigits.value = raw;
    // Восстанавливаем форматированное значение
    const formatted = formatDigits(raw);
    e.target.value = formatted;
    error.value = '';
}

function onPhoneKeydown(e) {
    const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Enter'];
    if (allowed.includes(e.key)) return;
    if (e.key >= '0' && e.key <= '9') return;
    e.preventDefault();
}

// ── Телефон (login-pin шаг) ───────────────────────────────────────────────────
const loginPhoneDigits  = ref('');
const loginPhoneFocused = ref(false);

const loginPhoneDisplay = computed(() => formatDigits(loginPhoneDigits.value));
const loginPhone = computed(() => `+998${loginPhoneDigits.value}`);

function onLoginPhoneInput(e) {
    const raw = e.target.value.replace(/\D/g, '').slice(0, 9);
    loginPhoneDigits.value = raw;
    e.target.value = formatDigits(raw);
    error.value = '';
}

// ── OTP (4 бокса) ─────────────────────────────────────────────────────────────
const otp          = ref(['', '', '', '']);
const otpRefs      = ref([]);
const hiddenOtpRef = ref(null);
const otpCode      = computed(() => otp.value.join(''));

function onHiddenOtpInput(e) {
    const digits = (e.target.value || '').replace(/\D/g, '').slice(0, 4);
    if (digits.length >= 4) {
        digits.split('').forEach((d, i) => { otp.value[i] = d; });
        e.target.value = '';
        nextTick(() => { otpRefs.value[3]?.focus(); verifyOtp(); });
    }
}

function onOtpInput(i) {
    const raw = otp.value[i].replace(/\D/g, '');
    // Автоподстановка iOS: все 4 цифры вставлены в одно поле
    if (raw.length >= 4) {
        const digits = raw.slice(0, 4).split('');
        digits.forEach((d, idx) => { otp.value[idx] = d; });
        nextTick(() => { otpRefs.value[3]?.focus(); verifyOtp(); });
        return;
    }
    otp.value[i] = raw.slice(-1);
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

function onOtpKeydown(e) {
    const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight'];
    if (allowed.includes(e.key)) return;
    if (e.key >= '0' && e.key <= '9') return;
    e.preventDefault();
}

// ── PIN ───────────────────────────────────────────────────────────────────────
const pin = ref('');

// ── Таймер повторной отправки ─────────────────────────────────────────────────
const canResend   = ref(false);
const resendTimer = ref(60);
let timerInterval = null;

function startResendTimer() {
    canResend.value  = false;
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

// ── API-вызовы ────────────────────────────────────────────────────────────────
async function sendOtp() {
    if (phoneDigits.value.length < 9) {
        error.value = t('auth.fullNumber');
        return;
    }
    error.value  = '';
    loading.value = true;
    try {
        await publicPortalApi.sendOtp(phone.value);
        step.value = 'otp';
        otp.value  = ['', '', '', ''];
        startResendTimer();
        nextTick(() => {
            // Фокус на скрытый input для iOS автоподстановки, затем на первый бокс
            hiddenOtpRef.value?.focus();
            setTimeout(() => otpRefs.value[0]?.focus(), 300);
        });
    } catch (e) {
        error.value = e.response?.data?.message || t('auth.smsError');
    } finally {
        loading.value = false;
    }
}

async function verifyOtp() {
    if (otpCode.value.length < 4) return;
    error.value  = '';
    loading.value = true;
    try {
        const { data } = await publicPortalApi.verifyOtp(phone.value, otpCode.value);
        const { user, token, is_new } = data.data;
        publicAuth.setSession(user, token);
        if (is_new) {
            step.value = 'pin';
        } else {
            finish();
        }
    } catch (e) {
        error.value = e.response?.data?.message || t('auth.wrongCode');
        otp.value   = ['', '', '', ''];
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
    } catch {
        // даже при ошибке продолжаем
    } finally {
        loading.value = false;
        finish();
    }
}

async function loginPin() {
    if (loginPhoneDigits.value.length < 9) {
        error.value = t('auth.enterFullPhone');
        return;
    }
    if (pin.value.length < 4) {
        error.value = t('auth.enter4pin');
        return;
    }
    error.value  = '';
    loading.value = true;
    try {
        const { data } = await publicPortalApi.loginPin(loginPhone.value, pin.value);
        publicAuth.setSession(data.data.user, data.data.token);
        finish();
    } catch (e) {
        error.value = e.response?.data?.message || t('auth.wrongPhoneOrPin');
    } finally {
        loading.value = false;
    }
}

function finish() {
    emit('success');
}
</script>

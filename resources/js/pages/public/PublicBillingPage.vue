<template>
    <div class="space-y-4">
        <div>
            <h1 class="text-lg font-bold text-[#0A1F44]">{{ $t('billing.title') }}</h1>
            <p class="text-xs text-gray-400 mt-0.5">{{ $t('billing.subtitle') }}</p>
        </div>

        <!-- Сводка -->
        <div v-if="!loading && payments.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-[#0A1F44]">{{ payments.length }}</div>
                <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wide mt-0.5">{{ $t('billing.totalInvoices') }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <div class="text-2xl font-bold text-[#1BA97F]">{{ paidCount }}</div>
                <div class="text-[10px] text-gray-400 font-medium uppercase tracking-wide mt-0.5">{{ $t('billing.paidCount') }}</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center col-span-2 sm:col-span-1">
                <div class="text-2xl font-bold" :class="unpaidCount > 0 ? 'text-red-500' : 'text-gray-300'">{{ unpaidCount }}</div>
                <div class="text-[10px] font-medium uppercase tracking-wide mt-0.5" :class="unpaidCount > 0 ? 'text-red-400' : 'text-gray-400'">{{ $t('billing.unpaidCount') }}</div>
            </div>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="space-y-3">
            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-4 bg-gray-100 rounded w-48 mb-2"></div>
                <div class="h-3 bg-gray-50 rounded w-full mb-1"></div>
                <div class="h-3 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else>
            <!-- Пусто -->
            <div v-if="!payments.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-400">{{ $t('billing.empty') }}</p>
            </div>

            <!-- Карточки счетов -->
            <div v-for="(p, idx) in payments" :key="p.id"
                class="bg-white rounded-2xl border shadow-sm overflow-hidden"
                :class="p.status === 'pending' ? 'border-amber-200' : 'border-gray-100'">

                <!-- Шапка счета -->
                <div class="px-5 py-3 flex items-center justify-between"
                    :class="p.status === 'succeeded' ? 'bg-[#1BA97F]/5' : p.status === 'pending' ? 'bg-amber-50' : 'bg-gray-50'">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" :class="p.status === 'succeeded' ? 'text-[#1BA97F]' : p.status === 'pending' ? 'text-amber-500' : 'text-gray-400'"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-xs font-semibold text-[#0A1F44]">
                            {{ $t('billing.invoiceNum', { num: invoiceNum(p) }) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <router-link v-if="p.case_id"
                            :to="{ name: 'me.cases.show', params: { id: p.case_id } }"
                            class="text-[10px] font-medium underline underline-offset-2 text-gray-400 hover:text-[#0A1F44] transition-colors">
                            {{ $t('billing.viewCase') }}
                        </router-link>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
                            :class="paymentStatusBadge(p.status)">
                            {{ paymentStatusLabel(p.status) }}
                        </span>
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Исполнитель + дата -->
                    <div class="flex items-start justify-between text-xs text-gray-400">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider mb-0.5">{{ $t('billing.executor') }}</div>
                            <div class="text-sm font-semibold text-[#0A1F44]">{{ p.agency_name }}</div>
                            <div v-if="p.agency_city">{{ p.agency_city }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-[10px] font-bold uppercase tracking-wider mb-0.5">{{ $t('billing.invoiceDate') }}</div>
                            <div class="text-sm font-semibold text-[#0A1F44]">{{ formatDateTime(p.created_at) }}</div>
                            <div v-if="p.case_number" class="font-mono mt-0.5">{{ $t('billing.caseNum') }} {{ p.case_number }}</div>
                        </div>
                    </div>

                    <!-- Таблица услуг -->
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <!-- Заголовок таблицы -->
                        <div class="grid grid-cols-12 gap-2 px-4 py-2 bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            <div class="col-span-7">{{ $t('billing.service') }}</div>
                            <div class="col-span-2 text-center">{{ $t('billing.qty') }}</div>
                            <div class="col-span-3 text-right">{{ $t('billing.sum') }}</div>
                        </div>
                        <!-- Строка услуги -->
                        <div class="grid grid-cols-12 gap-2 px-4 py-3 items-start">
                            <div class="col-span-7">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ codeToFlag(p.country_code) }}</span>
                                    <div>
                                        <div class="text-sm font-semibold text-[#0A1F44]">
                                            {{ p.package_name || (visaTypeLabel(p.visa_type) + ' — ' + (p.country_code || '')) }}
                                        </div>
                                        <div v-if="p.package_desc" class="text-[11px] text-gray-400 mt-0.5 leading-tight">{{ p.package_desc }}</div>
                                        <div v-if="p.package_days" class="text-[10px] text-gray-400 mt-0.5">
                                            {{ $t('payment.processingDays', { days: p.package_days }) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-span-2 text-center text-sm text-gray-600 self-center">{{ p.total_persons || 1 }}</div>
                            <div class="col-span-3 text-right text-sm font-bold text-[#0A1F44] self-center">
                                {{ formatPrice(p.amount, p.currency) }}
                            </div>
                        </div>
                    </div>

                    <!-- Что включено -->
                    <div v-if="p.services?.length" class="space-y-1.5">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('payment.includedServices') }}</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                            <div v-for="(s, si) in p.services" :key="si"
                                class="flex items-center gap-1.5 text-xs text-[#0A1F44]">
                                <svg class="w-3 h-3 text-[#1BA97F] shrink-0" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ s.name }}
                            </div>
                        </div>
                    </div>

                    <!-- ИТОГО к оплате -->
                    <div class="flex items-center justify-between p-4 rounded-xl bg-[#0A1F44] text-white">
                        <span class="text-sm font-semibold">{{ $t('payment.total') }}</span>
                        <span class="text-xl font-bold">{{ formatPrice(p.amount, p.currency) }}</span>
                    </div>

                    <!-- Оплата: pending -->
                    <template v-if="p.status === 'pending'">
                        <!-- Способы оплаты -->
                        <div>
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2">{{ $t('payment.chooseMethod') }}</div>
                            <div class="grid grid-cols-3 gap-3">
                                <button v-for="prov in PAYMENT_PROVIDERS" :key="prov.id"
                                    @click="initiatePayment(p, prov.id)"
                                    :disabled="payingId === p.id"
                                    class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-gray-100 hover:border-[#1BA97F] hover:shadow-md transition-all disabled:opacity-50">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl font-bold"
                                        :class="prov.bgClass">
                                        {{ prov.icon }}
                                    </div>
                                    <span class="text-xs font-semibold text-[#0A1F44]">{{ prov.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Тестовая оплата -->
                        <div class="pt-3 border-t border-dashed border-gray-200">
                            <button @click="testMarkAsPaid(p)"
                                :disabled="markingPaidId === p.id"
                                class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold transition-colors
                                    bg-gray-100 text-gray-600 hover:bg-[#1BA97F]/10 hover:text-[#1BA97F]
                                    disabled:opacity-50 flex items-center justify-center gap-2">
                                <svg v-if="markingPaidId === p.id" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ $t('payment.markAsPaid') }}
                            </button>
                            <p class="text-[10px] text-gray-400 text-center mt-1">{{ $t('payment.markAsPaidHint') }}</p>
                        </div>

                        <!-- Безопасность -->
                        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span class="text-[10px] text-gray-400">{{ $t('payment.securePayment') }}</span>
                        </div>
                    </template>

                    <!-- Оплачено -->
                    <div v-else-if="p.status === 'succeeded'" class="flex items-center gap-3 p-4 bg-[#1BA97F]/5 rounded-xl">
                        <div class="w-10 h-10 bg-[#1BA97F] rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-[#1BA97F]">{{ $t('billing.statusPaid') }}</div>
                            <div v-if="p.paid_at" class="text-xs text-gray-400">{{ formatDateTime(p.paid_at) }}</div>
                        </div>
                    </div>

                    <!-- Группа -->
                    <router-link v-if="p.group_id"
                        :to="{ name: 'me.groups.show', params: { id: p.group_id } }"
                        class="block text-center py-2.5 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 text-[#0A1F44] transition-colors">
                        {{ $t('billing.openGroup') }}
                    </router-link>
                </div>
            </div>
        </template>

        <!-- Toast -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                <div v-if="toast"
                    class="fixed top-5 right-5 z-50 bg-[#1BA97F] text-white text-sm px-5 py-3 rounded-xl shadow-lg max-w-xs font-medium">
                    {{ toast }}
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const loading  = ref(true);
const payments = ref([]);
const payingId = ref(null);
const markingPaidId = ref(null);
const toast = ref('');

const PAYMENT_PROVIDERS = [
    { id: 'click', label: 'Click', icon: 'C', bgClass: 'bg-blue-100 text-blue-600' },
    { id: 'payme', label: 'Payme', icon: 'P', bgClass: 'bg-cyan-100 text-cyan-600' },
    { id: 'uzum',  label: 'Uzum',  icon: 'U', bgClass: 'bg-purple-100 text-purple-600' },
];

const paidCount = computed(() => payments.value.filter(p => p.status === 'succeeded').length);
const unpaidCount = computed(() => payments.value.filter(p => p.status === 'pending').length);

const VISA_TYPE_LABELS = computed(() => ({
    tourist: t('portal.touristVisa'), business: t('portal.businessVisa'),
    student: t('portal.studentVisaFull'), work: t('portal.workVisa'),
    transit: t('portal.transitVisa'),
}));

function countryName(code) { return t(`countries.${code}`) !== `countries.${code}` ? t(`countries.${code}`) : code; }
function visaTypeLabel(type) { return VISA_TYPE_LABELS.value[type] || type; }

function invoiceNum(p) {
    return p.case_number ? 'INV-' + p.case_number : 'INV-' + p.id.slice(0, 8).toUpperCase();
}

function formatPrice(amount, currency) {
    if (!amount && amount !== 0) return '';
    const cur = currency || 'USD';
    if (cur === 'UZS') return amount.toLocaleString('ru-RU') + ' UZS';
    if (cur === 'USD') return '$' + amount.toLocaleString('ru-RU');
    return amount.toLocaleString('ru-RU') + ' ' + cur;
}

function paymentStatusBadge(status) {
    return {
        pending:   'bg-amber-100 text-amber-700',
        processing:'bg-blue-100 text-blue-700',
        succeeded: 'bg-green-100 text-green-700',
        failed:    'bg-red-100 text-red-700',
        refunded:  'bg-gray-100 text-gray-500',
    }[status] || 'bg-gray-100 text-gray-500';
}

function paymentStatusLabel(status) {
    return {
        pending: t('billing.statusPending'),
        succeeded: t('billing.statusPaid'),
        failed: t('billing.statusFailed'),
        refunded: t('billing.statusRefunded'),
    }[status] || status;
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    return new Date(dateStr).toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
}

function showToast(msg) {
    toast.value = msg;
    setTimeout(() => { toast.value = ''; }, 3000);
}

async function initiatePayment(p, provider) {
    payingId.value = p.id;
    try {
        const res = await publicPortalApi.initiatePayment({
            case_id: p.case_id,
            provider,
        });
        const url = res.data.data?.payment_url;
        if (url && url !== '#') {
            window.open(url, '_blank');
        }
        p.status = 'pending';
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        payingId.value = null;
    }
}

async function testMarkAsPaid(p) {
    markingPaidId.value = p.id;
    try {
        await publicPortalApi.markAsPaid({ case_id: p.case_id });
        p.status = 'succeeded';
        p.paid_at = new Date().toISOString();
        showToast(t('payment.markedAsPaid'));
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        markingPaidId.value = null;
    }
}

onMounted(async () => {
    try {
        const res = await publicPortalApi.billingHistory();
        payments.value = res.data.data?.payments ?? [];
    } catch { /* ignore */ } finally {
        loading.value = false;
    }
});
</script>

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
            <div v-for="p in payments" :key="p.id"
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
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
                        :class="paymentStatusBadge(p.status)">
                        {{ paymentStatusLabel(p.status) }}
                    </span>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Страна + виза + агентство -->
                    <div class="flex items-start gap-3">
                        <span class="text-2xl shrink-0">{{ codeToFlag(p.country_code) }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-bold text-[#0A1F44]">
                                {{ countryName(p.country_code) }} — {{ visaTypeLabel(p.visa_type) }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ p.agency_name }}<span v-if="p.agency_city">, {{ p.agency_city }}</span>
                            </div>
                            <div v-if="p.case_number" class="text-[10px] font-mono text-gray-400 mt-0.5">
                                {{ $t('billing.caseNum') }} {{ p.case_number }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-lg font-bold" :class="p.status === 'succeeded' ? 'text-[#1BA97F]' : 'text-[#0A1F44]'">
                                {{ formatPrice(p.amount, p.currency) }}
                            </div>
                        </div>
                    </div>

                    <!-- Детали пакета -->
                    <div v-if="p.package_name" class="border border-gray-100 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                            {{ $t('billing.serviceDetails') }}
                        </div>
                        <div class="px-4 py-3">
                            <div class="text-sm font-semibold text-[#0A1F44]">{{ p.package_name }}</div>
                            <div v-if="p.package_desc" class="text-xs text-gray-400 mt-0.5">{{ p.package_desc }}</div>
                            <div v-if="p.package_days" class="text-[10px] text-gray-400 mt-1">
                                {{ $t('payment.processingDays', { days: p.package_days }) }}
                            </div>
                        </div>
                        <!-- Услуги -->
                        <div v-if="p.services?.length" class="px-4 pb-3">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">{{ $t('payment.includedServices') }}</div>
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
                    </div>

                    <!-- Мета: дата, провайдер -->
                    <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-xs text-gray-400">
                        <div class="flex items-center gap-3">
                            <span>{{ formatDateTime(p.created_at) }}</span>
                            <span v-if="p.provider && p.provider !== 'pending'" class="font-medium uppercase px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ p.provider }}</span>
                        </div>
                        <div v-if="p.paid_at" class="flex items-center gap-1 text-[#1BA97F]">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $t('billing.paidOn') }} {{ formatDateTime(p.paid_at) }}
                        </div>
                    </div>

                    <!-- Кнопка оплатить (для неоплаченных) -->
                    <router-link v-if="p.status === 'pending' && p.case_id"
                        :to="{ name: 'me.cases.show', params: { id: p.case_id } }"
                        class="block w-full text-center py-2.5 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition-colors">
                        {{ $t('billing.goToPay') }}
                    </router-link>
                </div>
            </div>
        </template>
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

onMounted(async () => {
    try {
        const res = await publicPortalApi.billingHistory();
        payments.value = res.data.data?.payments ?? [];
    } catch { /* ignore */ } finally {
        loading.value = false;
    }
});
</script>

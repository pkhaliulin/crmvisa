<template>
    <div class="space-y-4">
        <div>
            <h1 class="text-lg font-bold text-[#0A1F44]">{{ $t('billing.title') }}</h1>
        </div>

        <div v-if="loading" class="space-y-3">
            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-4 bg-gray-100 rounded w-48 mb-2"></div>
                <div class="h-3 bg-gray-50 rounded w-full"></div>
            </div>
        </div>

        <template v-else>
            <div v-if="!payments.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-400">{{ $t('billing.empty') }}</p>
            </div>

            <div v-for="p in payments" :key="p.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ codeToFlag(p.country_code) }}</span>
                        <div>
                            <div class="text-sm font-semibold text-[#0A1F44]">
                                {{ countryName(p.country_code) }} — {{ visaTypeLabel(p.visa_type) }}
                            </div>
                            <div class="text-xs text-gray-400">{{ p.agency_name }}</div>
                        </div>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                        :class="paymentStatusBadge(p.status)">
                        {{ paymentStatusLabel(p.status) }}
                    </span>
                </div>
                <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-50">
                    <div class="flex items-center gap-3 text-xs text-gray-400">
                        <span class="font-medium uppercase">{{ p.provider }}</span>
                        <span>{{ formatDateTime(p.paid_at || p.created_at) }}</span>
                    </div>
                    <div class="text-base font-bold text-[#0A1F44]">{{ p.amount }} {{ p.currency }}</div>
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

const VISA_TYPE_LABELS = computed(() => ({
    tourist: t('portal.touristVisa'), business: t('portal.businessVisa'),
    student: t('portal.studentVisaFull'), work: t('portal.workVisa'),
    transit: t('portal.transitVisa'),
}));

function countryName(code) { return t(`countries.${code}`) !== `countries.${code}` ? t(`countries.${code}`) : code; }
function visaTypeLabel(type) { return VISA_TYPE_LABELS.value[type] || type; }

function paymentStatusBadge(status) {
    return {
        pending:   'bg-amber-50 text-amber-600',
        processing:'bg-blue-50 text-blue-600',
        succeeded: 'bg-green-50 text-green-700',
        failed:    'bg-red-50 text-red-600',
        refunded:  'bg-gray-100 text-gray-500',
    }[status] || 'bg-gray-100 text-gray-500';
}

function paymentStatusLabel(status) {
    return { pending: t('payment.pending'), succeeded: t('payment.paid'), failed: t('common.error'), refunded: 'Refund' }[status] || status;
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    return new Date(dateStr).toLocaleDateString(locale, { day: 'numeric', month: 'short', year: 'numeric' });
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

<template>
    <div class="space-y-4">
        <!-- Назад -->
        <button @click="router.push({ name: 'me.countries' })"
            class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $t('landing.countriesNav') }}
        </button>

        <div v-if="loading" class="space-y-4">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-48 mb-3"></div>
                <div class="h-4 bg-gray-50 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else-if="c">
            <!-- Заголовок + бейджи -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-4xl">{{ codeToFlag(c.country_code) }}</span>
                        <div>
                            <h1 class="text-xl font-bold text-[#0A1F44]">{{ localName }}</h1>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full" :class="regimeBadge">
                                    {{ $t(`visaRegime.${c.visa_regime}`) }}
                                </span>
                                <span v-if="c.continent" class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                    {{ $t(`continent.${c.continent}`) }}
                                </span>
                                <span v-if="c.is_popular" class="text-xs px-2 py-0.5 rounded-full bg-purple-50 text-purple-600">
                                    {{ $t('countryFlags.isPopular') }}
                                </span>
                                <span v-if="c.is_high_approval" class="text-xs px-2 py-0.5 rounded-full bg-green-50 text-green-600">
                                    {{ $t('countryFlags.isHighApproval') }}
                                </span>
                                <span v-if="c.is_high_refusal" class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-600">
                                    {{ $t('countryFlags.isHighRefusal') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ключевые метрики -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div v-if="c.visa_free_days" class="p-3 bg-green-50 rounded-xl text-center">
                        <div class="text-lg font-bold text-green-700">{{ c.visa_free_days }}</div>
                        <div class="text-[10px] text-green-600">{{ $t('countryPage.daysNoVisa') }}</div>
                    </div>
                    <div v-if="c.visa_on_arrival_days" class="p-3 bg-blue-50 rounded-xl text-center">
                        <div class="text-lg font-bold text-blue-700">{{ c.visa_on_arrival_days }}</div>
                        <div class="text-[10px] text-blue-600">{{ $t('countryPage.daysOnArrival') }}</div>
                    </div>
                    <div v-if="c.processing_days_standard" class="p-3 bg-gray-50 rounded-xl text-center">
                        <div class="text-lg font-bold text-[#0A1F44]">{{ c.processing_days_standard }}</div>
                        <div class="text-[10px] text-gray-500">{{ $t('countryPage.processingDays') }}</div>
                    </div>
                    <div v-if="c.processing_days_expedited" class="p-3 bg-orange-50 rounded-xl text-center">
                        <div class="text-lg font-bold text-orange-600">{{ c.processing_days_expedited }}</div>
                        <div class="text-[10px] text-orange-500">{{ $t('countryPage.expeditedDays') }}</div>
                    </div>
                    <div v-if="c.appointment_wait_days" class="p-3 bg-gray-50 rounded-xl text-center">
                        <div class="text-lg font-bold text-[#0A1F44]">{{ c.appointment_wait_days }}</div>
                        <div class="text-[10px] text-gray-500">{{ $t('countryPage.waitDays') }}</div>
                    </div>
                    <div v-if="c.buffer_days_recommended" class="p-3 bg-gray-50 rounded-xl text-center">
                        <div class="text-lg font-bold text-[#0A1F44]">{{ c.buffer_days_recommended }}</div>
                        <div class="text-[10px] text-gray-500">{{ $t('countryPage.bufferDays') }}</div>
                    </div>
                    <div v-if="totalDays" class="p-3 bg-[#0A1F44] rounded-xl text-center">
                        <div class="text-lg font-bold text-white">{{ totalDays }}</div>
                        <div class="text-[10px] text-gray-300">{{ $t('countryPage.totalDaysBefore') }}</div>
                    </div>
                    <div v-if="c.risk_level" class="p-3 rounded-xl text-center"
                        :class="c.risk_level === 'low' ? 'bg-green-50' : c.risk_level === 'high' ? 'bg-red-50' : 'bg-yellow-50'">
                        <div class="text-lg font-bold"
                            :class="c.risk_level === 'low' ? 'text-green-700' : c.risk_level === 'high' ? 'text-red-700' : 'text-yellow-700'">
                            {{ $t(`countryPage.risk_${c.risk_level}`) }}
                        </div>
                        <div class="text-[10px]"
                            :class="c.risk_level === 'low' ? 'text-green-500' : c.risk_level === 'high' ? 'text-red-500' : 'text-yellow-500'">
                            {{ $t('countryPage.riskLevel') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Чат-пузырь: совет VisaBor по срокам -->
            <ChatBubble v-if="timingTip" :msg="timingTip" color="green" />

            <!-- Стоимость -->
            <div v-if="hasCosts" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryPage.costsTitle') }}</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <CostCard v-if="c.visa_fee_usd" :label="$t('countryCosts.visaFee')" :value="`$${c.visa_fee_usd}`" icon="doc" />
                    <CostCard v-if="c.evisa_fee_usd" :label="$t('countryCosts.evisaFee')" :value="`$${c.evisa_fee_usd}`" icon="globe" />
                    <CostCard v-if="c.avg_flight_cost_usd" :label="$t('countryCosts.avgFlight')" :value="`~$${c.avg_flight_cost_usd}`" icon="plane" />
                    <CostCard v-if="c.avg_hotel_per_night_usd" :label="$t('countryCosts.avgHotel')" :value="`~$${c.avg_hotel_per_night_usd}`" icon="hotel" />
                </div>
                <div v-if="c.evisa_url" class="mt-3">
                    <a :href="c.evisa_url" target="_blank" rel="noopener"
                        class="inline-flex items-center gap-1.5 text-sm text-[#1BA97F] font-semibold hover:underline">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ $t('countryPage.applyEvisa') }}
                    </a>
                </div>
            </div>

            <!-- Финансовые требования -->
            <div v-if="hasFinance" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryPage.financeTitle') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div v-if="c.min_monthly_income_usd" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-10 h-10 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-lg shrink-0">$</div>
                        <div>
                            <div class="text-sm font-semibold text-[#0A1F44]">${{ c.min_monthly_income_usd }} / {{ $t('countryPage.month') }}</div>
                            <div class="text-[10px] text-gray-400">{{ $t('countryPage.minIncome') }}</div>
                        </div>
                    </div>
                    <div v-if="c.min_balance_usd" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-lg shrink-0">B</div>
                        <div>
                            <div class="text-sm font-semibold text-[#0A1F44]">${{ c.min_balance_usd }}</div>
                            <div class="text-[10px] text-gray-400">{{ $t('countryPage.minBalance') }}</div>
                        </div>
                    </div>
                </div>
                <p v-if="c.finance_notes" class="text-xs text-gray-500 mt-3 leading-relaxed">{{ c.finance_notes }}</p>
                <p v-if="c.finance_threshold_comment" class="text-xs text-gray-400 mt-1">{{ c.finance_threshold_comment }}</p>
            </div>

            <!-- Чат-пузырь: совет по финансам -->
            <ChatBubble v-if="financeTip" :msg="financeTip" color="yellow" />

            <!-- Необходимые документы и требования -->
            <div v-if="docRequirements.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryPage.docsTitle') }}</h2>
                <div class="space-y-2">
                    <div v-for="req in docRequirements" :key="req.key"
                        class="flex items-center gap-2.5 text-sm">
                        <div class="w-5 h-5 rounded-full flex items-center justify-center shrink-0"
                            :class="req.required ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-400'">
                            <svg v-if="req.required" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/>
                            </svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span :class="req.required ? 'text-[#0A1F44] font-medium' : 'text-gray-500'">{{ req.label }}</span>
                    </div>
                </div>
            </div>

            <!-- Подача документов -->
            <div v-if="hasSubmission" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryPage.submissionTitle') }}</h2>
                <div class="space-y-3">
                    <div v-if="submissionFlags.length" class="flex flex-wrap gap-2">
                        <span v-for="f in submissionFlags" :key="f"
                            class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-600 font-medium">
                            {{ f }}
                        </span>
                    </div>
                    <p v-if="c.submission_notes" class="text-xs text-gray-500 leading-relaxed">{{ c.submission_notes }}</p>
                </div>
            </div>

            <!-- Посольство / Визовый центр -->
            <div v-if="hasLocation" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryPage.whereToApply') }}</h2>

                <!-- Посольство -->
                <div v-if="c.has_embassy && c.embassy_name" class="mb-4">
                    <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl">
                        <div class="w-10 h-10 bg-[#0A1F44] text-white rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-[#0A1F44]">{{ c.embassy_name }}</div>
                            <div v-if="c.embassy_city" class="text-xs text-gray-500 mt-0.5">{{ c.embassy_city }}</div>
                            <div v-if="c.embassy_address" class="text-xs text-gray-400 mt-0.5">{{ c.embassy_address }}</div>
                            <div class="flex flex-wrap gap-3 mt-2">
                                <a v-if="c.embassy_phone" :href="`tel:${c.embassy_phone}`" class="text-xs text-[#1BA97F] hover:underline">{{ c.embassy_phone }}</a>
                                <a v-if="c.embassy_email" :href="`mailto:${c.embassy_email}`" class="text-xs text-[#1BA97F] hover:underline">{{ c.embassy_email }}</a>
                                <a v-if="c.embassy_website" :href="c.embassy_website" target="_blank" class="text-xs text-[#1BA97F] hover:underline">{{ $t('countryPage.website') }}</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Нет посольства — процедура -->
                <div v-if="!c.has_embassy" class="mb-4">
                    <div class="p-4 bg-amber-50 border border-amber-100 rounded-xl">
                        <p class="text-sm font-medium text-amber-800">{{ $t('countryPage.noEmbassyInUz') }}</p>
                        <p v-if="c.submission_procedure" class="text-xs text-amber-600 mt-1">
                            {{ $t(`countryPage.procedure_${c.submission_procedure}`, c.submission_procedure) }}
                        </p>
                    </div>
                    <div v-if="c.referral_embassy_name" class="mt-3 p-4 bg-gray-50 rounded-xl">
                        <div class="font-semibold text-sm text-[#0A1F44]">{{ c.referral_embassy_name }}</div>
                        <div v-if="c.referral_embassy_city" class="text-xs text-gray-500 mt-0.5">
                            {{ c.referral_embassy_city }}<span v-if="c.referral_embassy_country">, {{ c.referral_embassy_country }}</span>
                        </div>
                        <div v-if="c.referral_embassy_address" class="text-xs text-gray-400 mt-0.5">{{ c.referral_embassy_address }}</div>
                        <a v-if="c.referral_embassy_website" :href="c.referral_embassy_website" target="_blank"
                            class="text-xs text-[#1BA97F] hover:underline mt-1 inline-block">{{ $t('countryPage.website') }}</a>
                    </div>
                    <p v-if="c.no_embassy_notes" class="text-xs text-gray-600 mt-3 leading-relaxed whitespace-pre-line">{{ c.no_embassy_notes }}</p>
                </div>

                <!-- Визовый центр -->
                <div v-if="c.has_visa_center && c.visa_center_name">
                    <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-xl">
                        <div class="w-10 h-10 bg-[#1BA97F] text-white rounded-xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 0h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm text-[#0A1F44]">{{ c.visa_center_name }}</div>
                            <div v-if="c.visa_center_address" class="text-xs text-gray-400 mt-0.5">{{ c.visa_center_address }}</div>
                            <div class="flex flex-wrap gap-3 mt-2">
                                <a v-if="c.visa_center_phone" :href="`tel:${c.visa_center_phone}`" class="text-xs text-[#1BA97F] hover:underline">{{ c.visa_center_phone }}</a>
                                <a v-if="c.visa_center_email" :href="`mailto:${c.visa_center_email}`" class="text-xs text-[#1BA97F] hover:underline">{{ c.visa_center_email }}</a>
                                <a v-if="c.visa_center_website" :href="c.visa_center_website" target="_blank" class="text-xs text-[#1BA97F] hover:underline">{{ $t('countryPage.website') }}</a>
                            </div>
                            <p v-if="c.visa_center_notes" class="text-xs text-gray-500 mt-2 leading-relaxed">{{ c.visa_center_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ссылка на запись -->
                <a v-if="c.appointment_url" :href="c.appointment_url" target="_blank" rel="noopener"
                    class="mt-3 inline-flex items-center gap-1.5 text-sm text-[#1BA97F] font-semibold hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                    </svg>
                    {{ $t('countryPage.bookAppointment') }}
                </a>

                <!-- Карта -->
                <div v-if="c.latitude && c.longitude" class="mt-4 rounded-xl overflow-hidden border border-gray-200 h-40">
                    <iframe
                        :src="`https://www.openstreetmap.org/export/embed.html?bbox=${c.longitude - 0.01},${c.latitude - 0.01},${c.longitude + 0.01},${c.latitude + 0.01}&layer=mapnik&marker=${c.latitude},${c.longitude}`"
                        class="w-full h-full border-0" loading="lazy"></iframe>
                </div>
            </div>

            <!-- Чат-пузырь: советы по стране -->
            <ChatBubble v-if="countryTip" :msg="countryTip" color="blue" />

            <!-- eVisa -->
            <div v-if="c.evisa_available" class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-2xl border border-cyan-100 p-5">
                <h2 class="font-bold text-cyan-800 text-sm mb-2">{{ $t('countryPage.evisaTitle') }}</h2>
                <div class="flex items-center gap-4 text-sm">
                    <div v-if="c.evisa_processing_days" class="text-cyan-700">
                        {{ $t('countryPage.evisaProcessing') }}: <strong>{{ c.evisa_processing_days }} {{ $t('common.days') }}</strong>
                    </div>
                    <div v-if="c.evisa_fee_usd" class="text-cyan-700">
                        {{ $t('countryPage.evisaCost') }}: <strong>${{ c.evisa_fee_usd }}</strong>
                    </div>
                </div>
                <a v-if="c.evisa_url" :href="c.evisa_url" target="_blank" rel="noopener"
                    class="mt-3 inline-flex items-center gap-1.5 text-sm text-cyan-700 font-semibold hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    {{ $t('countryPage.applyEvisa') }}
                </a>
            </div>

            <!-- Правила посольства -->
            <div v-if="c.embassy_rules || c.embassy_description" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('countryPage.rulesTitle') }}</h2>
                <p v-if="c.embassy_description" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ c.embassy_description }}</p>
                <p v-if="c.embassy_rules" class="text-sm text-gray-600 leading-relaxed whitespace-pre-line mt-2">{{ c.embassy_rules }}</p>
            </div>

            <!-- Подать заявку -->
            <button @click="createDraftCase"
                :disabled="creatingCase"
                class="w-full py-3.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-sm font-semibold rounded-2xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                <svg v-if="creatingCase" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ creatingCase ? $t('portal.creating') : $t('landing.submitApplication') }}
            </button>
        </template>

        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <p class="font-semibold text-[#0A1F44]">{{ $t('countryDetail.notFound') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const route   = useRoute();
const router  = useRouter();
const loading = ref(true);
const c       = ref(null);
const creatingCase = ref(false);

// --- Компонент чат-пузырь ---
const ChatBubble = (props, { slots }) => {
    const colors = {
        green:  { bg: 'bg-gradient-to-br from-emerald-50 to-teal-50', border: 'border-emerald-200', label: 'text-emerald-500' },
        yellow: { bg: 'bg-gradient-to-br from-amber-50 to-orange-50', border: 'border-amber-200', label: 'text-amber-500' },
        blue:   { bg: 'bg-gradient-to-br from-blue-50 to-indigo-50', border: 'border-blue-200', label: 'text-blue-500' },
    };
    const cl = colors[props.color] || colors.green;
    return h('div', { class: 'relative mt-2' }, [
        h('div', { class: `absolute -top-[7px] left-5 w-3.5 h-3.5 rotate-45 border-l border-t ${cl.bg} ${cl.border} z-10` }),
        h('div', { class: `relative rounded-2xl p-3.5 shadow-sm border ${cl.bg} ${cl.border}` }, [
            h('div', { class: 'flex items-center gap-1.5 mb-1.5' }, [
                h('div', { class: 'w-5 h-5 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0' }, [
                    h('span', { class: 'text-[8px] font-bold text-white' }, 'VB'),
                ]),
                h('span', { class: `text-[10px] font-semibold ${cl.label}` }, 'VisaBor'),
            ]),
            h('p', { class: 'text-xs text-[#0A1F44] leading-relaxed' }, props.msg),
        ]),
    ]);
};
ChatBubble.props = { msg: String, color: String };

// --- Компонент карточки стоимости ---
const CostCard = (props) => {
    const icons = {
        doc:   'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        plane: 'M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5',
        hotel: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
    };
    return h('div', { class: 'p-3 bg-gray-50 rounded-xl flex items-center gap-3' }, [
        h('div', { class: 'w-9 h-9 bg-white rounded-lg flex items-center justify-center shadow-sm shrink-0' }, [
            h('svg', { class: 'w-4 h-4 text-[#1BA97F]', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5', viewBox: '0 0 24 24' }, [
                h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: icons[props.icon] || icons.doc }),
            ]),
        ]),
        h('div', {}, [
            h('div', { class: 'text-sm font-bold text-[#0A1F44]' }, props.value),
            h('div', { class: 'text-[10px] text-gray-400' }, props.label),
        ]),
    ]);
};
CostCard.props = { label: String, value: String, icon: String };

// --- Computed ---
const localName = computed(() => {
    if (!c.value) return '';
    const locale = i18n.global.locale.value;
    if (locale === 'uz' && c.value.name_uz) return c.value.name_uz;
    return c.value.name || c.value.country_code;
});

const regimeBadge = computed(() => ({
    visa_free:       'bg-green-50 text-green-700',
    visa_on_arrival: 'bg-blue-50 text-blue-600',
    evisa:           'bg-cyan-50 text-cyan-700',
    visa_required:   'bg-red-50 text-red-600',
}[c.value?.visa_regime] || 'bg-gray-100 text-gray-500'));

const totalDays = computed(() => {
    if (!c.value) return 0;
    return (c.value.processing_days_standard || 0) + (c.value.appointment_wait_days || 0) + (c.value.buffer_days_recommended || 0);
});

const hasCosts = computed(() => c.value && (c.value.visa_fee_usd || c.value.evisa_fee_usd || c.value.avg_flight_cost_usd || c.value.avg_hotel_per_night_usd));

const hasFinance = computed(() => c.value && (c.value.min_monthly_income_usd || c.value.min_balance_usd || c.value.finance_notes));

const docRequirements = computed(() => {
    if (!c.value) return [];
    const docs = [
        { key: 'bank_statement', label: t('requirements.bankStatement'), required: c.value.bank_statement_required },
        { key: 'insurance', label: t('requirements.insurance'), required: c.value.insurance_required },
        { key: 'hotel_booking', label: t('requirements.hotelBooking'), required: c.value.hotel_booking_required },
        { key: 'return_ticket', label: t('requirements.returnTicket'), required: c.value.return_ticket_required },
        { key: 'invitation', label: t('requirements.invitation'), required: c.value.invitation_required },
    ];
    // Показываем обязательные первыми
    return docs.sort((a, b) => (b.required ? 1 : 0) - (a.required ? 1 : 0));
});

const hasSubmission = computed(() => c.value && (c.value.appointment_required || c.value.personal_submission_required || c.value.biometrics_required || c.value.photo_required || c.value.submission_notes));

const submissionFlags = computed(() => {
    if (!c.value) return [];
    const flags = [];
    if (c.value.appointment_required) flags.push(t('countryPage.appointmentRequired'));
    if (c.value.personal_submission_required) flags.push(t('countryPage.personalRequired'));
    if (c.value.biometrics_required) flags.push(t('countryPage.biometricsRequired'));
    if (c.value.photo_required) flags.push(t('countryPage.photoRequired'));
    return flags;
});

const hasLocation = computed(() => c.value && (
    (c.value.has_embassy && c.value.embassy_name) ||
    (!c.value.has_embassy) ||
    (c.value.has_visa_center && c.value.visa_center_name) ||
    c.value.appointment_url
));

// --- Чат-пузыри (генерация советов) ---
const timingTip = computed(() => {
    if (!c.value) return '';
    const std = c.value.processing_days_standard;
    const wait = c.value.appointment_wait_days;
    const total = totalDays.value;
    if (total > 0) {
        return t('countryPage.tipTiming', { total, std: std || '?', wait: wait || '0' });
    }
    if (c.value.visa_regime === 'visa_free') {
        return t('countryPage.tipVisaFree', { days: c.value.visa_free_days || 30 });
    }
    return '';
});

const financeTip = computed(() => {
    if (!c.value) return '';
    const inc = c.value.min_monthly_income_usd;
    const bal = c.value.min_balance_usd;
    if (inc && bal) return t('countryPage.tipFinanceBoth', { income: inc, balance: bal });
    if (inc) return t('countryPage.tipFinanceIncome', { income: inc });
    return '';
});

const countryTip = computed(() => {
    if (!c.value) return '';
    if (!c.value.has_embassy && c.value.referral_embassy_city) {
        return t('countryPage.tipNoEmbassy', { city: c.value.referral_embassy_city, country: c.value.referral_embassy_country || '' });
    }
    if (c.value.is_high_approval) return t('countryPage.tipHighApproval');
    if (c.value.is_high_refusal) return t('countryPage.tipHighRefusal');
    return '';
});

// --- Actions ---
async function createDraftCase() {
    creatingCase.value = true;
    try {
        const res = await publicPortalApi.createCase({
            country_code: c.value.country_code,
            visa_type: 'tourist',
        });
        const caseId = res.data?.data?.id;
        router.push(caseId ? { name: 'me.cases.show', params: { id: caseId } } : { name: 'me.cases' });
    } catch (e) {
        alert(e?.response?.data?.message ?? t('portal.createError'));
    } finally {
        creatingCase.value = false;
    }
}

onMounted(async () => {
    try {
        const code = route.params.code;
        const res = await publicPortalApi.countryDetail(code);
        c.value = res.data.data ?? null;
    } catch {
        // fallback to list
        try {
            const res = await publicPortalApi.countries();
            c.value = (res.data.data ?? []).find(x => x.country_code === route.params.code) || null;
        } catch { /* ignore */ }
    } finally {
        loading.value = false;
    }
});
</script>

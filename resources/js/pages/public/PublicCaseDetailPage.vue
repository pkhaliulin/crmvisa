<template>
    <div class="space-y-4">

        <!-- Toast уведомление о загрузке -->
        <transition name="fade">
            <div v-if="uploadToast"
                :class="['fixed bottom-6 left-1/2 -translate-x-1/2 z-50',
                       'text-white text-sm font-semibold',
                       'px-5 py-3 rounded-2xl shadow-lg',
                       'flex items-center gap-2 pointer-events-none',
                       uploadToastError ? 'bg-red-500 shadow-red-500/30' : 'bg-[#1BA97F] shadow-[#1BA97F]/30']">
                <svg v-if="!uploadToastError" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                {{ uploadToast }}
            </div>
        </transition>

        <!-- Назад -->
        <button @click="router.push({ name: 'me.cases' })"
            class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $t('cases.myCases') }}
        </button>

        <!-- Скелетон загрузки -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-48 mb-3"></div>
                <div class="h-4 bg-gray-50 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else-if="caseData">

            <!-- === Баннер: неоплаченный счет === -->
            <div v-if="caseData.agency && caseData.public_status === 'awaiting_payment' && caseData.payment_status !== 'paid' && !isTerminal"
                class="bg-amber-50 border border-amber-200 rounded-2xl p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-bold text-amber-800">{{ $t('payment.invoiceBannerTitle') }}</div>
                        <div class="text-xs text-amber-600 mt-0.5">
                            {{ $t('payment.invoiceBannerDesc', { agency: caseData.agency?.name }) }}
                        </div>
                        <div class="text-sm font-bold text-amber-800 mt-1">
                            {{ formatPrice(invoiceTotalAmount, invoiceCurrency) }}
                        </div>
                    </div>
                    <button @click="router.push({ name: 'me.billing' })"
                        class="shrink-0 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl transition-colors">
                        {{ $t('payment.payNow') }}
                    </button>
                </div>
                <!-- Таймер аннулирования в баннере -->
                <div v-if="caseData.payment_expires_at && invoiceHoursLeft > 0"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl"
                    :class="invoiceHoursLeft <= 24 ? 'bg-red-100' : 'bg-amber-100'">
                    <svg class="w-4 h-4 shrink-0" :class="invoiceHoursLeft <= 24 ? 'text-red-500 animate-pulse' : 'text-amber-500'"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs font-semibold" :class="invoiceHoursLeft <= 24 ? 'text-red-700' : 'text-amber-700'">
                        {{ $t('payment.invoiceExpiresIn', { time: invoiceExpiresFormatted }) }}
                    </span>
                </div>
            </div>

            <!-- === Заголовок заявки === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ codeToFlag(caseData.country_code) }}</span>
                        <div>
                            <h1 class="text-lg font-bold text-[#0A1F44] leading-tight">
                                {{ countryName(caseData.country_code) }}
                            </h1>
                            <p class="text-sm text-gray-400 mt-0.5">{{ visaTypeLabel(caseData.visa_type) }}</p>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <p v-if="caseData.case_number" class="text-xs font-mono font-semibold text-[#0A1F44]/60">
                                    {{ $t('cases.caseNumber') }} {{ caseData.case_number }}
                                </p>
                                <span class="text-[10px] text-gray-400">{{ formatDate(caseData.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full"
                        :class="publicStatusBadge(caseData.public_status)"
                        :title="caseData.public_status_tooltip">
                        {{ caseData.public_status_label }}
                    </span>
                </div>

                <!-- Прогресс по public_status (10 шагов) -->
                <div class="mb-3">
                    <div class="flex items-center gap-0.5 mb-1">
                        <div v-for="(s, i) in getVisibleStatuses(caseData)" :key="s.key"
                            class="flex-1 h-2 rounded-full transition-colors cursor-help"
                            :class="getProgressColor(i, caseData.public_status, currentStepIdx)"
                            :title="$t('caseStatus.desc.' + s.key)">
                        </div>
                    </div>
                    <div class="flex mt-1">
                        <div v-for="(s, i) in getVisibleStatuses(caseData)" :key="'l'+s.key"
                            class="flex-1 text-center cursor-help"
                            :title="$t('caseStatus.desc.' + s.key)">
                            <span class="text-[7px] sm:text-[9px] leading-tight block truncate px-0.5"
                                :class="i === currentStepIdx ? 'text-[#1BA97F] font-bold' : i < currentStepIdx ? 'text-gray-500' : 'text-gray-300'">
                                {{ $t('caseStatus.step.' + s.key) }}
                            </span>
                        </div>
                    </div>
                    <p class="text-sm font-medium mt-2" :class="statusTextColor(caseData.public_status)">
                        {{ caseData.public_status_tooltip || caseData.public_status_label }}
                    </p>
                </div>

                <!-- Менеджер (в header) -->
                <div v-if="caseData.assignee" class="mt-4 p-3 rounded-xl bg-[#1BA97F]/5 border border-[#1BA97F]/20">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white font-bold text-sm shrink-0">
                            {{ caseData.assignee.name?.[0]?.toUpperCase() ?? '?' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-bold text-[#0A1F44]">{{ caseData.assignee.name }}</div>
                            <div class="text-xs text-gray-400">{{ $t('cases.visaSpecialist') }}</div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a v-if="caseData.assignee.telegram_username"
                                :href="`https://t.me/${caseData.assignee.telegram_username}`" target="_blank" rel="noopener"
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#229ED9]/10 hover:bg-[#229ED9]/20 transition-colors">
                                <svg class="w-4 h-4 text-[#229ED9]" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                                </svg>
                            </a>
                            <a v-if="caseData.assignee.phone"
                                :href="`tel:${caseData.assignee.phone}`"
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#1BA97F]/10 hover:bg-[#1BA97F]/20 transition-colors">
                                <svg class="w-4 h-4 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </a>
                            <a v-if="caseData.assignee.email"
                                :href="`mailto:${caseData.assignee.email}`"
                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                <div v-else-if="caseData.agency && caseData.public_status !== 'draft' && caseData.public_status !== 'awaiting_payment'"
                    class="mt-4 p-3 rounded-xl bg-gray-50 flex items-center gap-3">
                    <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500">{{ $t('cases.noManager') }}</div>
                        <div class="text-[10px] text-gray-400">{{ $t('cases.noManagerHint') }}</div>
                    </div>
                </div>

                <!-- Дедлайн подачи -->
                <div v-if="caseData.critical_date || caseData.deadline_info" class="mt-4 p-4 rounded-xl border"
                    :class="caseData.critical_date && deadlineUrgent(caseData.critical_date) ? 'bg-red-50 border-red-200' : 'bg-amber-50 border-amber-200'">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide mb-1"
                                :class="caseData.critical_date && deadlineUrgent(caseData.critical_date) ? 'text-red-600' : 'text-amber-600'">
                                {{ $t('cases.deadlineTitle') }}
                            </div>
                            <div v-if="caseData.critical_date" class="text-lg font-bold"
                                :class="deadlineClass(caseData.critical_date)">
                                {{ formatDate(caseData.critical_date) }}
                                <span class="text-sm font-normal ml-1">
                                    ({{ deadlineDaysText(caseData.critical_date) }})
                                </span>
                            </div>
                            <div v-else class="text-sm text-amber-600 font-medium">
                                {{ $t('cases.deadlineNoTravel') }}
                            </div>
                        </div>
                    </div>
                    <div v-if="caseData.deadline_info" class="mt-2 text-xs text-gray-500 leading-relaxed">
                        {{ $t('cases.deadlineCalc', {
                            processing: caseData.deadline_info.processing_days,
                            wait: caseData.deadline_info.appointment_wait_days,
                            buffer: caseData.deadline_info.buffer_days,
                            total: caseData.deadline_info.min_days_before_departure
                        }) }}
                    </div>
                </div>

                <!-- Даты -->
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <!-- Дата вылета (туда) -->
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 17h14M3.5 13.5L12 5l8.5 8.5"/>
                            </svg>
                            {{ $t('cases.departureDateLabel') }} <span class="text-red-500">*</span>
                        </div>
                        <input type="date" :value="caseData.travel_date || ''" @change="onTravelDateChange($event)"
                            :min="todayStr"
                            :disabled="savingTravelDate || isTerminal"
                            class="text-sm border rounded-lg px-2 py-1.5 w-full outline-none transition-colors cursor-pointer"
                            :class="savingTravelDate ? 'border-gray-100 bg-gray-100 text-gray-400' : 'border-gray-200 focus:border-[#1BA97F] hover:border-[#1BA97F]'"/>
                        <div v-if="savingTravelDate" class="text-[10px] text-[#1BA97F] mt-1">{{ $t('common.saving') }}...</div>
                        <div v-else-if="!caseData.travel_date" class="text-[10px] text-red-400 mt-1">{{ $t('cases.travelDateRequired') }}</div>
                    </div>
                    <!-- Дата возврата (обратно) -->
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 7h14M3.5 10.5L12 19l8.5-8.5"/>
                            </svg>
                            {{ $t('cases.returnDateLabel') }}
                        </div>
                        <input type="date" :value="caseData.return_date || ''" @change="onReturnDateChange($event)"
                            :min="returnDateMin"
                            :disabled="savingReturnDate || !caseData.travel_date || isTerminal"
                            class="text-sm border rounded-lg px-2 py-1.5 w-full outline-none transition-colors cursor-pointer"
                            :class="savingReturnDate ? 'border-gray-100 bg-gray-100 text-gray-400' : !caseData.travel_date ? 'border-gray-100 bg-gray-100 text-gray-300' : 'border-gray-200 focus:border-[#1BA97F] hover:border-[#1BA97F]'"/>
                        <div v-if="savingReturnDate" class="text-[10px] text-[#1BA97F] mt-1">{{ $t('common.saving') }}...</div>
                        <div v-else-if="!caseData.travel_date" class="text-[10px] text-gray-300 mt-1">{{ $t('cases.setDepartureFirst') }}</div>
                        <div v-else-if="tripDaysWarning" class="text-[10px] text-amber-600 mt-1">{{ tripDaysWarning }}</div>
                    </div>
                    <!-- Срок пребывания -->
                    <div v-if="caseData.travel_date && caseData.return_date" class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">{{ $t('cases.tripDuration') }}</div>
                        <div class="text-sm font-semibold" :class="tripDaysOverLimit ? 'text-red-600' : 'text-[#0A1F44]'">
                            {{ tripDays }} {{ $t('common.days') }}
                            <span v-if="caseData.max_stay_days" class="text-xs font-normal text-gray-400">
                                / {{ $t('cases.maxStayDays', { days: caseData.max_stay_days }) }}
                            </span>
                        </div>
                    </div>
                    <!-- Примерная стоимость поездки -->
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">{{ $t('cases.estimatedCost') }}</div>
                        <template v-if="caseData.travel_date && caseData.return_date && tripDays > 0 && estimatedCost > 0">
                            <div class="text-sm font-bold text-[#1BA97F]">~${{ estimatedCostTotal.toLocaleString('ru-RU') }}</div>
                            <div class="mt-1 space-y-0.5">
                                <div v-if="costBreakdown.visa" class="flex justify-between text-[9px] text-gray-400">
                                    <span>{{ $t('cases.costVisa') }}</span><span>${{ costBreakdown.visa }}</span>
                                </div>
                                <div v-if="costBreakdown.flights" class="flex justify-between text-[9px] text-gray-400">
                                    <span>{{ $t('cases.costFlights') }}</span><span>${{ costBreakdown.flights }}</span>
                                </div>
                                <div v-if="costBreakdown.hotel" class="flex justify-between text-[9px] text-gray-400">
                                    <span>{{ $t('cases.costHotel') }}</span><span>${{ costBreakdown.hotel }}</span>
                                </div>
                                <div v-if="costBreakdown.agency" class="flex justify-between text-[9px] text-gray-500 font-medium border-t border-gray-200 pt-0.5 mt-0.5">
                                    <span>{{ $t('cases.costAgency') }}</span><span>{{ costBreakdown.agencyFormatted }}</span>
                                </div>
                            </div>
                            <div class="text-[8px] text-gray-400 mt-1 leading-tight">{{ $t('cases.estimatedCostNote') }}</div>
                        </template>
                        <template v-else-if="!caseData.return_date">
                            <div class="text-[10px] text-gray-400 leading-tight">{{ $t('cases.setReturnForEstimate') }}</div>
                        </template>
                        <template v-else>
                            <div class="text-xs text-gray-400">--</div>
                        </template>
                    </div>
                </div>

                <!-- Заметки от агентства -->
                <div v-if="caseData.notes" class="mt-4 p-3 bg-blue-50 rounded-xl">
                    <div class="text-xs font-semibold text-blue-700 mb-1">{{ $t('cases.agencyComment') }}</div>
                    <p class="text-sm text-blue-800 leading-relaxed whitespace-pre-line">{{ caseData.notes }}</p>
                </div>
            </div>

            <!-- === Группа (если case.group_id) === -->
            <div v-if="caseData.group_id"
                class="bg-white rounded-2xl border border-[#1BA97F]/20 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#1BA97F]/10 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-[#0A1F44] text-sm">{{ $t('group.youAreInGroup') }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $t('group.groupCaseHint') }}</div>
                    </div>
                    <button @click="router.push({ name: 'me.groups.show', params: { id: caseData.group_id } })"
                        class="text-xs font-semibold text-[#1BA97F] hover:text-[#158a65] transition-colors shrink-0">
                        {{ $t('group.viewGroup') }}
                    </button>
                </div>
            </div>

            <!-- === Inline-выбор агентства (draft без агентства) === -->
            <div v-if="caseData.public_status === 'draft' && !caseData.agency" class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <h2 class="font-bold text-[#0A1F44] text-base mb-1">{{ $t('agencySelection.title') }}</h2>
                    <p class="text-sm text-gray-400 mb-4">{{ $t('cases.draftNoAgencyDesc') }}</p>

                    <div v-if="agenciesLoading" class="flex items-center justify-center py-8">
                        <svg class="w-6 h-6 animate-spin text-gray-300" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </div>
                    <div v-else-if="inlineAgencies.length" class="grid gap-3 sm:grid-cols-2">
                        <AgencyCard v-for="a in inlineAgencies" :key="a.id" :agency="a" @select="confirmSelectAgency" />
                    </div>
                    <div v-else class="text-center py-6 text-sm text-gray-400">
                        {{ $t('agencies.emptyTitle') }}
                    </div>
                </div>
            </div>

            <!-- === СЧЕТ НА ОПЛАТУ (awaiting_payment) === -->
            <div v-if="caseData.agency && caseData.public_status === 'awaiting_payment' && (caseData.payment_status === 'unpaid' || caseData.payment_status === 'pending') && !isTerminal"
                ref="paymentSection"
                class="bg-white rounded-2xl border-2 border-amber-200 shadow-sm overflow-hidden">

                <!-- Шапка счета -->
                <div class="bg-[#0A1F44] px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-white font-bold text-sm">{{ $t('payment.invoiceTitle') }}</div>
                            <div class="text-white/60 text-[10px]">{{ $t('payment.invoiceNumber', { number: invoiceNumber }) }}</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div v-if="caseData.payment_expires_at && invoiceHoursLeft > 0"
                            class="flex items-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-full"
                            :class="invoiceHoursLeft <= 24 ? 'bg-red-500 text-white' : 'bg-white/20 text-white/90'">
                            <svg class="w-3 h-3" :class="invoiceHoursLeft <= 24 ? 'animate-pulse' : ''"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ invoiceExpiresFormatted }}
                        </div>
                        <div class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-400 text-amber-900">
                            {{ $t('payment.statusUnpaid') }}
                        </div>
                    </div>
                </div>

                <!-- Предупреждение об аннулировании -->
                <div v-if="caseData.payment_expires_at && invoiceHoursLeft > 0"
                    class="mx-5 mb-0 -mt-1 flex items-center gap-2 px-4 py-2.5 rounded-xl"
                    :class="invoiceHoursLeft <= 24 ? 'bg-red-50 border border-red-200' : 'bg-amber-50 border border-amber-200'">
                    <svg class="w-4 h-4 shrink-0" :class="invoiceHoursLeft <= 24 ? 'text-red-400' : 'text-amber-400'"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                    </svg>
                    <span class="text-xs" :class="invoiceHoursLeft <= 24 ? 'text-red-600' : 'text-amber-600'">
                        {{ $t('payment.invoiceWillExpire', { time: invoiceExpiresFormatted }) }}
                    </span>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Стороны: от кого / кому -->
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <div class="text-gray-400 font-medium uppercase tracking-wide mb-1">{{ $t('payment.invoiceFrom') }}</div>
                            <div class="font-semibold text-[#0A1F44]">{{ caseData.agency?.name }}</div>
                            <div v-if="caseData.agency?.city" class="text-gray-400">{{ caseData.agency.city }}</div>
                        </div>
                        <div>
                            <div class="text-gray-400 font-medium uppercase tracking-wide mb-1">{{ $t('payment.invoiceDate') }}</div>
                            <div class="font-semibold text-[#0A1F44]">{{ formatDate(caseData.created_at) }}</div>
                            <div class="text-gray-400 mt-0.5">{{ $t('payment.invoiceCase') }} {{ caseData.case_number || '—' }}</div>
                        </div>
                    </div>

                    <!-- Визовая услуга -->
                    <div class="border border-gray-100 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 px-4 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider grid grid-cols-12 gap-2">
                            <div class="col-span-6">{{ $t('billing.participants') }}</div>
                            <div class="col-span-3 text-center">{{ $t('billing.discount') }}</div>
                            <div class="col-span-3 text-right">{{ $t('billing.sum') }}</div>
                        </div>
                        <!-- Breakdown из сервера -->
                        <template v-if="caseData.price_breakdown?.length">
                            <div v-for="(row, ri) in caseData.price_breakdown" :key="'br'+ri"
                                class="grid grid-cols-12 gap-2 px-4 py-2.5 items-center border-t border-gray-50">
                                <div class="col-span-6 flex items-center gap-2 min-w-0">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                        :class="row.role === 'applicant' ? 'bg-[#0A1F44]' : row.role === 'child' ? 'bg-amber-100' : 'bg-[#1BA97F]/20'">
                                        <span class="text-xs font-bold"
                                            :class="row.role === 'applicant' ? 'text-white' : row.role === 'child' ? 'text-amber-600' : 'text-[#1BA97F]'">
                                            {{ row.name?.charAt(0) || '?' }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-[#0A1F44] truncate">{{ row.name }}</div>
                                        <div class="text-[10px] text-gray-400">{{ invoiceRoleLabel(row.role) }}</div>
                                    </div>
                                </div>
                                <div class="col-span-3 text-center">
                                    <span v-if="row.discount > 0" class="text-xs font-bold text-[#1BA97F]">-{{ row.discount }}%</span>
                                    <span v-else class="text-xs text-gray-300">—</span>
                                </div>
                                <div class="col-span-3 text-right text-xs font-bold text-[#0A1F44]">
                                    {{ formatPrice(row.price, caseData.payment_currency || caseData.package?.currency) }}
                                </div>
                            </div>
                        </template>
                        <!-- Фолбэк: только пакет без breakdown -->
                        <template v-else>
                            <div v-if="caseData.package" class="px-4 py-3 grid grid-cols-12 gap-2 items-start">
                                <div class="col-span-6">
                                    <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.package.name }}</div>
                                    <div v-if="caseData.package.description" class="text-[11px] text-gray-400 mt-0.5 leading-snug">{{ caseData.package.description }}</div>
                                    <div v-if="caseData.package.processing_days" class="text-[10px] text-gray-400 mt-1">
                                        {{ $t('payment.processingDays', { days: caseData.package.processing_days }) }}
                                    </div>
                                </div>
                                <div class="col-span-3 text-center text-xs text-gray-300">—</div>
                                <div class="col-span-3 text-right text-sm font-bold text-[#0A1F44]">{{ formatPrice(caseData.package.price, caseData.package.currency) }}</div>
                            </div>
                            <div v-else class="px-4 py-3 grid grid-cols-12 gap-2">
                                <div class="col-span-6 text-sm text-[#0A1F44]">{{ $t('payment.visaService') }}</div>
                                <div class="col-span-3 text-center text-sm text-gray-600">—</div>
                                <div class="col-span-3 text-right text-sm text-gray-400">—</div>
                            </div>
                        </template>
                        <!-- ИТОГО в таблице (если несколько участников) -->
                        <div v-if="caseData.price_breakdown?.length > 1" class="grid grid-cols-12 gap-2 px-4 py-2.5 items-center border-t-2 border-gray-200 bg-gray-50">
                            <div class="col-span-6 text-xs font-bold text-[#0A1F44] uppercase">{{ $t('payment.total') }}</div>
                            <div class="col-span-3 text-center text-[10px] text-gray-400">{{ caseData.price_breakdown.length }} {{ $t('billing.persons') }}</div>
                            <div class="col-span-3 text-right text-sm font-bold text-[#0A1F44]">{{ formatPrice(invoiceTotalAmount, invoiceCurrency) }}</div>
                        </div>
                    </div>

                    <!-- Пакет и срок -->
                    <div v-if="caseData.package" class="text-xs text-gray-400">
                        <span class="font-semibold text-[#0A1F44]">{{ caseData.package.name }}</span>
                        <span v-if="caseData.package.processing_days"> — {{ $t('payment.processingDays', { days: caseData.package.processing_days }) }}</span>
                    </div>

                    <!-- Что включено -->
                    <div v-if="caseData.package?.services?.length" class="space-y-1.5">
                        <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('payment.includedServices') }}</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                            <div v-for="(s, si) in caseData.package.services" :key="si"
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
                        <span class="text-xl font-bold">{{ formatPrice(invoiceTotalAmount, invoiceCurrency) }}</span>
                    </div>

                    <!-- Статус pending -->
                    <div v-if="caseData.payment_status === 'pending'" class="flex items-center gap-3 p-4 bg-amber-50 rounded-xl">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-amber-700">{{ $t('payment.pending') }}</span>
                    </div>

                    <!-- Кнопка перехода к оплате в разделе Счета -->
                    <div v-else>
                        <button @click="router.push({ name: 'me.billing' })"
                            class="w-full py-3.5 bg-[#1BA97F] hover:bg-[#0d7a5c] text-white text-sm font-bold rounded-xl transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            {{ $t('payment.payNow') }}
                        </button>
                    </div>

                    <!-- Безопасность -->
                    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
                        <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span class="text-[10px] text-gray-400">{{ $t('payment.securePayment') }}</span>
                    </div>
                </div>
            </div>

            <!-- === Оплачено === -->
            <div v-if="caseData.payment_status === 'paid' && !isTerminal"
                class="bg-[#1BA97F]/5 border border-[#1BA97F]/20 rounded-2xl p-5 flex items-center gap-3">
                <div class="w-10 h-10 bg-[#1BA97F] rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <div class="text-sm font-semibold text-[#1BA97F]">{{ $t('payment.paid') }}</div>
                </div>
            </div>

            <!-- === Дата приема в посольство === -->
            <div v-if="caseData.public_status !== 'draft' && caseData.public_status !== 'awaiting_payment' && !isTerminal"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <h2 class="font-bold text-[#0A1F44] text-sm mb-3">{{ $t('appointment.title') }}</h2>
                <div v-if="caseData.appointment_date" class="space-y-2">
                    <div class="flex items-center gap-3 p-3 rounded-xl bg-green-50">
                        <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <div class="text-sm font-semibold text-green-700">{{ formatDate(caseData.appointment_date) }}{{ caseData.appointment_time ? ', ' + caseData.appointment_time : '' }}</div>
                            <div v-if="caseData.appointment_location" class="text-xs text-green-600 mt-0.5">{{ caseData.appointment_location }}</div>
                        </div>
                    </div>
                </div>
                <div v-else class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                    <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm text-gray-400">{{ $t('appointment.notAssigned') }}</span>
                </div>
            </div>

            <!-- === Сменить агентство (до оплаты) === -->
            <div v-if="caseData.agency && caseData.payment_status !== 'paid' && caseData.public_status !== 'draft' && !isTerminal"
                class="flex justify-end">
                <button @click="showChangeAgencyModal = true"
                    class="text-xs text-gray-400 hover:text-red-500 transition-colors underline underline-offset-2">
                    {{ $t('agencySelection.changeAgency') }}
                </button>
            </div>

            <!-- === Чек-лист документов с вкладками === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('cases.documentsTitle') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('cases.documentsHint') }}</p>
                    </div>
                    <div v-if="allDocsTotal > 0" class="text-right">
                        <div class="text-lg font-bold"
                            :class="allDocsUploaded >= allDocsTotal ? 'text-[#1BA97F]' : 'text-[#0A1F44]'">
                            {{ allDocsUploaded }}/{{ allDocsTotal }}
                        </div>
                        <div class="text-xs text-gray-400">{{ $t('cases.uploaded') }}</div>
                    </div>
                </div>

                <!-- Вкладки: Заявитель + каждый член семьи -->
                <div v-if="docTabs.length > 1" class="px-5 pt-3 flex gap-1 overflow-x-auto scrollbar-hide">
                    <button v-for="(tab, idx) in docTabs" :key="tab.key"
                        @click="activeDocTab = tab.key"
                        class="shrink-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap"
                        :class="activeDocTab === tab.key
                            ? 'bg-[#1BA97F] text-white'
                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                        {{ tab.label }}
                        <span class="ml-1 text-[10px] opacity-70">{{ tab.uploaded }}/{{ tab.total }}</span>
                    </button>
                </div>

                <!-- Прогресс документов текущей вкладки -->
                <div v-if="activeTabChecklist.length" class="px-5 pt-3 pb-1">
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                            :class="activeTabUploaded >= activeTabChecklist.length ? 'bg-[#1BA97F]' : 'bg-amber-400'"
                            :style="{ width: (activeTabChecklist.length ? (activeTabUploaded / activeTabChecklist.length * 100) : 0) + '%' }">
                        </div>
                    </div>
                </div>

                <!-- Список документов текущей вкладки -->
                <div v-if="activeTabChecklist.length" class="divide-y divide-gray-50 mt-1">
                    <div v-for="item in activeTabChecklist" :key="item.id"
                        class="px-5 py-3.5 flex items-start gap-3">
                        <div class="mt-0.5 shrink-0 w-5 h-5 rounded-full flex items-center justify-center"
                            :class="{
                                'bg-[#1BA97F]':   item.status === 'approved' || item.status === 'uploaded' || item.is_checked,
                                'bg-amber-400':   item.status === 'not_available',
                                'bg-gray-200':    (item.status === 'pending' || item.status === 'rejected') && !item.is_checked,
                            }">
                            <svg v-if="item.status === 'approved' || item.status === 'uploaded' || item.is_checked" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg v-else-if="item.status === 'not_available'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            <svg v-else class="w-3 h-3 text-gray-400"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-[#0A1F44]">{{ item.name }}</span>
                            </div>
                            <p v-if="item.description" class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ item.description }}</p>
                            <p v-if="item.notes" class="text-xs text-blue-600 mt-0.5">{{ item.notes }}</p>

                            <div class="mt-1 text-xs font-medium"
                                :class="{
                                    'text-[#1BA97F]': item.status === 'approved' || item.status === 'uploaded' || item.is_checked,
                                    'text-amber-500': item.status === 'not_available',
                                    'text-red-500':   item.status === 'rejected',
                                    'text-gray-500':  item.status === 'pending' && !item.is_checked,
                                }">
                                {{ statusLabel(item.status, item) }}
                            </div>
                            <!-- Имя загруженного файла + предпросмотр -->
                            <button v-if="item.file_name && item.document_id"
                                @click="openPreview(item)"
                                class="mt-0.5 text-[11px] text-blue-500 hover:text-blue-700 hover:underline truncate max-w-full block text-left transition-colors">
                                {{ item.file_name }}
                            </button>

                            <!-- Checkbox type: кнопка отметки -->
                            <button v-if="item.type === 'checkbox' && item.responsibility !== 'agency' && !isTerminal"
                                @click="toggleCheckbox(item)"
                                :disabled="checkboxLoading[item.id]"
                                class="inline-flex items-center gap-1.5 text-xs font-medium mt-1.5 select-none transition-colors"
                                :class="item.is_checked
                                    ? 'text-[#1BA97F] hover:text-red-500 cursor-pointer'
                                    : 'text-blue-600 hover:text-blue-800 cursor-pointer'">
                                <svg v-if="!checkboxLoading[item.id]" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path v-if="item.is_checked" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span>{{ item.is_checked ? $t('cases.checkboxChecked') : $t('cases.checkboxMark') }}</span>
                            </button>

                            <!-- Upload type: кнопка загрузки файла -->
                            <label v-else-if="item.type !== 'checkbox' && item.responsibility !== 'agency' && item.status !== 'approved' && !isTerminal"
                                class="inline-flex items-center gap-1.5 text-xs font-medium
                                       cursor-pointer mt-1.5 select-none"
                                :class="[
                                    { 'opacity-50 pointer-events-none': uploading[item.id] },
                                    item.status === 'uploaded'
                                        ? 'text-gray-500 hover:text-gray-700'
                                        : 'text-blue-600 hover:text-blue-800'
                                ]">
                                <svg v-if="!uploading[item.id] && item.status === 'uploaded'" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182M4.031 9.865H9.02"/>
                                </svg>
                                <svg v-else-if="!uploading[item.id]" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span>{{ uploading[item.id] ? $t('cases.uploading') : (item.status === 'uploaded' ? $t('cases.replaceFile') : $t('cases.upload')) }}</span>
                                <input type="file" class="hidden"
                                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                    @change="(e) => uploadDoc(item.id, e)"/>
                            </label>
                            <div v-if="item.type !== 'checkbox' && item.responsibility !== 'agency' && !item.document_id && (item.status === 'pending' || item.status === 'not_available') && !isTerminal"
                                class="mt-1 flex items-center gap-3 flex-wrap">
                                <span v-if="item.status === 'pending'" class="text-[10px] text-gray-400">
                                    {{ $t('cases.allowedFormats') }} &middot; max {{ MAX_FILE_SIZE_MB }} MB
                                </span>
                                <button v-if="item.status !== 'not_available'"
                                    @click="markNotAvailable(item)"
                                    :disabled="notAvailableLoading[item.id]"
                                    class="text-[10px] text-amber-500 hover:text-amber-700 font-medium transition-colors disabled:opacity-50">
                                    {{ $t('cases.noDocument') }}
                                </button>
                                <button v-else
                                    @click="undoNotAvailable(item)"
                                    :disabled="notAvailableLoading[item.id]"
                                    class="text-[10px] text-blue-500 hover:text-blue-700 font-medium transition-colors disabled:opacity-50">
                                    {{ $t('cases.undoNoDocument') }}
                                </button>
                            </div>
                            <div v-else-if="item.responsibility === 'agency' && item.status !== 'approved'"
                                class="mt-1 text-xs text-gray-400 italic">
                                {{ item.status === 'done' ? $t('cases.doneByAgency') : $t('cases.waitingAgency') }}
                            </div>

                            <!-- Кнопка "+ ещё" для повторяемых документов -->
                            <button v-if="item.is_repeatable && !isTerminal && (item.status === 'uploaded' || item.status === 'approved')"
                                @click="repeatSlot(item)"
                                :disabled="repeating[item.id]"
                                class="mt-1.5 inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors disabled:opacity-50">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('cases.addMoreFile') }}
                            </button>
                            <!-- Кнопка удалить для доп. копий (не-обязательных) -->
                            <button v-if="!item.is_required && !isTerminal"
                                @click="deleteSlot(item)"
                                :disabled="deleting[item.id]"
                                class="mt-1.5 inline-flex items-center gap-1 text-xs text-red-400 hover:text-red-600 font-medium transition-colors disabled:opacity-50">
                                <svg v-if="!deleting[item.id]" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <svg v-else class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ $t('cases.deleteSlot') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="px-5 py-6 text-center">
                    <div class="text-sm text-gray-400">{{ $t('cases.noDocsList') }}</div>
                </div>
            </div>

            <!-- === Члены семьи === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('family.title') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('family.hint') }}</p>
                    </div>
                    <button v-if="!isTerminal" @click="showFamilyModal = true"
                        class="text-xs font-semibold text-[#1BA97F] hover:text-[#0d7a5c] transition-colors flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('family.add') }}
                    </button>
                </div>

                <!-- Привязанные к заявке -->
                <div v-if="caseFamilyMembers.length" class="divide-y divide-gray-50">
                    <div v-for="fm in caseFamilyMembers" :key="fm.id" class="px-5 py-3.5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                    :class="fm.is_minor ? 'bg-amber-100 text-amber-700' : 'bg-[#0A1F44]/10 text-[#0A1F44]'">
                                    {{ fm.name?.[0]?.toUpperCase() }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-[#0A1F44]">{{ fm.name }}</div>
                                    <div class="text-xs text-gray-400 flex items-center gap-2 flex-wrap">
                                        <span>{{ $t('family.rel.' + fm.relationship) }}</span>
                                        <span v-if="fm.is_minor" class="text-amber-600 font-medium">{{ $t('family.minor') }}</span>
                                        <span v-if="fm.dob" class="text-gray-300">{{ formatDate(fm.dob) }}</span>
                                        <span v-if="fm.citizenship" class="text-gray-300">{{ codeToFlag(fm.citizenship) }} {{ fm.citizenship }}</span>
                                    </div>
                                    <div v-if="fm.passport_number" class="text-[11px] text-gray-400 font-mono mt-0.5">
                                        {{ $t('family.passportShort') }}: {{ fm.passport_number }}
                                    </div>
                                </div>
                            </div>
                            <button v-if="!isTerminal" @click="detachFamilyFromCase(fm)"
                                class="text-xs text-gray-400 hover:text-red-500 transition-colors shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Пусто -->
                <div v-else class="px-5 py-6 text-center text-sm text-gray-400">
                    {{ $t('family.empty') }}
                </div>
            </div>

            <!-- Модал добавления/привязки семьи -->
            <div v-if="showFamilyModal"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
                @click.self="showFamilyModal = false">
                <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-xl max-h-[85vh] flex flex-col">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                        <h3 class="text-base font-bold text-[#0A1F44]">{{ $t('family.modalTitle') }}</h3>
                        <button @click="showFamilyModal = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="overflow-y-auto flex-1 p-5 space-y-4">
                        <!-- Существующие (из профиля) -->
                        <div v-if="profileFamily.length">
                            <div class="text-xs font-semibold text-gray-400 uppercase mb-2">{{ $t('family.fromProfile') }}</div>
                            <div class="space-y-2">
                                <div v-for="fm in profileFamily" :key="fm.id"
                                    class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-[#1BA97F]/30 transition-colors">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0"
                                        :class="fm.is_minor ? 'bg-amber-100 text-amber-700' : 'bg-[#0A1F44]/10 text-[#0A1F44]'">
                                        {{ fm.name?.[0]?.toUpperCase() }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-[#0A1F44]">{{ fm.name }}</div>
                                        <div class="text-xs text-gray-400">{{ $t('family.rel.' + fm.relationship) }}</div>
                                    </div>
                                    <button v-if="!isFamilyAttached(fm.id)"
                                        @click="attachFamily(fm.id)"
                                        :disabled="familyAttaching"
                                        class="text-xs font-semibold text-[#1BA97F] hover:text-[#0d7a5c] px-3 py-1.5 rounded-lg bg-[#1BA97F]/10 hover:bg-[#1BA97F]/20 transition-colors disabled:opacity-50">
                                        {{ $t('family.attach') }}
                                    </button>
                                    <span v-else class="text-xs text-gray-400 px-3 py-1.5">{{ $t('family.attached') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Добавить нового -->
                        <div class="border-t border-gray-100 pt-4">
                            <div class="text-xs font-semibold text-gray-400 uppercase mb-2">{{ $t('family.addNew') }}</div>
                            <p class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 mb-3">
                                {{ $t('family.latinWarning') }}
                            </p>
                            <div class="space-y-3">
                                <!-- Имя + Фамилия (латиница) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.firstName') }} *</label>
                                        <input v-model="newFamilyForm.first_name" type="text"
                                            @input="newFamilyForm.first_name = newFamilyForm.first_name.replace(/[^a-zA-Z\s\-']/g, '').toUpperCase()"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors font-mono uppercase"
                                            placeholder="JOHN"/>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.lastName') }} *</label>
                                        <input v-model="newFamilyForm.last_name" type="text"
                                            @input="newFamilyForm.last_name = newFamilyForm.last_name.replace(/[^a-zA-Z\s\-']/g, '').toUpperCase()"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors font-mono uppercase"
                                            placeholder="DOE"/>
                                    </div>
                                </div>
                                <!-- Родство + Дата рождения -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.relationship') }} *</label>
                                        <SearchSelect v-model="newFamilyForm.relationship"
                                            :items="relationshipItems"
                                            placeholder="--" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.dob') }} <span class="text-red-500">*</span></label>
                                        <input v-model="newFamilyForm.dob" type="date"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                                    </div>
                                </div>
                                <!-- Пол + Гражданство (searchable dropdown) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.gender') }} <span class="text-red-500">*</span></label>
                                        <SearchSelect v-model="newFamilyForm.gender"
                                            :items="genderItems"
                                            placeholder="--" />
                                    </div>
                                    <div class="relative">
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.citizenship') }} <span class="text-red-500">*</span></label>
                                        <input
                                            v-model="citizenshipSearch"
                                            @focus="showCitizenshipDropdown = true"
                                            @input="showCitizenshipDropdown = true"
                                            type="text"
                                            autocomplete="off"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"
                                            :class="newFamilyForm.citizenship ? 'border-[#1BA97F]' : ''"
                                            :placeholder="$t('family.citizenshipPlaceholder')"/>
                                        <div v-if="showCitizenshipDropdown && filteredCountries.length"
                                            class="absolute z-20 left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-48 overflow-y-auto">
                                            <button v-for="c in filteredCountries" :key="c.code"
                                                @mousedown.prevent="selectCitizenship(c)"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-[#1BA97F]/5 flex items-center gap-2 transition-colors">
                                                <span>{{ c.flag }}</span>
                                                <span>{{ c.name }}</span>
                                                <span class="text-gray-400 text-xs ml-auto">{{ c.code }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Паспорт (формат AA-1234567 как в профиле) -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.passportNumber') }} <span class="text-red-500">*</span></label>
                                        <div class="flex items-stretch rounded-xl border transition-colors"
                                            :class="fmPassportValid ? 'border-[#1BA97F]' : 'border-gray-200 focus-within:border-[#1BA97F]'">
                                            <input
                                                ref="fmPassportSeriesInput"
                                                :value="fmPassportSeries"
                                                @input="handleFmSeriesInput"
                                                placeholder="AA"
                                                maxlength="2"
                                                autocomplete="off"
                                                spellcheck="false"
                                                class="w-16 py-2.5 text-center text-sm font-mono uppercase outline-none bg-gray-50 border-r border-gray-200 rounded-l-xl text-[#0A1F44] font-bold"/>
                                            <div class="flex items-center px-2.5 text-gray-300 text-sm select-none font-light shrink-0">—</div>
                                            <input
                                                ref="fmPassportNumberInput"
                                                :value="fmPassportDigits"
                                                @input="handleFmDigitsInput"
                                                placeholder="1234567"
                                                maxlength="7"
                                                inputmode="numeric"
                                                autocomplete="off"
                                                class="flex-1 min-w-0 px-1 py-2.5 text-sm font-mono outline-none tracking-[0.2em] text-[#0A1F44]"/>
                                            <div v-if="fmPassportValid" class="flex items-center pr-2 shrink-0">
                                                <div class="w-4 h-4 rounded-full bg-[#1BA97F] flex items-center justify-center">
                                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $t('family.passportFormat') }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('family.passportExpires') }} <span class="text-red-500">*</span></label>
                                        <input v-model="newFamilyForm.passport_expires_at" type="date"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                                    </div>
                                </div>
                                <button @click="createAndAttachFamily"
                                    :disabled="!newFamilyForm.first_name || !newFamilyForm.last_name || !newFamilyForm.relationship || !newFamilyForm.dob || !newFamilyForm.gender || !newFamilyForm.citizenship || !fmPassportValid || !newFamilyForm.passport_expires_at || familySaving"
                                    class="w-full py-3 bg-[#1BA97F] hover:bg-[#0d7a5c] text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                                    <svg v-if="familySaving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    {{ familySaving ? $t('common.loading') : $t('family.addAndAttach') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Модал подтверждения открепления семьи -->
            <div v-if="detachTarget"
                class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
                @click.self="detachTarget = null">
                <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl p-6">
                    <h3 class="text-base font-bold text-[#0A1F44] mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
                    <p class="text-sm text-gray-500 mb-5">{{ $t('family.confirmDetach', { name: detachTarget.name }) }}</p>
                    <div class="flex gap-3">
                        <button @click="detachTarget = null" class="flex-1 py-2.5 border border-gray-200 text-sm font-semibold rounded-xl text-gray-600 hover:bg-gray-50 transition-colors">
                            {{ $t('common.cancel') }}
                        </button>
                        <button @click="confirmDetachFamily" :disabled="familyDetaching"
                            class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50">
                            {{ familyDetaching ? $t('common.loading') : $t('common.confirmDeleteBtn') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- === Агентство === -->
            <div v-if="caseData.agency" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('cases.agencyTitle') }}</h2>
                </div>
                <div class="p-5">
                    <div class="flex items-start gap-4 mb-4">
                        <!-- Логотип -->
                        <div class="w-14 h-14 rounded-xl border border-gray-100 flex items-center justify-center bg-gray-50 shrink-0 overflow-hidden">
                            <img v-if="caseData.agency.logo_url" :src="caseData.agency.logo_url" :alt="caseData.agency.name"
                                class="w-full h-full object-cover">
                            <span v-else class="text-xl font-bold text-gray-300">
                                {{ caseData.agency.name?.[0]?.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-[#0A1F44]">{{ caseData.agency.name }}</span>
                                <span v-if="caseData.agency.is_verified"
                                    class="flex items-center gap-1 text-xs text-[#1BA97F] bg-[#1BA97F]/10 px-2 py-0.5 rounded-full shrink-0">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $t('cases.verified') }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                                <span v-if="caseData.agency.city">{{ caseData.agency.city }}</span>
                                <span v-if="caseData.agency.experience_years">{{ caseData.agency.experience_years }} {{ $t('common.years') }}</span>
                                <span v-if="caseData.agency.rating" class="flex items-center gap-1">
                                    <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    {{ Number(caseData.agency.rating).toFixed(1) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <p v-if="caseData.agency.description" class="text-sm text-gray-500 leading-relaxed mb-4">
                        {{ caseData.agency.description }}
                    </p>

                    <!-- Контакты агентства -->
                    <div class="space-y-2">
                        <a v-if="caseData.agency.phone"
                            :href="`tel:${caseData.agency.phone}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">{{ $t('cases.phone') }}</div>
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ formatPhone(caseData.agency.phone) }}</div>
                            </div>
                        </a>

                        <a v-if="caseData.agency.email"
                            :href="`mailto:${caseData.agency.email}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">{{ $t('cases.email') }}</div>
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.agency.email }}</div>
                            </div>
                        </a>

                        <div v-if="caseData.agency.address"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-400">{{ $t('cases.address') }}</div>
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.agency.address }}</div>
                            </div>
                        </div>

                        <a v-if="caseData.agency.website_url"
                            :href="caseData.agency.website_url" target="_blank" rel="noopener"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs text-gray-400">{{ $t('cases.website') }}</div>
                                <div class="text-sm font-semibold text-[#0A1F44] truncate">{{ caseData.agency.website_url }}</div>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Менеджер уже отображается в header блоке -->

            <!-- === История заявки (Timeline) === -->
            <CaseTimeline v-if="caseData.id && caseData.public_status !== 'draft'"
              :case-id="caseData.id"
              :fetch-fn="publicPortalApi.caseActivities"
            />

            <!-- === Отзыв об агентстве === -->
            <div v-if="caseData.agency?.id && reviewState.loaded && !isTerminal"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                <!-- Форма -->
                <template v-if="reviewState.can_review">
                    <div class="px-5 py-4 border-b border-gray-50">
                        <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('cases.reviewTitle') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('cases.reviewHint') }}</p>
                    </div>
                    <div class="p-5 space-y-5">
                        <!-- 5 критериев -->
                        <div v-for="crit in REVIEW_CRITERIA" :key="crit.key"
                            class="flex items-center justify-between gap-3">
                            <span class="text-sm text-gray-700 min-w-0 leading-tight">{{ crit.label }}</span>
                            <div class="flex gap-0.5 shrink-0">
                                <button v-for="n in 5" :key="n"
                                    @click="reviewState.form[crit.key] = n"
                                    class="w-8 h-8 flex items-center justify-center transition-colors rounded-lg"
                                    :class="(reviewState.form[crit.key] ?? 0) >= n
                                        ? 'text-amber-400 bg-amber-50'
                                        : 'text-gray-200 bg-gray-50 hover:text-amber-300'">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <!-- Комментарий -->
                        <div>
                            <label class="text-xs text-gray-400 mb-1.5 block">{{ $t('cases.commentOptional') }}</label>
                            <textarea v-model="reviewState.form.comment"
                                :placeholder="$t('cases.commentPlaceholder')"
                                rows="3"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none transition-colors">
                            </textarea>
                        </div>
                        <!-- Кнопка -->
                        <button @click="submitCaseReview"
                            :disabled="!reviewAllFilled || reviewState.submitting"
                            class="w-full py-3 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="reviewState.submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ reviewState.submitting ? $t('cases.submittingReview') : $t('cases.publishReview') }}
                        </button>
                        <p v-if="!reviewAllFilled" class="text-xs text-gray-400 text-center">
                            {{ $t('cases.rateAll5') }}
                        </p>
                    </div>
                </template>

                <!-- Уже оставил отзыв -->
                <div v-else-if="reviewState.has_review" class="p-5 flex items-center gap-3">
                    <div class="w-10 h-10 bg-[#1BA97F]/10 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ $t('cases.reviewPublished') }}</div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('cases.reviewThanks') }}</p>
                    </div>
                </div>
            </div>

        </template>

        <!-- Модал подтверждения выбора агентства -->
        <div v-if="confirmAgency"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="confirmAgency = null">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl p-6">
                <h3 class="text-base font-bold text-[#0A1F44] mb-3">{{ $t('agencies.confirmTitle') }}</h3>
                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">{{ $t('agencies.confirmAgency') }}</span>
                        <span class="font-semibold text-[#0A1F44]">{{ confirmAgency.name }}</span>
                    </div>
                    <div v-if="confirmAgency.package" class="flex justify-between text-sm">
                        <span class="text-gray-400">{{ $t('agencies.confirmPrice') }}</span>
                        <span class="font-semibold text-[#0A1F44]">{{ confirmAgency.package.price }} {{ confirmAgency.package.currency }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-4">{{ $t('agencies.confirmDesc') }}</p>
                <div class="flex gap-3">
                    <button @click="confirmAgency = null" class="flex-1 py-2.5 border border-gray-200 text-sm font-semibold rounded-xl text-gray-600 hover:bg-gray-50 transition-colors">
                        {{ $t('common.cancel') }}
                    </button>
                    <button @click="submitAgencySelection" :disabled="agencySubmitting"
                        class="flex-1 py-2.5 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50">
                        {{ agencySubmitting ? $t('agencies.sending') : $t('agencies.send') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Модал подтверждения смены агентства -->
        <div v-if="showChangeAgencyModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="showChangeAgencyModal = false">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl p-6">
                <h3 class="text-base font-bold text-[#0A1F44] mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
                <p class="text-sm text-gray-500 mb-5">{{ $t('agencySelection.confirmChange') }}</p>
                <div class="flex gap-3">
                    <button @click="showChangeAgencyModal = false" class="flex-1 py-2.5 border border-gray-200 text-sm font-semibold rounded-xl text-gray-600 hover:bg-gray-50 transition-colors">
                        {{ $t('common.cancel') }}
                    </button>
                    <button @click="doChangeAgency" :disabled="changingAgency"
                        class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50">
                        {{ changingAgency ? $t('common.loading') : $t('common.yes') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Отмена заявки (только draft / awaiting_payment и не оплачено) -->
        <div v-if="caseData && canCancel" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div v-if="!showCancelConfirm" class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">{{ $t('cases.cancelHint') }}</p>
                </div>
                <button @click="showCancelConfirm = true"
                    class="px-4 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-xl hover:bg-red-50 transition-colors">
                    {{ $t('cases.cancelCase') }}
                </button>
            </div>
            <div v-else class="text-center space-y-3">
                <p class="text-sm font-semibold text-red-600">{{ $t('cases.cancelConfirmText') }}</p>
                <div class="flex gap-3 justify-center">
                    <button @click="showCancelConfirm = false"
                        class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                        {{ $t('common.back') }}
                    </button>
                    <button @click="doCancelCase" :disabled="cancelling"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors disabled:opacity-50">
                        {{ cancelling ? '...' : $t('cases.cancelConfirmBtn') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Ошибка / не найдено -->
        <div v-if="!loading && !caseData" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="text-3xl mb-3">404</div>
            <div class="font-semibold text-[#0A1F44]">{{ $t('cases.notFound') }}</div>
            <button @click="router.push({ name: 'me.cases' })"
                class="mt-4 px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] transition-colors">
                {{ $t('cases.backToList') }}
            </button>
        </div>

    </div>

    <!-- Модалка предпросмотра документа -->
    <teleport to="body">
        <transition name="fade">
            <div v-if="previewModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60" @click.self="previewModal.show = false">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-3 border-b border-gray-100">
                        <span class="text-sm font-bold text-[#0A1F44] truncate">{{ previewModal.name }}</span>
                        <button @click="previewModal.show = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto p-1 min-h-[300px] flex items-center justify-center bg-gray-50">
                        <div v-if="previewModal.loading" class="text-gray-400 text-sm">{{ $t('cases.loading') }}...</div>
                        <img v-else-if="previewModal.mime?.startsWith('image/')" :src="previewModal.url" class="max-w-full max-h-[75vh] object-contain rounded"/>
                        <iframe v-else-if="previewModal.mime === 'application/pdf'" :src="previewModal.url" class="w-full h-[75vh] border-0 rounded"/>
                        <div v-else class="text-center py-8">
                            <p class="text-sm text-gray-500 mb-3">{{ $t('cases.previewNotSupported') }}</p>
                            <a :href="previewModal.url" target="_blank" class="text-sm text-blue-600 hover:underline font-medium">{{ $t('cases.downloadFile') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { ref, computed, reactive, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag, ALL_COUNTRIES } from '@/utils/countries';
import AgencyCard from '@/components/AgencyCard.vue';
import CaseTimeline from '@/components/CaseTimeline.vue';
import i18n from '@/i18n';
import { formatPhone } from '@/utils/format';
import SearchSelect from '@/components/SearchSelect.vue';
import { usePublicAuthStore } from '@/stores/publicAuth';

const { t } = useI18n();
const route  = useRoute();
const router = useRouter();
const publicAuth = usePublicAuthStore();

const loading     = ref(true);
const caseData    = ref(null);
const uploading   = ref({});
const repeating   = ref({});
const deleting    = ref({});
const notAvailableLoading = ref({});
const previewModal = ref({ show: false, url: '', name: '', mime: '', loading: false });
const uploadToast = ref('');
const uploadToastError = ref(false);
const checkboxLoading = ref({});

// --- Отмена заявки ---
const showCancelConfirm = ref(false);
const cancelling = ref(false);
const isTerminal = computed(() => {
    const s = caseData.value?.public_status;
    return s === 'rejected' || s === 'cancelled';
});

const canCancel = computed(() => {
    const c = caseData.value;
    if (!c) return false;
    // Разрешаем отмену только для draft и awaiting_payment, и только если не оплачено
    if (!['draft', 'awaiting_payment'].includes(c.public_status)) return false;
    if (c.payment_status === 'paid') return false;
    return true;
});

async function doCancelCase() {
    cancelling.value = true;
    try {
        await publicPortalApi.cancelCase(caseData.value.id);
        router.push({ name: 'me.cases' });
    } catch (e) {
        alert(e?.response?.data?.message ?? t('cases.cancelError'));
    } finally {
        cancelling.value = false;
    }
}

// --- Inline-агентства ---
const inlineAgencies  = ref([]);
const agenciesLoading = ref(false);
const confirmAgency   = ref(null);
const agencySubmitting = ref(false);

// --- Оплата ---
const paymentLoading = ref(false);
const markingPaid = ref(false);
const paymentSection = ref(null);
const PAYMENT_PROVIDERS = [
    { id: 'click', label: 'Click', icon: 'C', bgClass: 'bg-blue-100 text-blue-600' },
    { id: 'payme', label: 'Payme', icon: 'P', bgClass: 'bg-cyan-100 text-cyan-600' },
    { id: 'uzum',  label: 'Uzum',  icon: 'U', bgClass: 'bg-purple-100 text-purple-600' },
];

// --- Смена агентства ---
const showChangeAgencyModal = ref(false);
const changingAgency = ref(false);

// --- Travel date ---
const savingTravelDate = ref(false);
const savingReturnDate = ref(false);
const todayStr = new Date().toISOString().slice(0, 10);

const returnDateMin = computed(() => {
    return caseData.value?.travel_date || todayStr;
});

const countryInfo = ref(null);

const tripDays = computed(() => {
    const d = caseData.value;
    if (!d?.travel_date || !d?.return_date) return 0;
    const ms = new Date(d.return_date) - new Date(d.travel_date);
    return Math.max(0, Math.round(ms / 86400000));
});

// Примерная стоимость поездки: виза + билеты (туда-обратно) + отель * дни + услуги агентства
const costBreakdown = computed(() => {
    const ci = countryInfo.value;
    const days = tripDays.value;
    if (!ci || days <= 0) return {};
    const visa    = Math.round(ci.visa_fee_usd || ci.evisa_fee_usd || 0);
    const flights = Math.round((ci.avg_flight_cost_usd || 0) * 2);
    const hotel   = Math.round((ci.avg_hotel_per_night_usd || 0) * days);
    // Услуги агентства
    const pkg = caseData.value?.package;
    const agencyPrice = pkg?.price || 0;
    const agencyCurrency = pkg?.currency || 'UZS';
    return {
        visa:    visa || 0,
        flights: flights || 0,
        hotel:   hotel || 0,
        agency:  agencyPrice,
        agencyFormatted: agencyPrice ? formatPrice(agencyPrice, agencyCurrency) : null,
    };
});

const estimatedCost = computed(() => {
    const b = costBreakdown.value;
    return (b.visa || 0) + (b.flights || 0) + (b.hotel || 0);
});

// Общая сумма с учётом агентства (конвертация UZS→USD ~12800)
const estimatedCostTotal = computed(() => {
    const base = estimatedCost.value;
    const pkg = caseData.value?.package;
    if (!pkg?.price) return base;
    const cur = pkg.currency || 'UZS';
    if (cur === 'USD') return base + pkg.price;
    // UZS → USD (приблизительно)
    return base + Math.round(pkg.price / 12800);
});

const tripDaysOverLimit = computed(() => {
    const max = caseData.value?.max_stay_days;
    return max && tripDays.value > max;
});

const tripDaysWarning = computed(() => {
    if (tripDaysOverLimit.value) {
        return t('cases.tripExceedsMax', { days: caseData.value.max_stay_days });
    }
    return '';
});

async function onTravelDateChange(e) {
    const val = e.target.value || null;
    savingTravelDate.value = true;
    try {
        const res = await publicPortalApi.updateCase(route.params.id, { travel_date: val });
        const d = res.data?.data ?? {};
        caseData.value.travel_date = d.travel_date ?? val;
        caseData.value.critical_date = d.critical_date ?? caseData.value.critical_date;
        if (d.deadline_info) caseData.value.deadline_info = d.deadline_info;
        // Если дата возврата раньше новой даты вылета — сбросить
        if (caseData.value.return_date && val && caseData.value.return_date < val) {
            await publicPortalApi.updateCase(route.params.id, { return_date: null });
            caseData.value.return_date = null;
        }
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        savingTravelDate.value = false;
    }
}

async function onReturnDateChange(e) {
    const val = e.target.value || null;
    savingReturnDate.value = true;
    try {
        await publicPortalApi.updateCase(route.params.id, { return_date: val });
        caseData.value.return_date = val;
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        savingReturnDate.value = false;
    }
}

// --- Семья ---
const showFamilyModal = ref(false);
const profileFamily = ref([]);
const caseFamilyMembers = ref([]);
const familyAttaching = ref(false);
const familySaving = ref(false);
const familyDetaching = ref(false);
const detachTarget = ref(null);
const newFamilyForm = ref({
    first_name: '', last_name: '', relationship: '', dob: '', gender: '', citizenship: '',
    passport_number: '', passport_expires_at: '',
});

const relationshipItems = computed(() => [
    { value: 'spouse',  label: t('family.rel.spouse') },
    { value: 'child',   label: t('family.rel.child') },
    { value: 'parent',  label: t('family.rel.parent') },
    { value: 'sibling', label: t('family.rel.sibling') },
    { value: 'other',   label: t('family.rel.other') },
]);

const genderItems = computed(() => [
    { value: 'M', label: t('family.male') },
    { value: 'F', label: t('family.female') },
]);

// --- Citizenship search ---
const citizenshipSearch = ref('');
const showCitizenshipDropdown = ref(false);

const filteredCountries = computed(() => {
    const q = citizenshipSearch.value.toLowerCase().trim();
    if (!q) return ALL_COUNTRIES.slice(0, 20);
    return ALL_COUNTRIES.filter(c =>
        c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q)
    ).slice(0, 15);
});

function selectCitizenship(country) {
    newFamilyForm.value.citizenship = country.code;
    citizenshipSearch.value = `${country.flag} ${country.name}`;
    showCitizenshipDropdown.value = false;
}

// --- Family passport split ---
const fmPassportSeries = ref('');
const fmPassportDigits = ref('');
const fmPassportNumberInput = ref(null);

const fmPassportValid = computed(() => /^[A-Z]{2}[0-9]{7}$/.test(newFamilyForm.value.passport_number || ''));

function handleFmSeriesInput(e) {
    const clean = e.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 2);
    fmPassportSeries.value = clean;
    newFamilyForm.value.passport_number = clean + fmPassportDigits.value;
    nextTick(() => { e.target.value = clean; });
    if (clean.length === 2) nextTick(() => fmPassportNumberInput.value?.focus());
}

function handleFmDigitsInput(e) {
    const clean = e.target.value.replace(/[^0-9]/g, '').slice(0, 7);
    fmPassportDigits.value = clean;
    newFamilyForm.value.passport_number = fmPassportSeries.value + clean;
    nextTick(() => { e.target.value = clean; });
}

// --- Document tabs ---
const activeDocTab = ref('all');

const applicantName = computed(() => publicAuth.user?.name || t('family.applicant'));

const docTabs = computed(() => {
    const mainItems = checklist.value;
    const mainUploaded = mainItems.filter(i => i.status === 'uploaded' || i.status === 'approved').length;

    const familyTabs = caseFamilyMembers.value.map(fm => {
        const items = fm.checklist ?? [];
        return {
            key: fm.id,
            label: fm.name,
            items,
            uploaded: items.filter(i => i.status === 'uploaded' || i.status === 'approved').length,
            total: items.length,
        };
    });

    const allItems = [...mainItems, ...familyTabs.flatMap(t => t.items)];
    const allUploaded = allItems.filter(i => i.status === 'uploaded' || i.status === 'approved').length;

    return [
        {
            key: 'all',
            label: t('common.all'),
            items: allItems,
            uploaded: allUploaded,
            total: allItems.length,
        },
        {
            key: 'main',
            label: applicantName.value,
            items: mainItems,
            uploaded: mainUploaded,
            total: mainItems.length,
        },
        ...familyTabs,
    ];
});

const activeTabChecklist = computed(() => {
    const tab = docTabs.value.find(t => t.key === activeDocTab.value);
    return tab?.items ?? [];
});

const activeTabUploaded = computed(() => {
    return activeTabChecklist.value.filter(i => i.status === 'uploaded' || i.status === 'approved').length;
});

const allDocsTotal = computed(() => docTabs.value.find(t => t.key === 'all')?.total ?? 0);
const allDocsUploaded = computed(() => docTabs.value.find(t => t.key === 'all')?.uploaded ?? 0);

function isFamilyAttached(fid) {
    return caseFamilyMembers.value.some(fm => fm.id === fid);
}

async function loadFamilyData() {
    try {
        const [profileRes, caseRes] = await Promise.all([
            publicPortalApi.familyMembers(),
            publicPortalApi.caseFamilyMembers(route.params.id),
        ]);
        profileFamily.value = profileRes.data.data ?? [];
        caseFamilyMembers.value = caseRes.data.data ?? [];
    } catch { /* ignore */ }
}

async function attachFamily(fid) {
    familyAttaching.value = true;
    try {
        await publicPortalApi.attachFamilyToCase(route.params.id, fid);
        await loadFamilyData();
        uploadToast.value = t('family.attached');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        familyAttaching.value = false;
    }
}

async function createAndAttachFamily() {
    familySaving.value = true;
    try {
        const form = { ...newFamilyForm.value };
        // Конкатенируем имя + фамилию
        form.name = `${form.first_name} ${form.last_name}`.trim();
        delete form.first_name;
        delete form.last_name;
        // Убираем пустые поля
        Object.keys(form).forEach(k => { if (!form[k]) delete form[k]; });
        const res = await publicPortalApi.addFamilyMember(form);
        const newId = res.data.data?.id;
        if (newId) {
            await publicPortalApi.attachFamilyToCase(route.params.id, newId);
        }
        // Перезагрузим весь case чтобы обновить checklist и семью
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
        caseFamilyMembers.value = data.data?.family_members ?? [];
        await loadFamilyData();
        newFamilyForm.value = { first_name: '', last_name: '', relationship: '', dob: '', gender: '', citizenship: '', passport_number: '', passport_expires_at: '' };
        fmPassportSeries.value = '';
        fmPassportDigits.value = '';
        citizenshipSearch.value = '';
        showFamilyModal.value = false;
        uploadToast.value = t('family.addedAndAttached');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        familySaving.value = false;
    }
}

function detachFamilyFromCase(fm) {
    detachTarget.value = fm;
}

async function confirmDetachFamily() {
    if (!detachTarget.value) return;
    familyDetaching.value = true;
    try {
        await publicPortalApi.detachFamilyFromCase(route.params.id, detachTarget.value.id);
        detachTarget.value = null;
        // Перезагрузим case чтобы обновить checklist и семью
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
        caseFamilyMembers.value = data.data?.family_members ?? [];
        await loadFamilyData();
        activeDocTab.value = 'all';
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        familyDetaching.value = false;
    }
}

// Критерии оценки агентства
const REVIEW_CRITERIA = computed(() => [
    { key: 'punctuality',     label: t('cases.punctuality') },
    { key: 'quality',         label: t('cases.quality') },
    { key: 'communication',   label: t('cases.communication') },
    { key: 'professionalism', label: t('cases.professionalism') },
    { key: 'price_quality',   label: t('cases.priceQuality') },
]);

const reviewState = reactive({
    loaded:      false,
    can_review:  false,
    has_review:  false,
    submitting:  false,
    form: {
        punctuality:     0,
        quality:         0,
        communication:   0,
        professionalism: 0,
        price_quality:   0,
        comment:         '',
    },
});

const reviewAllFilled = computed(() =>
    REVIEW_CRITERIA.value.every(c => (reviewState.form[c.key] ?? 0) > 0)
);

async function loadCanReview(agencyId) {
    try {
        const res = await publicPortalApi.canReview(agencyId);
        const d   = res.data.data;
        reviewState.can_review = d.can_review;
        reviewState.has_review = d.has_review;
    } catch { /* ignore */ } finally {
        reviewState.loaded = true;
    }
}

async function submitCaseReview() {
    if (!reviewAllFilled.value || reviewState.submitting) return;
    reviewState.submitting = true;
    try {
        await publicPortalApi.submitReview(caseData.value.agency.id, {
            punctuality:     reviewState.form.punctuality,
            quality:         reviewState.form.quality,
            communication:   reviewState.form.communication,
            professionalism: reviewState.form.professionalism,
            price_quality:   reviewState.form.price_quality,
            comment:         reviewState.form.comment || null,
            case_id:         route.params.id,
        });
        reviewState.can_review = false;
        reviewState.has_review = true;
        uploadToast.value = t('cases.reviewPublishedToast');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        alert(e?.response?.data?.message ?? t('cases.reviewError'));
    } finally {
        reviewState.submitting = false;
    }
}

function goChooseAgency() {
    router.push({
        name: 'me.agencies',
        query: {
            case_id: route.params.id,
            country_code: caseData.value.country_code,
            visa_type: caseData.value.visa_type,
        },
    });
}

async function loadInlineAgencies() {
    agenciesLoading.value = true;
    try {
        const res = await publicPortalApi.caseAgencies(route.params.id);
        inlineAgencies.value = res.data.data?.agencies ?? [];
    } catch { /* ignore */ } finally {
        agenciesLoading.value = false;
    }
}

function confirmSelectAgency(agency) {
    confirmAgency.value = agency;
}

async function submitAgencySelection() {
    if (!confirmAgency.value || agencySubmitting.value) return;
    agencySubmitting.value = true;
    try {
        await publicPortalApi.submitLead({
            agency_id:    confirmAgency.value.id,
            country_code: caseData.value.country_code,
            visa_type:    caseData.value.visa_type,
            package_id:   confirmAgency.value.package?.id ?? null,
            case_id:      route.params.id,
        });
        confirmAgency.value = null;
        // Перезагрузить данные кейса
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
        uploadToast.value = t('agencies.sent');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        // 409 = дубликат — уже есть заявка в это агентство, перенаправляем
        if (e?.response?.status === 409 && e.response.data?.case_id) {
            router.push({ name: 'me.cases.show', params: { id: e.response.data.case_id } });
            return;
        }
        alert(e?.response?.data?.message ?? t('agencies.sendError'));
    } finally {
        agencySubmitting.value = false;
    }
}

async function initiatePayment(provider) {
    paymentLoading.value = true;
    try {
        const res = await publicPortalApi.initiatePayment({
            case_id:  route.params.id,
            provider,
        });
        const url = res.data.data?.payment_url;
        if (url && url !== '#') {
            window.open(url, '_blank');
        }
        // Обновить статус
        caseData.value.payment_status = 'pending';
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        paymentLoading.value = false;
    }
}

function scrollToPayment() {
    paymentSection.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

async function testMarkAsPaid() {
    markingPaid.value = true;
    try {
        await publicPortalApi.markAsPaid({ case_id: route.params.id });
        // Перезагрузить данные кейса
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
        uploadToast.value = t('payment.markedAsPaid');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        markingPaid.value = false;
    }
}

const invoiceNumber = computed(() => {
    const cn = caseData.value?.case_number;
    if (cn) return 'INV-' + cn;
    const id = caseData.value?.id;
    return id ? 'INV-' + id.slice(0, 8).toUpperCase() : 'INV-000';
});

const invoiceHoursLeft = computed(() => {
    const exp = caseData.value?.payment_expires_at;
    if (!exp) return 999;
    return Math.max(0, (new Date(exp) - new Date()) / (1000 * 60 * 60));
});

const invoiceExpiresFormatted = computed(() => {
    const h = invoiceHoursLeft.value;
    if (h <= 0) return t('billing.expired');
    const days = Math.floor(h / 24);
    const hours = Math.floor(h % 24);
    if (days > 0) return t('billing.daysAndHours', { days, hours });
    if (hours > 0) return t('billing.hoursLeft', { hours });
    return t('billing.lessThanHour');
});

const invoiceTotalAmount = computed(() => {
    return caseData.value?.payment_amount ?? caseData.value?.package?.price ?? 0;
});
const invoiceCurrency = computed(() => {
    return caseData.value?.payment_currency ?? caseData.value?.package?.currency ?? 'USD';
});

function invoiceRoleLabel(role) {
    const map = {
        applicant: t('billing.applicant'),
        child: t('billing.roleChild'),
        spouse: t('billing.roleSpouse'),
        parent: t('billing.roleParent'),
        sibling: t('billing.roleSibling'),
        other: t('billing.familyMember'),
    };
    return map[role] || t('billing.familyMember');
}

function formatPrice(amount, currency) {
    if (!amount && amount !== 0) return '';
    const cur = currency || 'USD';
    if (cur === 'UZS') return amount.toLocaleString('ru-RU') + ' UZS';
    if (cur === 'USD') return '$' + amount.toLocaleString('ru-RU');
    return amount.toLocaleString('ru-RU') + ' ' + cur;
}

async function doChangeAgency() {
    changingAgency.value = true;
    try {
        await publicPortalApi.changeAgency(route.params.id);
        showChangeAgencyModal.value = false;
        // Перезагрузить данные кейса
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
        // Загрузить агентства для inline-выбора
        loadInlineAgencies();
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    } finally {
        changingAgency.value = false;
    }
}

const MAX_FILE_SIZE_MB = 20;

async function uploadDoc(itemId, event) {
    const file = event.target.files[0];
    if (!file) return;

    // Клиентская валидация размера файла
    if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
        uploadToast.value = t('cases.fileTooLarge', { max: MAX_FILE_SIZE_MB });
        uploadToastError.value = true;
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
        event.target.value = '';
        return;
    }

    uploading.value[itemId] = true;
    try {
        const fd = new FormData();
        fd.append('file', file);
        await publicPortalApi.uploadChecklistItem(route.params.id, itemId, fd);
        // Обновляем статус локально
        const item = caseData.value?.checklist?.find(i => i.id === itemId)
            || caseData.value?.family_members?.flatMap(fm => fm.checklist || []).find(i => i.id === itemId);
        if (item) {
            item.status = 'uploaded';
            item.file_name = file.name;
        }
        uploadToastError.value = false;
        uploadToast.value = t('cases.docUploaded');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        const msg = e?.response?.data?.message ?? t('cases.uploadError');
        uploadToastError.value = true;
        uploadToast.value = msg;
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
    } finally {
        uploading.value[itemId] = false;
        event.target.value = '';
    }
}

async function toggleCheckbox(item) {
    checkboxLoading.value[item.id] = true;
    try {
        const newChecked = !item.is_checked;
        await publicPortalApi.checkChecklistItem(route.params.id, item.id, newChecked);
        item.is_checked = newChecked;
        item.status = newChecked ? 'uploaded' : 'pending';
        uploadToastError.value = false;
        uploadToast.value = newChecked ? t('cases.checkboxChecked') : '';
        if (uploadToast.value) setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        uploadToastError.value = true;
        uploadToast.value = e?.response?.data?.message ?? t('cases.uploadError');
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
    } finally {
        checkboxLoading.value[item.id] = false;
    }
}

async function repeatSlot(item) {
    repeating.value[item.id] = true;
    try {
        const { data } = await publicPortalApi.repeatChecklistItem(route.params.id, item.id);
        const newItem = data.data;
        // Добавим в чеклист после текущего элемента
        const list = caseData.value?.checklist;
        if (list) {
            const idx = list.findIndex(i => i.id === item.id);
            list.splice(idx + 1, 0, newItem);
        }
        uploadToastError.value = false;
        uploadToast.value = t('cases.slotAdded');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        uploadToastError.value = true;
        uploadToast.value = e?.response?.data?.message ?? t('cases.uploadError');
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
    } finally {
        repeating.value[item.id] = false;
    }
}

async function deleteSlot(item) {
    deleting.value[item.id] = true;
    try {
        await publicPortalApi.deleteChecklistItem(route.params.id, item.id);
        const list = caseData.value?.checklist;
        if (list) {
            const idx = list.findIndex(i => i.id === item.id);
            if (idx !== -1) list.splice(idx, 1);
        }
        // Также проверяем family members
        caseData.value?.family_members?.forEach(fm => {
            const idx = fm.checklist?.findIndex(i => i.id === item.id);
            if (idx !== undefined && idx !== -1) fm.checklist.splice(idx, 1);
        });
        uploadToastError.value = false;
        uploadToast.value = t('cases.slotDeleted');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        uploadToastError.value = true;
        uploadToast.value = e?.response?.data?.message ?? t('cases.uploadError');
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
    } finally {
        deleting.value[item.id] = false;
    }
}

async function openPreview(item) {
    previewModal.value = { show: true, url: '', name: item.file_name || item.name, mime: '', loading: true };
    try {
        const { data } = await publicPortalApi.previewDocument(item.document_id);
        const d = data.data;
        previewModal.value.url = d.url;
        previewModal.value.mime = d.mime_type;
        previewModal.value.name = d.original_name || item.name;
    } catch {
        previewModal.value.url = '';
        previewModal.value.mime = '';
    } finally {
        previewModal.value.loading = false;
    }
}

async function markNotAvailable(item) {
    notAvailableLoading.value[item.id] = true;
    try {
        await publicPortalApi.checkChecklistItem(route.params.id, item.id, false, 'not_available');
        item.status = 'not_available';
        uploadToastError.value = false;
        uploadToast.value = t('cases.markedNotAvailable');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        uploadToastError.value = true;
        uploadToast.value = e?.response?.data?.message ?? t('cases.uploadError');
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
    } finally {
        notAvailableLoading.value[item.id] = false;
    }
}

async function undoNotAvailable(item) {
    notAvailableLoading.value[item.id] = true;
    try {
        await publicPortalApi.checkChecklistItem(route.params.id, item.id, false, 'pending');
        item.status = 'pending';
        uploadToastError.value = false;
        uploadToast.value = t('cases.undoneNotAvailable');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        uploadToastError.value = true;
        uploadToast.value = e?.response?.data?.message ?? t('cases.uploadError');
        setTimeout(() => { uploadToast.value = ''; uploadToastError.value = false; }, 4000);
    } finally {
        notAvailableLoading.value[item.id] = false;
    }
}

const PUBLIC_STATUSES = [
    { key: 'draft',                order: 0 },
    { key: 'awaiting_payment',     order: 1 },
    { key: 'submitted',            order: 2 },
    { key: 'manager_assigned',     order: 3 },
    { key: 'document_collection',  order: 4 },
    { key: 'document_review',      order: 5 },
    { key: 'translation',          order: 6 },
    { key: 'ready_for_submission', order: 7 },
    { key: 'under_review',         order: 8 },
    { key: 'completed',            order: 9 },
    { key: 'rejected',             order: 10 },
];

function getVisibleStatuses(c) {
    const base = PUBLIC_STATUSES.slice(0, 9); // draft..under_review
    if (c?.public_status === 'rejected') {
        return [...base, { key: 'rejected', order: 9 }];
    }
    return [...base, { key: 'completed', order: 9 }];
}

const currentStepIdx = computed(() => {
    if (!caseData.value) return 0;
    const s = caseData.value.public_status;
    if (s === 'completed' || s === 'rejected' || s === 'cancelled') return 9;
    return caseData.value.public_status_order ?? 0;
});

const VISA_TYPE_LABELS = computed(() => ({
    tourist: t('portal.touristVisa'), business: t('portal.businessVisa'),
    student: t('portal.studentVisaFull'), work: t('portal.workVisa'),
    transit: t('portal.transitVisa'), immigrant: t('portal.immigrantVisa'),
}));

const checklist = computed(() => caseData.value?.checklist ?? []);
const docsUploaded = computed(() =>
    checklist.value.filter(i => i.status === 'uploaded' || i.status === 'approved').length
);

function countryName(code) { return t(`countries.${code}`) !== `countries.${code}` ? t(`countries.${code}`) : code; }
function codeToFlagLocal(code){ return codeToFlag(code); }
function visaTypeLabel(type)  { return VISA_TYPE_LABELS.value[type] || type; }

function publicStatusBadge(status) {
    const map = {
        draft:                 'bg-gray-100 text-gray-600',
        awaiting_payment:      'bg-amber-50 text-amber-700',
        submitted:             'bg-blue-50 text-blue-600',
        manager_assigned:      'bg-indigo-50 text-indigo-700',
        document_collection:   'bg-amber-50 text-amber-700',
        document_review:       'bg-yellow-50 text-yellow-700',
        translation:           'bg-cyan-50 text-cyan-700',
        ready_for_submission:  'bg-orange-50 text-orange-700',
        under_review:          'bg-purple-50 text-purple-700',
        completed:             'bg-green-50 text-green-700',
        rejected:              'bg-red-50 text-red-700',
        cancelled:             'bg-gray-100 text-gray-500',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

function statusTextColor(status) {
    if (status === 'completed') return 'text-[#1BA97F]';
    if (status === 'rejected')  return 'text-red-500';
    if (status === 'cancelled') return 'text-gray-400';
    return 'text-gray-500';
}

function getProgressColor(index, status, order) {
    if (status === 'rejected') {
        return index < order ? 'bg-red-300' : index === order ? 'bg-red-500' : 'bg-gray-100';
    }
    if (status === 'cancelled') return 'bg-gray-200';
    if (status === 'completed') return 'bg-[#1BA97F]';
    return index < order ? 'bg-[#1BA97F]' : index === order ? 'bg-[#1BA97F]/50' : 'bg-gray-100';
}

function statusLabel(status, item) {
    if (status === 'approved') return t('cases.approved');
    if (status === 'uploaded') return item?.type === 'checkbox' ? t('cases.checkboxChecked') : t('cases.uploadedReview');
    if (status === 'not_available') return t('cases.notAvailable');
    if (status === 'rejected') return t('cases.rejectedByAgency');
    return item?.type === 'checkbox' ? t('cases.checkboxMark') : t('cases.needUpload');
}

function deadlineClass(dateStr) {
    if (!dateStr) return 'text-gray-600';
    const days = Math.floor((new Date(dateStr) - new Date()) / 86400000);
    if (days < 0)  return 'text-red-600';
    if (days <= 5) return 'text-amber-600';
    return 'text-[#0A1F44]';
}

function deadlineUrgent(dateStr) {
    if (!dateStr) return false;
    return Math.floor((new Date(dateStr) - new Date()) / 86400000) < 5;
}

function deadlineDaysText(dateStr) {
    if (!dateStr) return '';
    const days = Math.floor((new Date(dateStr) - new Date()) / 86400000);
    if (days < 0) return t('cases.deadlineOverdue', { days: Math.abs(days) });
    if (days === 0) return t('cases.deadlineToday');
    return t('cases.deadlineDaysLeft', { days });
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    return new Date(dateStr).toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
}

onMounted(async () => {
    try {
        const { data } = await publicPortalApi.caseDetail(route.params.id);
        caseData.value = data.data;
        caseFamilyMembers.value = data.data?.family_members ?? [];
        // Загружаем статус отзыва для агентства
        if (caseData.value?.agency?.id) {
            await loadCanReview(caseData.value.agency.id);
        }
        // Загружаем inline-агентства для draft без агентства
        if (caseData.value?.public_status === 'draft' && !caseData.value?.agency) {
            loadInlineAgencies();
        }
        // Загружаем данные страны для расчёта примерной стоимости
        if (caseData.value?.country_code) {
            publicPortalApi.countryDetail(caseData.value.country_code)
                .then(r => { countryInfo.value = r.data.data ?? null; })
                .catch(e => console.error('[PublicCaseDetail] countryDetail', e));
        }
        // Загружаем семью из профиля (для модала)
        publicPortalApi.familyMembers().then(r => { profileFamily.value = r.data.data ?? []; }).catch(e => console.error('[PublicCaseDetail] familyMembers', e));
    } catch {
        caseData.value = null;
    } finally {
        loading.value = false;
    }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s, transform 0.25s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(8px); }
</style>

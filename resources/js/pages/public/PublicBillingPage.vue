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
                <div class="px-5 py-3 flex items-center justify-between cursor-pointer"
                    :class="p.status === 'succeeded' ? 'bg-[#1BA97F]/5' : p.status === 'pending' ? 'bg-amber-50' : 'bg-gray-50'"
                    @click="p.status !== 'pending' && toggleExpand(p.id)">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 shrink-0" :class="p.status === 'succeeded' ? 'text-[#1BA97F]' : p.status === 'pending' ? 'text-amber-500' : 'text-gray-400'"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="text-xs font-semibold text-[#0A1F44]">
                            {{ $t('billing.invoiceNum', { num: invoiceNum(p) }) }}
                        </span>
                        <!-- Сумма в шапке для свёрнутых -->
                        <span v-if="p.status !== 'pending'" class="text-xs font-bold text-[#0A1F44] ml-1">
                            {{ formatPrice(p.amount, p.currency) }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Таймер аннулирования в шапке -->
                        <div v-if="p.status === 'pending' && p.expires_at"
                            class="flex items-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-full"
                            :class="hoursLeft(p.expires_at) <= 24 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500'">
                            <svg class="w-3 h-3" :class="hoursLeft(p.expires_at) <= 24 ? 'animate-pulse' : ''"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ expiresIn(p.expires_at) }}
                        </div>
                        <!-- Дата оплаты для succeeded -->
                        <span v-if="p.status === 'succeeded' && p.paid_at" class="text-[10px] text-gray-400">
                            {{ formatDateTime(p.paid_at) }}
                        </span>
                        <router-link v-if="p.case_id"
                            :to="{ name: 'me.cases.show', params: { id: p.case_id } }"
                            class="text-[10px] font-medium underline underline-offset-2 text-gray-400 hover:text-[#0A1F44] transition-colors"
                            @click.stop>
                            {{ $t('billing.viewCase') }}
                        </router-link>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
                            :class="paymentStatusBadge(p.status)">
                            {{ paymentStatusLabel(p.status) }}
                        </span>
                        <!-- Стрелка раскрытия для не-pending -->
                        <svg v-if="p.status !== 'pending'"
                            class="w-4 h-4 text-gray-400 transition-transform duration-200"
                            :class="expandedIds.has(p.id) ? 'rotate-180' : ''"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>

                <!-- Для expired — компактная строка с кнопкой -->
                <div v-if="p.status === 'expired' && !expandedIds.has(p.id)" class="px-5 py-3 flex items-center justify-between border-t border-gray-100">
                    <div class="text-xs text-gray-400">{{ $t('billing.expiredHint') }}</div>
                    <router-link v-if="p.case_id"
                        :to="{ name: 'me.cases.show', params: { id: p.case_id } }"
                        class="shrink-0 px-4 py-1.5 rounded-lg text-xs font-bold bg-[#1BA97F] hover:bg-[#158a68] text-white transition-colors">
                        {{ $t('billing.payAgain') }}
                    </router-link>
                </div>

                <!-- Контент: всегда видим для pending, раскрываемый для остальных -->
                <div v-if="p.status === 'pending' || expandedIds.has(p.id)" class="p-5 space-y-4">
                    <!-- Заявитель + Исполнитель + дата -->
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

                    <!-- Участники -->
                    <div v-if="p.applicant_name" class="bg-gray-50 rounded-xl p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('billing.participants') }}</div>
                            <div v-if="p.total_persons > 1" class="text-[10px] font-bold text-[#0A1F44] bg-white px-2 py-0.5 rounded-full">
                                {{ p.total_persons }} {{ $t('billing.persons') }}
                            </div>
                        </div>
                        <!-- Заявитель -->
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-[#0A1F44] rounded-lg flex items-center justify-center shrink-0">
                                <span class="text-white text-xs font-bold">{{ p.applicant_name.charAt(0) }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-[#0A1F44] truncate">{{ p.applicant_name }}</div>
                                <div class="text-[10px] text-gray-400">{{ $t('billing.applicant') }}</div>
                            </div>
                        </div>
                        <!-- Члены семьи -->
                        <div v-for="(fm, fi) in (p.family_members || [])" :key="'fm'+fi" class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-[#1BA97F]/20 rounded-lg flex items-center justify-center shrink-0">
                                <span class="text-[#1BA97F] text-xs font-bold">{{ fm.name?.charAt(0) || '?' }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-[#0A1F44] truncate">{{ fm.name }}</div>
                                <div class="text-[10px] text-gray-400">{{ $t('billing.familyMember') }}</div>
                            </div>
                        </div>
                        <!-- Члены группы -->
                        <div v-for="(gm, gi) in (p.group_members || [])" :key="'gm'+gi" class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                <span class="text-blue-600 text-xs font-bold">{{ gm.name?.charAt(0) || '?' }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-[#0A1F44] truncate">{{ gm.name }}</div>
                                <div class="text-[10px] text-gray-400">{{ $t('billing.group') }}</div>
                            </div>
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

                    <!-- ИТОГО к оплате — только для pending -->
                    <div v-if="p.status === 'pending'" class="flex items-center justify-between p-4 rounded-xl bg-[#1BA97F] text-white">
                        <span class="text-sm font-semibold">{{ $t('payment.total') }}</span>
                        <span class="text-xl font-bold">{{ formatPrice(p.amount, p.currency) }}</span>
                    </div>

                    <!-- Обратный отсчёт: крайний срок -->
                    <div v-if="p.status === 'pending' && p.critical_date"
                        class="rounded-xl overflow-hidden"
                        :class="daysLeft(p.critical_date) <= 0
                            ? 'bg-red-50 border-2 border-red-300'
                            : daysLeft(p.critical_date) <= 7
                                ? 'bg-red-50 border border-red-200'
                                : daysLeft(p.critical_date) <= 21
                                    ? 'bg-amber-50 border border-amber-200'
                                    : 'bg-orange-50 border border-orange-200'">
                        <div class="px-4 py-3 flex items-start gap-3">
                            <div class="shrink-0 mt-0.5">
                                <svg class="w-5 h-5 animate-pulse" :class="daysLeft(p.critical_date) <= 7 ? 'text-red-500' : daysLeft(p.critical_date) <= 21 ? 'text-amber-500' : 'text-orange-500'"
                                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold uppercase tracking-wider"
                                    :class="daysLeft(p.critical_date) <= 7 ? 'text-red-600' : daysLeft(p.critical_date) <= 21 ? 'text-amber-600' : 'text-orange-600'">
                                    {{ daysLeft(p.critical_date) <= 0 ? $t('billing.overdue') : $t('billing.urgentTitle') }}
                                </div>
                                <div class="text-sm font-semibold mt-1"
                                    :class="daysLeft(p.critical_date) <= 7 ? 'text-red-800' : daysLeft(p.critical_date) <= 21 ? 'text-amber-800' : 'text-orange-800'">
                                    {{ daysLeft(p.critical_date) <= 0
                                        ? $t('billing.overdueWarning', { date: formatDeadline(p.critical_date) })
                                        : $t('billing.deadlineWarning', { days: daysLeft(p.critical_date), date: formatDeadline(p.critical_date) })
                                    }}
                                </div>
                                <div class="text-xs mt-1"
                                    :class="daysLeft(p.critical_date) <= 7 ? 'text-red-600' : daysLeft(p.critical_date) <= 21 ? 'text-amber-600' : 'text-orange-600'">
                                    {{ $t('billing.payToStart') }}
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-2xl font-black"
                                    :class="daysLeft(p.critical_date) <= 7 ? 'text-red-600' : daysLeft(p.critical_date) <= 21 ? 'text-amber-600' : 'text-orange-600'">
                                    {{ Math.max(0, daysLeft(p.critical_date)) }}
                                </div>
                                <div class="text-[10px] font-bold uppercase"
                                    :class="daysLeft(p.critical_date) <= 7 ? 'text-red-400' : daysLeft(p.critical_date) <= 21 ? 'text-amber-400' : 'text-orange-400'">
                                    {{ $t('billing.daysShort') }}
                                </div>
                            </div>
                        </div>
                        <!-- Прогресс-бар времени -->
                        <div class="px-4 pb-3">
                            <div class="h-1.5 bg-white/60 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="daysLeft(p.critical_date) <= 7 ? 'bg-red-500' : daysLeft(p.critical_date) <= 21 ? 'bg-amber-500' : 'bg-orange-400'"
                                    :style="{ width: Math.max(5, 100 - (daysLeft(p.critical_date) / 90 * 100)) + '%' }">
                                </div>
                            </div>
                        </div>
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

                    <!-- Детали оплаченного счёта (в раскрытом виде) -->
                    <div v-if="p.status === 'succeeded'" class="flex items-center gap-3 p-3 bg-[#1BA97F]/5 rounded-xl">
                        <svg class="w-5 h-5 text-[#1BA97F] shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-[#1BA97F]">{{ $t('billing.statusPaid') }}</div>
                            <div class="text-xs text-gray-400">
                                {{ formatDateTime(p.paid_at) }}
                                <span v-if="p.provider"> — {{ $t('billing.paidVia') }} {{ providerLabel(p.provider) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Детали аннулированного счёта (в раскрытом виде) -->
                    <div v-if="p.status === 'expired'" class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <div class="flex-1">
                            <div class="text-sm font-semibold text-gray-500">{{ $t('billing.statusExpired') }}</div>
                            <div class="text-xs text-gray-400">{{ $t('billing.expiredHint') }}</div>
                        </div>
                        <router-link v-if="p.case_id"
                            :to="{ name: 'me.cases.show', params: { id: p.case_id } }"
                            class="shrink-0 px-4 py-1.5 rounded-lg text-xs font-bold bg-[#1BA97F] hover:bg-[#158a68] text-white transition-colors">
                            {{ $t('billing.payAgain') }}
                        </router-link>
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
const expandedIds = ref(new Set());

const PAYMENT_PROVIDERS = [
    { id: 'click', label: 'Click', icon: 'C', bgClass: 'bg-blue-100 text-blue-600' },
    { id: 'payme', label: 'Payme', icon: 'P', bgClass: 'bg-cyan-100 text-cyan-600' },
    { id: 'uzum',  label: 'Uzum',  icon: 'U', bgClass: 'bg-purple-100 text-purple-600' },
];

const paidCount = computed(() => payments.value.filter(p => p.status === 'succeeded').length);
const unpaidCount = computed(() => payments.value.filter(p => p.status === 'pending').length);
const expiredCount = computed(() => payments.value.filter(p => p.status === 'expired').length);

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
        expired:   'bg-gray-200 text-gray-500',
        refunded:  'bg-gray-100 text-gray-500',
    }[status] || 'bg-gray-100 text-gray-500';
}

function paymentStatusLabel(status) {
    return {
        pending: t('billing.statusPending'),
        succeeded: t('billing.statusPaid'),
        failed: t('billing.statusFailed'),
        expired: t('billing.statusExpired'),
        refunded: t('billing.statusRefunded'),
    }[status] || status;
}

function formatDateTime(dateStr) {
    if (!dateStr) return '';
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    return new Date(dateStr).toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
}

function hoursLeft(dateStr) {
    if (!dateStr) return 999;
    const now = new Date();
    const target = new Date(dateStr);
    return Math.max(0, (target - now) / (1000 * 60 * 60));
}

function expiresIn(dateStr) {
    const h = hoursLeft(dateStr);
    if (h <= 0) return t('billing.expired');
    const days = Math.floor(h / 24);
    const hours = Math.floor(h % 24);
    if (days > 0) return t('billing.daysAndHours', { days, hours });
    if (hours > 0) return t('billing.hoursLeft', { hours });
    return t('billing.lessThanHour');
}

function daysLeft(dateStr) {
    if (!dateStr) return -1;
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    const target = new Date(dateStr);
    target.setHours(0, 0, 0, 0);
    return Math.ceil((target - now) / (1000 * 60 * 60 * 24));
}

function formatDeadline(dateStr) {
    if (!dateStr) return '';
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    return new Date(dateStr).toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
}

function toggleExpand(id) {
    const s = new Set(expandedIds.value);
    if (s.has(id)) s.delete(id); else s.add(id);
    expandedIds.value = s;
}

function providerLabel(provider) {
    const map = { click: 'Click', payme: 'Payme', uzum: 'Uzum' };
    return map[provider] || provider || '—';
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

<template>
    <div class="space-y-4">

        <!-- Toast уведомление о загрузке -->
        <transition name="fade">
            <div v-if="uploadToast"
                class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50
                       bg-[#1BA97F] text-white text-sm font-semibold
                       px-5 py-3 rounded-2xl shadow-lg shadow-[#1BA97F]/30
                       flex items-center gap-2 pointer-events-none">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
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

            <!-- === Заголовок заявки === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ codeToFlag(caseData.country_code) }}</span>
                        <div>
                            <h1 class="text-lg font-bold text-[#0A1F44] leading-tight">
                                {{ countryName(caseData.country_code) }}
                            </h1>
                            <div class="text-sm text-gray-400 mt-0.5">{{ visaTypeLabel(caseData.visa_type) }}</div>
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full"
                        :class="publicStatusBadge(caseData.public_status)"
                        :title="caseData.public_status_tooltip">
                        {{ caseData.public_status_label }}
                    </span>
                </div>

                <!-- Прогресс по public_status (8 шагов) -->
                <div class="mb-3">
                    <div class="flex items-center gap-0.5 mb-2">
                        <div v-for="(s, i) in PUBLIC_STATUSES" :key="s.key"
                            class="flex-1 h-2 rounded-full transition-colors"
                            :class="getProgressColor(i, caseData.public_status, caseData.public_status_order)">
                        </div>
                    </div>
                    <p class="text-sm font-medium" :class="statusTextColor(caseData.public_status)">
                        {{ caseData.public_status_tooltip || caseData.public_status_label }}
                    </p>
                </div>

                <!-- Даты -->
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div v-if="caseData.critical_date" class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">{{ $t('cases.deadline') }}</div>
                        <div class="text-sm font-semibold" :class="deadlineClass(caseData.critical_date)">
                            {{ formatDate(caseData.critical_date) }}
                        </div>
                    </div>
                    <div v-if="caseData.travel_date" class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">{{ $t('cases.travelDate') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ formatDate(caseData.travel_date) }}</div>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50">
                        <div class="text-xs text-gray-400 mb-0.5">{{ $t('cases.created') }}</div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ formatDate(caseData.created_at) }}</div>
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

            <!-- === Оплата услуги (submitted, unpaid) === -->
            <div v-if="caseData.agency && caseData.public_status !== 'draft' && (caseData.payment_status === 'unpaid' || caseData.payment_status === 'pending')"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('payment.title') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('payment.selectProvider') }}</p>
                </div>
                <div class="p-5">
                    <div v-if="caseData.payment_status === 'pending'" class="flex items-center gap-3 p-4 bg-amber-50 rounded-xl">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 animate-pulse" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm font-medium text-amber-700">{{ $t('payment.pending') }}</span>
                    </div>
                    <div v-else class="grid grid-cols-3 gap-3">
                        <button v-for="p in PAYMENT_PROVIDERS" :key="p.id"
                            @click="initiatePayment(p.id)"
                            :disabled="paymentLoading"
                            class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-gray-100 hover:border-[#1BA97F] transition-colors disabled:opacity-50">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center text-2xl font-bold"
                                :class="p.bgClass">
                                {{ p.icon }}
                            </div>
                            <span class="text-xs font-semibold text-[#0A1F44]">{{ p.label }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- === Оплачено === -->
            <div v-if="caseData.payment_status === 'paid'"
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

            <!-- === Сменить агентство (до оплаты) === -->
            <div v-if="caseData.agency && caseData.payment_status !== 'paid' && caseData.public_status !== 'draft'"
                class="flex justify-end">
                <button @click="showChangeAgencyModal = true"
                    class="text-xs text-gray-400 hover:text-red-500 transition-colors underline underline-offset-2">
                    {{ $t('agencySelection.changeAgency') }}
                </button>
            </div>

            <!-- === Чек-лист документов === -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('cases.documentsTitle') }}</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('cases.documentsHint') }}</p>
                    </div>
                    <div v-if="checklist.length" class="text-right">
                        <div class="text-lg font-bold"
                            :class="docsUploaded >= checklist.length ? 'text-[#1BA97F]' : 'text-[#0A1F44]'">
                            {{ docsUploaded }}/{{ checklist.length }}
                        </div>
                        <div class="text-xs text-gray-400">{{ $t('cases.uploaded') }}</div>
                    </div>
                </div>

                <!-- Прогресс документов -->
                <div v-if="checklist.length" class="px-5 pt-3 pb-1">
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                            :class="docsUploaded >= checklist.length ? 'bg-[#1BA97F]' : 'bg-amber-400'"
                            :style="{ width: (docsUploaded / checklist.length * 100) + '%' }">
                        </div>
                    </div>
                </div>

                <!-- Список документов -->
                <div v-if="checklist.length" class="divide-y divide-gray-50 mt-1">
                    <div v-for="item in checklist" :key="item.id"
                        class="px-5 py-3.5 flex items-start gap-3">
                        <!-- Статус иконка -->
                        <div class="mt-0.5 shrink-0 w-5 h-5 rounded-full flex items-center justify-center"
                            :class="{
                                'bg-[#1BA97F]':   item.status === 'approved',
                                'bg-blue-100':    item.status === 'uploaded',
                                'bg-amber-100':   item.status === 'pending' && item.is_required,
                                'bg-gray-100':    item.status === 'pending' && !item.is_required,
                            }">
                            <!-- Одобрено -->
                            <svg v-if="item.status === 'approved'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            <!-- Загружено, на проверке -->
                            <svg v-else-if="item.status === 'uploaded'" class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <!-- Ожидает -->
                            <svg v-else class="w-3 h-3" :class="item.is_required ? 'text-amber-600' : 'text-gray-400'"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium text-[#0A1F44]">{{ item.name }}</span>
                                <span v-if="item.is_required"
                                    class="text-[10px] font-semibold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full shrink-0">
                                    {{ $t('common.required') }}
                                </span>
                            </div>
                            <p v-if="item.description" class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ item.description }}</p>
                            <p v-if="item.notes" class="text-xs text-blue-600 mt-0.5">{{ item.notes }}</p>

                            <!-- Статус текст -->
                            <div class="mt-1 text-xs font-medium"
                                :class="{
                                    'text-[#1BA97F]': item.status === 'approved',
                                    'text-blue-600':  item.status === 'uploaded',
                                    'text-amber-600': item.status === 'pending' && item.is_required,
                                    'text-gray-400':  item.status === 'pending' && !item.is_required,
                                }">
                                {{ statusLabel(item.status, item.is_required) }}
                            </div>

                            <!-- Кнопка загрузки: только для responsibility=client и не approved -->
                            <label v-if="item.responsibility !== 'agency' && item.status !== 'approved'"
                                class="inline-flex items-center gap-1.5 text-xs text-[#1BA97F] font-medium
                                       cursor-pointer hover:text-[#169B72] mt-1.5 select-none"
                                :class="{ 'opacity-50 pointer-events-none': uploading[item.id] }">
                                <svg v-if="!uploading[item.id]" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                            <!-- Для agency responsibility — только статус -->
                            <div v-else-if="item.responsibility === 'agency' && item.status !== 'approved'"
                                class="mt-1 text-xs text-gray-400 italic">
                                {{ item.status === 'done' ? $t('cases.doneByAgency') : $t('cases.waitingAgency') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Нет документов -->
                <div v-else class="px-5 py-6 text-center">
                    <div class="text-sm text-gray-400">{{ $t('cases.noDocsList') }}</div>
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
                                <div class="text-sm font-semibold text-[#0A1F44]">{{ caseData.agency.phone }}</div>
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

            <!-- === Менеджер === -->
            <div v-if="caseData.assignee" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-[#0A1F44] text-sm">{{ $t('cases.managerTitle') }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('cases.managerHint') }}</p>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center text-white font-bold text-lg shrink-0">
                            {{ caseData.assignee.name?.[0]?.toUpperCase() ?? '?' }}
                        </div>
                        <div>
                            <div class="font-bold text-[#0A1F44]">{{ caseData.assignee.name }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">{{ $t('cases.visaSpecialist') }}</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <a v-if="caseData.assignee.phone"
                            :href="`tel:${caseData.assignee.phone}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-[#1BA97F]/5 hover:bg-[#1BA97F]/10 border border-[#1BA97F]/20 transition-colors">
                            <svg class="w-4 h-4 text-[#1BA97F] shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span class="text-sm font-semibold text-[#1BA97F]">{{ caseData.assignee.phone }}</span>
                        </a>
                        <a v-if="caseData.assignee.email"
                            :href="`mailto:${caseData.assignee.email}`"
                            class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm text-gray-700">{{ caseData.assignee.email }}</span>
                        </a>
                        <a v-if="caseData.assignee.telegram_username"
                            :href="`https://t.me/${caseData.assignee.telegram_username}`" target="_blank" rel="noopener"
                            class="flex items-center gap-3 p-3 rounded-xl bg-[#229ED9]/5 hover:bg-[#229ED9]/10 border border-[#229ED9]/20 transition-colors">
                            <svg class="w-4 h-4 text-[#229ED9] shrink-0" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.248l-2.018 9.51c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L6.51 14.617 3.56 13.7c-.657-.204-.671-.657.137-.972l10.905-4.205c.548-.194 1.027.126.96.725z"/>
                            </svg>
                            <span class="text-sm font-semibold text-[#229ED9]">@{{ caseData.assignee.telegram_username }}</span>
                        </a>
                        <div v-if="!caseData.assignee.phone && !caseData.assignee.email && !caseData.assignee.telegram_username"
                            class="text-sm text-gray-400 p-3">
                            {{ $t('cases.managerWillContact') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- === Без менеджера === -->
            <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-[#0A1F44]">{{ $t('cases.noManager') }}</div>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $t('cases.noManagerHint') }}</p>
                    </div>
                </div>
            </div>

            <!-- === Отзыв об агентстве === -->
            <div v-if="caseData.agency?.id && reviewState.loaded"
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

        <!-- Ошибка / не найдено -->
        <div v-else-if="!loading" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="text-3xl mb-3">404</div>
            <div class="font-semibold text-[#0A1F44]">{{ $t('cases.notFound') }}</div>
            <button @click="router.push({ name: 'me.cases' })"
                class="mt-4 px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] transition-colors">
                {{ $t('cases.backToList') }}
            </button>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';
import AgencyCard from '@/components/AgencyCard.vue';
import i18n from '@/i18n';

const { t } = useI18n();
const route  = useRoute();
const router = useRouter();

const loading     = ref(true);
const caseData    = ref(null);
const uploading   = ref({});
const uploadToast = ref('');

// --- Inline-агентства ---
const inlineAgencies  = ref([]);
const agenciesLoading = ref(false);
const confirmAgency   = ref(null);
const agencySubmitting = ref(false);

// --- Оплата ---
const paymentLoading = ref(false);
const PAYMENT_PROVIDERS = [
    { id: 'click', label: 'Click', icon: 'C', bgClass: 'bg-blue-100 text-blue-600' },
    { id: 'payme', label: 'Payme', icon: 'P', bgClass: 'bg-cyan-100 text-cyan-600' },
    { id: 'uzum',  label: 'Uzum',  icon: 'U', bgClass: 'bg-purple-100 text-purple-600' },
];

// --- Смена агентства ---
const showChangeAgencyModal = ref(false);
const changingAgency = ref(false);

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

async function uploadDoc(itemId, event) {
    const file = event.target.files[0];
    if (!file) return;
    uploading.value[itemId] = true;
    try {
        const fd = new FormData();
        fd.append('file', file);
        await publicPortalApi.uploadChecklistItem(route.params.id, itemId, fd);
        // Обновляем статус локально
        const item = caseData.value?.checklist?.find(i => i.id === itemId);
        if (item) item.status = 'uploaded';
        uploadToast.value = t('cases.docUploaded');
        setTimeout(() => { uploadToast.value = ''; }, 3000);
    } catch (e) {
        alert(e?.response?.data?.message ?? t('cases.uploadError'));
    } finally {
        uploading.value[itemId] = false;
        event.target.value = '';
    }
}

const PUBLIC_STATUSES = [
    { key: 'draft' },
    { key: 'submitted' },
    { key: 'manager_assigned' },
    { key: 'document_collection' },
    { key: 'submitted_to_embassy' },
    { key: 'decision_pending' },
    { key: 'completed' },
    { key: 'rejected' },
];

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
        submitted:             'bg-blue-50 text-blue-600',
        manager_assigned:      'bg-indigo-50 text-indigo-700',
        document_collection:   'bg-amber-50 text-amber-700',
        submitted_to_embassy:  'bg-orange-50 text-orange-700',
        decision_pending:      'bg-purple-50 text-purple-700',
        completed:             'bg-green-50 text-green-700',
        rejected:              'bg-red-50 text-red-700',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

function statusTextColor(status) {
    if (status === 'completed') return 'text-[#1BA97F]';
    if (status === 'rejected')  return 'text-red-500';
    return 'text-gray-500';
}

function getProgressColor(index, status, order) {
    if (status === 'rejected') {
        return index < order ? 'bg-red-300' : index === order ? 'bg-red-500' : 'bg-gray-100';
    }
    if (status === 'completed') return 'bg-[#1BA97F]';
    return index < order ? 'bg-[#1BA97F]' : index === order ? 'bg-[#1BA97F]/50' : 'bg-gray-100';
}

function statusLabel(status, required) {
    if (status === 'approved') return t('cases.approved');
    if (status === 'uploaded') return t('cases.uploadedReview');
    return required ? t('cases.needUpload') : t('common.optional');
}

function deadlineClass(dateStr) {
    if (!dateStr) return 'text-gray-600';
    const days = Math.floor((new Date(dateStr) - new Date()) / 86400000);
    if (days < 0)  return 'text-red-600';
    if (days <= 5) return 'text-amber-600';
    return 'text-[#0A1F44]';
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
        // Загружаем статус отзыва для агентства
        if (caseData.value?.agency?.id) {
            await loadCanReview(caseData.value.agency.id);
        }
        // Загружаем inline-агентства для draft без агентства
        if (caseData.value?.public_status === 'draft' && !caseData.value?.agency) {
            loadInlineAgencies();
        }
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

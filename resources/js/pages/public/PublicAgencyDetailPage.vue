<template>
    <div v-if="loading" class="flex items-center justify-center py-20">
        <div class="w-6 h-6 border-2 border-[#0A1F44] border-t-transparent rounded-full animate-spin"></div>
    </div>

    <div v-else-if="agency">

        <!-- Назад -->
        <button @click="router.back()" class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] mb-4 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ t('agencyDetail.back') }}
        </button>

        <!-- Hero -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <!-- Gradient header -->
            <div class="h-24 sm:h-32 bg-gradient-to-r from-[#0A1F44] via-[#1a3a6e] to-[#0A1F44] relative">
                <div class="absolute inset-0 opacity-10"
                    style="background-image: url('data:image/svg+xml,%3Csvg width=60 height=60 viewBox=%270 0 60 60%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cg fill=%27%23ffffff%27 fill-opacity=%271%27%3E%3Cpath d=%27M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%27/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')"></div>
            </div>

            <div class="px-5 sm:px-6 pb-5 sm:pb-6 -mt-10 sm:-mt-12 relative">
                <div class="flex items-end gap-4">
                    <!-- Логотип -->
                    <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white border-4 border-white shadow-lg flex items-center justify-center shrink-0 overflow-hidden">
                        <img v-if="agency.logo_url" :src="agency.logo_url" :alt="agency.name"
                            class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full bg-gradient-to-br from-[#0A1F44] to-[#1a3a6e] flex items-center justify-center">
                            <span class="text-white text-3xl font-bold">{{ agency.name?.charAt(0) }}</span>
                        </div>
                    </div>
                    <div class="pb-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-bold text-[#0A1F44]">{{ agency.name }}</h1>
                            <span v-if="agency.is_verified"
                                class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-600 border border-blue-100 px-2 py-0.5 rounded-full font-medium">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ t('agencyDetail.verified') }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-sm text-gray-500">
                            <span v-if="agency.city" class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                {{ agency.city }}
                            </span>
                            <span v-if="agency.experience_years">{{ t('agencies.experience', { years: agency.experience_years }) }}</span>
                            <span v-if="agency.member_since" class="text-gray-400">{{ t('agencyDetail.memberSince', { date: agency.member_since }) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Рейтинг -->
                <div class="mt-4 flex items-center gap-3">
                    <div v-if="agency.rating" class="flex items-center gap-2">
                        <span class="text-3xl font-bold text-[#0A1F44]">{{ Number(agency.rating).toFixed(1) }}</span>
                        <div>
                            <div class="flex gap-0.5">
                                <svg v-for="n in 5" :key="n" class="w-5 h-5"
                                    :class="n <= Math.round(agency.rating) ? 'text-amber-400' : 'text-gray-200'"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                            <span class="text-xs text-gray-400">{{ agency.reviews_count }} {{ t('agencyDetail.reviewsCount') }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-gray-400 italic">{{ t('agencies.noReviews') }}</div>

                    <!-- Лучший критерий -->
                    <div v-if="agency.top_criterion && agency.reviews_count >= 1"
                        class="ml-auto flex items-center gap-1.5 text-sm text-[#1BA97F] font-medium bg-green-50 px-3 py-1.5 rounded-full">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ topCriterionMessage(agency.top_criterion) }}
                    </div>
                </div>

                <!-- Описание -->
                <p v-if="agency.description" class="mt-4 text-sm text-gray-600 leading-relaxed">
                    {{ agency.description }}
                </p>
            </div>
        </div>

        <!-- Статистика в цифрах -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="grid grid-cols-3 sm:grid-cols-5 divide-x divide-gray-100">
                <!-- Всего обработано -->
                <div class="p-4 text-center">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-[#0A1F44]">{{ agency.total_cases || 0 }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ t('agencyDetail.totalCases') }}</div>
                </div>
                <!-- Сейчас в работе -->
                <div class="p-4 text-center">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-amber-500">{{ agency.active_cases || 0 }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ t('agencyDetail.activeCases') }}</div>
                </div>
                <!-- Успешность -->
                <div class="p-4 text-center">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center mx-auto mb-2"
                        :class="agency.success_rate >= 80 ? 'bg-green-50' : agency.success_rate >= 50 ? 'bg-amber-50' : 'bg-gray-50'">
                        <svg class="w-5 h-5" :class="agency.success_rate >= 80 ? 'text-[#1BA97F]' : agency.success_rate >= 50 ? 'text-amber-500' : 'text-gray-400'"
                            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold" :class="agency.success_rate >= 80 ? 'text-[#1BA97F]' : agency.success_rate >= 50 ? 'text-amber-500' : 'text-gray-400'">
                        {{ agency.success_rate != null ? agency.success_rate + '%' : t('agencyDetail.noData') }}
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ t('agencyDetail.successRate') }}</div>
                </div>
                <!-- Сотрудников -->
                <div class="p-4 text-center">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-[#0A1F44]">{{ agency.team_count || 0 }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ t('agencyDetail.teamCount') }}</div>
                </div>
                <!-- Клиентов -->
                <div class="p-4 text-center">
                    <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center mx-auto mb-2">
                        <svg class="w-5 h-5 text-[#1BA97F]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                    <div class="text-2xl font-bold text-[#0A1F44]">{{ agency.clients_count || 0 }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">{{ t('agencyDetail.clientsCount') }}</div>
                </div>
            </div>
        </div>

        <!-- Страны работы -->
        <div v-if="agency.countries?.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-[#0A1F44] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ t('agencyDetail.workCountries') }} ({{ agency.countries.length }})
                </h2>
            </div>
            <div class="p-5 flex flex-wrap gap-2">
                <span v-for="code in agency.countries" :key="code"
                    class="inline-flex items-center gap-1.5 text-sm bg-blue-50 text-blue-700 border border-blue-100 px-3 py-1.5 rounded-full font-medium">
                    <span>{{ codeToFlag(code) }}</span>
                    <span>{{ countryName(code) }}</span>
                </span>
            </div>
        </div>

        <!-- Команда -->
        <div v-if="agency.team?.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-[#0A1F44] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    {{ t('agencyDetail.team') }} ({{ agency.team.length }})
                </h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-5">
                    <div v-for="member in agency.team" :key="member.name"
                        class="flex flex-col items-center text-center group">
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-full overflow-hidden bg-gray-100 mb-2.5 ring-3 ring-gray-100 group-hover:ring-[#1BA97F]/20 transition-all shadow-sm">
                            <img v-if="member.avatar_url" :src="member.avatar_url" :alt="member.name"
                                class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300">
                                <span class="text-2xl font-bold text-gray-500">{{ member.name?.charAt(0) }}</span>
                            </div>
                        </div>
                        <span class="text-sm font-semibold text-[#0A1F44]">{{ member.name }}</span>
                        <span class="text-xs mt-0.5 px-2 py-0.5 rounded-full"
                            :class="member.role === 'owner' ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-500'">
                            {{ roleLabel(member.role) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Пакеты услуг -->
        <div v-if="agency.packages?.length" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-[#0A1F44] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    {{ t('agencies.packages') }} ({{ agency.packages.length }})
                </h2>
            </div>
            <div class="divide-y divide-gray-50">
                <div v-for="pkg in agency.packages" :key="pkg.id"
                    class="px-5 py-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="font-semibold text-[#0A1F44] text-sm">{{ pkg.name }}</span>
                                <span v-if="pkg.country_code" class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full">
                                    {{ codeToFlag(pkg.country_code) }} {{ countryName(pkg.country_code) }}
                                </span>
                                <span v-if="pkg.visa_type" class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">{{ visaTypeLabel(pkg.visa_type) }}</span>
                                <span v-if="pkg.processing_days" class="text-xs bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full">{{ pkg.processing_days }} {{ t('common.days') }}</span>
                            </div>
                            <p v-if="pkg.description" class="text-xs text-gray-400 mb-2 leading-relaxed">{{ pkg.description }}</p>
                            <div v-if="pkg.services?.length" class="flex flex-wrap gap-1">
                                <span v-for="svc in pkg.services" :key="svc.name"
                                    class="text-xs bg-green-50 text-green-700 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ svc.name }}
                                </span>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="font-bold text-[#0A1F44] text-lg whitespace-nowrap">
                                {{ pkg.price ? formatPriceSom(pkg.price) : t('common.byRequest') }}
                            </div>
                            <button @click="openConfirm(pkg)"
                                class="mt-2 px-5 py-2 bg-[#1BA97F] hover:bg-[#17956f] text-white text-xs font-semibold rounded-xl transition-colors">
                                {{ t('agencies.select') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Контакты -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-[#0A1F44] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    {{ t('agencyDetail.contacts') }}
                </h2>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a v-if="agency.phone" :href="`tel:${agency.phone}`"
                    class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 hover:bg-blue-50 transition-colors group">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">{{ t('agencyDetail.phone') }}</div>
                        <div class="text-sm font-medium text-[#0A1F44] group-hover:text-blue-600">{{ agency.phone }}</div>
                    </div>
                </a>

                <a v-if="agency.email" :href="`mailto:${agency.email}`"
                    class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 hover:bg-purple-50 transition-colors group">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Email</div>
                        <div class="text-sm font-medium text-[#0A1F44] group-hover:text-purple-600">{{ agency.email }}</div>
                    </div>
                </a>

                <a v-if="agency.website_url" :href="agency.website_url" target="_blank" rel="noopener"
                    class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50 hover:bg-green-50 transition-colors group">
                    <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">{{ t('agencies.site') }}</div>
                        <div class="text-sm font-medium text-[#0A1F44] group-hover:text-green-600">{{ agency.website_url }}</div>
                    </div>
                </a>

                <div v-if="agency.address"
                    class="flex items-center gap-3 p-3.5 rounded-xl bg-gray-50">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">{{ t('agencyDetail.address') }}</div>
                        <div class="text-sm font-medium text-[#0A1F44]">{{ agency.address }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Отзывы -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-[#0A1F44] text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ t('agencies.clientReviews') }}
                    </h2>
                    <div v-if="reviewsData?.stats?.avg_rating" class="flex items-center gap-1.5">
                        <span class="text-lg font-bold text-[#0A1F44]">{{ reviewsData.stats.avg_rating }}</span>
                        <div class="flex gap-0.5">
                            <svg v-for="n in 5" :key="n" class="w-4 h-4"
                                :class="n <= Math.round(reviewsData.stats.avg_rating) ? 'text-amber-400' : 'text-gray-200'"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <span class="text-xs text-gray-400">({{ reviewsData.stats.total }})</span>
                    </div>
                </div>
            </div>

            <div class="p-5">
                <!-- Критерии рейтинга -->
                <div v-if="reviewsData?.stats?.criteria_avg" class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
                    <div v-for="(val, key) in reviewsData.stats.criteria_avg" :key="key"
                        class="bg-gray-50 rounded-xl p-3 text-center">
                        <div class="text-lg font-bold text-[#0A1F44]">{{ Number(val).toFixed(1) }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ criterionLabel(key) }}</div>
                        <div class="mt-1.5 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all"
                                :class="Number(val) >= 4 ? 'bg-[#1BA97F]' : Number(val) >= 3 ? 'bg-amber-400' : 'bg-red-400'"
                                :style="{ width: (Number(val) / 5 * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Распределение оценок -->
                <div v-if="reviewsData?.stats?.distribution" class="mb-5 bg-gray-50 rounded-xl p-4">
                    <div v-for="star in [5,4,3,2,1]" :key="star" class="flex items-center gap-2 mb-1.5 last:mb-0">
                        <span class="text-xs text-gray-500 w-3 text-right">{{ star }}</span>
                        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-400 rounded-full transition-all"
                                :style="{ width: distPct(star) + '%' }"></div>
                        </div>
                        <span class="text-xs text-gray-400 w-6 text-right">{{ reviewsData.stats.distribution[star] || 0 }}</span>
                    </div>
                </div>

                <!-- Табы сортировки -->
                <div class="flex gap-1.5 mb-4 overflow-x-auto scrollbar-hide">
                    <button v-for="tab in reviewTabs" :key="tab.value"
                        @click="setReviewSort(tab.value)"
                        class="flex-shrink-0 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors"
                        :class="currentSort === tab.value
                            ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                            : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'">
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Загрузка -->
                <div v-if="reviewsLoading" class="py-8 text-center">
                    <div class="w-5 h-5 border-2 border-[#0A1F44] border-t-transparent rounded-full animate-spin mx-auto"></div>
                </div>

                <!-- Нет отзывов -->
                <div v-else-if="!reviewsData?.reviews?.length" class="py-8 text-center">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-gray-400">{{ t('agencies.noReviewsYet') }}</p>
                </div>

                <!-- Список отзывов -->
                <div v-else class="space-y-3">
                    <div v-for="review in reviewsData.reviews" :key="review.id"
                        class="bg-gray-50 rounded-xl p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div>
                                <div class="font-semibold text-[#0A1F44] text-sm">{{ review.client_name }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ review.created_at }}</div>
                            </div>
                            <div class="flex gap-0.5 shrink-0">
                                <svg v-for="n in 5" :key="n" class="w-4 h-4"
                                    :class="n <= review.rating ? 'text-amber-400' : 'text-gray-200'"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <!-- Детали оценок -->
                        <div v-if="review.punctuality" class="flex flex-wrap gap-1.5 mb-2">
                            <span v-for="(v, k) in reviewCriteria(review)" :key="k"
                                class="text-xs px-2 py-0.5 rounded-full"
                                :class="v >= 4 ? 'bg-green-50 text-green-600' : v >= 3 ? 'bg-amber-50 text-amber-600' : 'bg-red-50 text-red-600'">
                                {{ criterionLabel(k) }}: {{ v }}
                            </span>
                        </div>
                        <p v-if="review.comment" class="text-sm text-gray-600 leading-relaxed">{{ review.comment }}</p>
                        <p v-else class="text-xs text-gray-400 italic">{{ t('agencies.noComment') }}</p>
                    </div>
                </div>

                <!-- Пагинация -->
                <div v-if="reviewsData?.pagination?.last_page > 1" class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100">
                    <button v-if="reviewsData.pagination.current_page > 1"
                        @click="loadReviewsPage(reviewsData.pagination.current_page - 1)"
                        class="text-xs text-[#0A1F44] font-medium hover:underline">{{ t('agencies.previous') }}</button>
                    <span v-else></span>
                    <span class="text-xs text-gray-400">{{ reviewsData.pagination.current_page }} / {{ reviewsData.pagination.last_page }}</span>
                    <button v-if="reviewsData.pagination.current_page < reviewsData.pagination.last_page"
                        @click="loadReviewsPage(reviewsData.pagination.current_page + 1)"
                        class="text-xs text-[#0A1F44] font-medium hover:underline">{{ t('agencies.next') }}</button>
                    <span v-else></span>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="bg-gradient-to-r from-[#0A1F44] via-[#1a3a6e] to-[#0A1F44] rounded-2xl p-5 sm:p-6 mb-4 relative overflow-hidden">
            <div class="absolute inset-0 opacity-5"
                style="background-image: url('data:image/svg+xml,%3Csvg width=60 height=60 viewBox=%270 0 60 60%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cg fill=%27none%27 fill-rule=%27evenodd%27%3E%3Cg fill=%27%23ffffff%27 fill-opacity=%271%27%3E%3Cpath d=%27M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z%27/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')"></div>
            <div class="relative flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-white font-bold text-base sm:text-lg">{{ t('agencyDetail.ctaTitle') }}</h3>
                    <p class="text-white/60 text-sm mt-1">{{ t('agencyDetail.ctaDesc') }}</p>
                </div>
                <button @click="openConfirm(null)"
                    class="shrink-0 px-6 py-3 bg-[#1BA97F] hover:bg-[#17956f] text-white font-semibold text-sm rounded-xl transition-colors flex items-center gap-2 shadow-lg">
                    {{ t('agencies.sendApplication') }}
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Карта -->
        <div v-if="agency.latitude && agency.longitude" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
            <div class="px-5 py-4 border-b border-gray-50">
                <h2 class="font-bold text-[#0A1F44] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    {{ t('agencyDetail.onMap') }}
                </h2>
            </div>
            <div class="relative" style="height: 300px;">
                <iframe
                    :src="`https://yandex.ru/map-widget/v1/?lang=ru_RU&ll=${agency.longitude},${agency.latitude}&z=15&pt=${agency.longitude},${agency.latitude},pm2rd&l=map`"
                    width="100%" height="300" frameborder="0" allowfullscreen class="block"></iframe>
            </div>
        </div>

    </div>

    <!-- Модал подтверждения -->
    <div v-if="confirm.show"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
        @click.self="confirm.show = false">
        <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-[#0A1F44]">{{ t('agencies.confirmTitle') }}</h3>
                    <button @click="confirm.show = false" class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ t('agencies.confirmAgency') }}</span>
                        <span class="font-semibold text-[#0A1F44] text-right">{{ agency.name }}</span>
                    </div>
                    <div v-if="confirm.pkg" class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ t('agencies.confirmPackage') }}</span>
                        <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.pkg.name }}</span>
                    </div>
                    <div v-if="confirm.pkg?.price" class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ t('agencies.confirmPrice') }}</span>
                        <span class="font-bold text-[#0A1F44]">{{ formatPriceSom(confirm.pkg.price) }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-5 leading-relaxed">{{ t('agencies.confirmDesc') }}</p>
                <div class="flex gap-3">
                    <button @click="confirm.show = false"
                        class="flex-1 py-3 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-50">
                        {{ t('common.cancel') }}
                    </button>
                    <button @click="submitLead" :disabled="submitting"
                        class="flex-1 py-3 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold rounded-xl disabled:opacity-60 flex items-center justify-center gap-2">
                        <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ submitting ? t('agencies.sending') : t('agencies.send') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div v-if="toast"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] max-w-sm w-[calc(100%-2rem)]
               bg-[#1BA97F] text-white rounded-2xl shadow-xl px-5 py-4 flex items-start gap-3">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
        <div>
            <div class="font-semibold text-sm">{{ toastTitle }}</div>
            <div class="text-xs text-white/80 mt-0.5">{{ toast }}</div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { countryName as getCountryName, codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const route  = useRoute();
const router = useRouter();

const agency         = ref(null);
const loading        = ref(true);
const reviewsData    = ref(null);
const reviewsLoading = ref(false);
const currentSort    = ref('latest');
const submitting     = ref(false);
const toast          = ref('');
const toastTitle     = ref('');
const confirm        = ref({ show: false, pkg: null });

const reviewTabs = computed(() => [
    { value: 'latest',   label: t('agencies.latest') },
    { value: 'positive', label: t('agencies.positive') },
    { value: 'negative', label: t('agencies.negative') },
]);

function countryName(code) { return getCountryName(code) ?? code; }

function roleLabel(role) {
    const map = {
        owner:   t('agencyDetail.roleOwner'),
        manager: t('agencyDetail.roleManager'),
        partner: t('agencyDetail.rolePartner'),
    };
    return map[role] ?? role;
}

function topCriterionMessage(key) {
    const map = {
        quality:         t('agencies.criterionQuality'),
        punctuality:     t('agencies.criterionPunctuality'),
        communication:   t('agencies.criterionCommunication'),
        professionalism: t('agencies.criterionProfessionalism'),
        price_quality:   t('agencies.criterionPriceQuality'),
    };
    return map[key] ?? '';
}

function criterionLabel(key) {
    const map = {
        punctuality:     t('agencyDetail.critPunctuality'),
        quality:         t('agencyDetail.critQuality'),
        communication:   t('agencyDetail.critCommunication'),
        professionalism: t('agencyDetail.critProfessionalism'),
        price_quality:   t('agencyDetail.critPriceQuality'),
    };
    return map[key] ?? key;
}

function reviewCriteria(review) {
    const result = {};
    for (const k of ['punctuality', 'quality', 'communication', 'professionalism', 'price_quality']) {
        if (review[k]) result[k] = review[k];
    }
    return result;
}

function distPct(star) {
    if (!reviewsData.value?.stats?.total) return 0;
    const count = reviewsData.value.stats.distribution?.[star] || 0;
    return Math.round((count / reviewsData.value.stats.total) * 100);
}

const VISA_TYPE_LABELS = computed(() => ({
    tourist: t('portal.touristVisa'), business: t('portal.businessVisa'),
    student: t('portal.studentVisaFull'), work: t('portal.workVisa'),
    transit: t('portal.transitVisa'), immigrant: t('portal.immigrantVisa'),
}));
function visaTypeLabel(type) { return VISA_TYPE_LABELS.value[type] || type || ''; }

function formatPriceSom(price) {
    if (!price) return '';
    const n = Number(price);
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    const somLabel = i18n.global.locale.value === 'uz' ? "so'm" : 'сум';
    return `${n.toLocaleString(locale)} ${somLabel}`;
}

function openConfirm(pkg) {
    confirm.value = { show: true, pkg };
}

async function submitLead() {
    submitting.value = true;
    try {
        const firstPkg = confirm.value.pkg ?? agency.value.packages?.[0];
        const payload = {
            agency_id:    agency.value.id,
            country_code: firstPkg?.country_code || '',
            visa_type:    firstPkg?.visa_type || 'tourist',
            package_id:   confirm.value.pkg?.id ?? null,
        };
        const res = await publicPortalApi.submitLead(payload);
        confirm.value.show = false;
        showToast(t('agencies.sent'), t('agencies.sentDesc', { name: agency.value.name }));
        const targetCaseId = res.data?.data?.case_id;
        setTimeout(() => {
            toast.value = '';
            if (targetCaseId) {
                router.push({ name: 'me.cases.show', params: { id: targetCaseId } });
            } else {
                router.push({ name: 'me.cases' });
            }
        }, 2500);
    } catch (e) {
        if (e?.response?.status === 409) {
            confirm.value.show = false;
            showToast(t('agencies.alreadySent'), t('agencies.alreadySentDesc'));
            setTimeout(() => { toast.value = ''; }, 3000);
        } else {
            alert(e?.response?.data?.message ?? t('agencies.sendError'));
        }
    } finally {
        submitting.value = false;
    }
}

function showToast(title, msg) {
    toastTitle.value = title;
    toast.value      = msg;
    setTimeout(() => { toast.value = ''; toastTitle.value = ''; }, 3500);
}

async function loadAgency() {
    loading.value = true;
    try {
        const res = await publicPortalApi.agencyDetail(route.params.id);
        agency.value = res.data.data;
    } catch {
        agency.value = null;
    } finally {
        loading.value = false;
    }
}

async function loadReviews(page = 1) {
    reviewsLoading.value = true;
    try {
        const res = await publicPortalApi.agencyReviews(route.params.id, { sort: currentSort.value, page });
        reviewsData.value = res.data.data;
    } catch {
        reviewsData.value = { reviews: [], pagination: {}, stats: {} };
    } finally {
        reviewsLoading.value = false;
    }
}

async function loadReviewsPage(page) { await loadReviews(page); }

async function setReviewSort(sort) {
    currentSort.value = sort;
    await loadReviews(1);
}

onMounted(async () => {
    await loadAgency();
    if (agency.value) loadReviews();
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

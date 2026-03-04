<template>
    <div>

        <!-- Баннер: выбор агентства для существующей заявки -->
        <div v-if="caseId"
            class="mb-4 bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-sm text-blue-800 font-medium flex-1">
                {{ $t('cases.choosingForCase', { country: countryName(countryCode), visa: visaTypeLabel(route.query.visa_type) }) }}
            </p>
            <button @click="cancelCaseLink"
                class="text-blue-400 hover:text-blue-600 p-1 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Хедер страницы -->
        <div class="mb-5 sm:mb-6">
            <h1 class="text-xl sm:text-2xl font-bold text-[#0A1F44]">
                <span v-if="countryCode">{{ countryFlag(countryCode) }} {{ $t('agencies.agenciesFor', { country: countryName(countryCode) }) }}</span>
                <span v-else>{{ $t('agencies.title') }}</span>
            </h1>
            <p class="text-gray-500 text-sm mt-0.5">{{ $t('agencies.subtitle') }}</p>
        </div>

        <!-- Фильтр по стране — autocomplete -->
        <div v-if="allCountries.length > 1" class="mb-4 relative" ref="countryPickerRef">
            <!-- Поле ввода -->
            <div class="flex items-center gap-2 bg-white border rounded-xl px-3 py-2.5 shadow-sm transition-colors"
                :class="countryDropOpen ? 'border-[#0A1F44]' : 'border-gray-200'">
                <!-- Иконка поиска -->
                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                </svg>
                <!-- Если выбрана страна — показываем бейдж и крест, иначе поле ввода -->
                <template v-if="selectedCountryCode && !countryDropOpen">
                    <span class="flex items-center gap-1.5 flex-1 text-sm font-medium text-[#0A1F44]">
                        <span>{{ allCountries.find(c => c.code === selectedCountryCode)?.flag }}</span>
                        {{ allCountries.find(c => c.code === selectedCountryCode)?.label }}
                    </span>
                    <button @click.stop="clearCountry"
                        class="text-gray-400 hover:text-red-500 transition-colors p-0.5 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </template>
                <input v-else
                    ref="countryInputRef"
                    v-model="countryQuery"
                    @focus="countryDropOpen = true"
                    @input="countryDropOpen = true"
                    @keydown.escape="countryDropOpen = false"
                    @keydown.enter.prevent="pickFirstSuggestion"
                    @keydown.arrow-down.prevent="moveSuggestion(1)"
                    @keydown.arrow-up.prevent="moveSuggestion(-1)"
                    :placeholder="$t('agencies.destinationCountry')"
                    class="flex-1 text-sm outline-none bg-transparent placeholder-gray-400 text-[#0A1F44]"
                    autocomplete="off"
                />
                <!-- Стрелка -->
                <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform"
                    :class="countryDropOpen ? 'rotate-180' : ''"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    @click="toggleCountryDrop">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>

            <!-- Дропдаун -->
            <div v-show="countryDropOpen && countrySuggestions.length"
                class="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg z-30 max-h-56 overflow-y-auto">
                <button v-for="(c, idx) in countrySuggestions" :key="c.code"
                    @click="selectCountry(c.code)"
                    class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-left transition-colors"
                    :class="idx === highlightedIdx
                        ? 'bg-[#0A1F44] text-white'
                        : 'text-gray-700 hover:bg-gray-50'">
                    <span class="text-base w-5 shrink-0">{{ c.flag }}</span>
                    <span class="flex-1">{{ c.label }}</span>
                    <svg v-if="c.code === selectedCountryCode" class="w-4 h-4 text-[#1BA97F] shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Фильтр по типу визы + сортировка по цене -->
        <div class="flex items-center gap-2 mb-5 flex-wrap">
            <!-- Тип визы -->
            <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide flex-1 min-w-0">
                <button v-for="vt in visaTypes" :key="vt.value"
                    @click="selectedVisaType = vt.value"
                    class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors"
                    :class="selectedVisaType === vt.value
                        ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                    {{ vt.label }}
                </button>
            </div>
            <!-- Сортировка по цене -->
            <button @click="cyclePriceSort"
                class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors"
                :class="priceSort !== 'none'
                    ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                </svg>
                <span v-if="priceSort === 'none'">{{ priceSortLabels.none }}</span>
                <span v-else-if="priceSort === 'asc'">{{ priceSortLabels.asc }}</span>
                <span v-else>{{ priceSortLabels.desc }}</span>
                <svg v-if="priceSort === 'asc'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
                <svg v-else-if="priceSort === 'desc'" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                </svg>
            </button>
            <!-- Сортировка по отзывам -->
            <button @click="cycleReviewsSort"
                class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium border transition-colors"
                :class="reviewsSort !== 'none'
                    ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                    : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span v-if="reviewsSort === 'none'">{{ reviewsSortLabels.none }}</span>
                <span v-else-if="reviewsSort === 'count'">{{ reviewsSortLabels.count }}</span>
                <span v-else>{{ reviewsSortLabels.rating }}</span>
            </button>
        </div>

        <!-- Загрузка -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-14 h-14 bg-gray-100 rounded-xl shrink-0"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-5 bg-gray-100 rounded w-48"></div>
                        <div class="h-4 bg-gray-50 rounded w-32"></div>
                    </div>
                </div>
                <div class="h-4 bg-gray-50 rounded mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <!-- Пусто -->
        <div v-else-if="!agencies.length"
            class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 text-center">
            <div class="text-4xl mb-3">🏢</div>
            <div class="font-semibold text-[#0A1F44] mb-1">{{ $t('agencies.emptyTitle') }}</div>
            <p class="text-sm text-gray-500">{{ $t('agencies.emptyDesc') }}</p>
        </div>

        <!-- Список агентств -->
        <div v-else class="space-y-4">
            <div v-for="agency in sortedAgencies" :key="agency.id"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-all">

                <!-- ── Шапка карточки (всегда видна) ── -->
                <div class="p-5">
                    <div class="flex items-start gap-4">
                        <!-- Логотип -->
                        <div class="shrink-0 w-14 h-14 rounded-xl overflow-hidden border border-gray-100 flex items-center justify-center bg-gray-50">
                            <img v-if="agency.logo_url" :src="agency.logo_url" :alt="agency.name"
                                class="w-full h-full object-cover">
                            <span v-else class="text-xl font-bold text-gray-300">{{ agency.name?.[0]?.toUpperCase() }}</span>
                        </div>
                        <!-- Название и метаданные -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-bold text-[#0A1F44] text-base leading-tight">{{ agency.name }}</h2>
                                <span v-if="agency.is_verified"
                                    class="flex items-center gap-1 text-xs text-[#1BA97F] bg-[#1BA97F]/10 px-2 py-0.5 rounded-full font-medium shrink-0">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    {{ $t('cases.verified') }}
                                </span>
                            </div>
                            <!-- Мета-строка -->
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                                <span v-if="agency.city" class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    {{ agency.city }}
                                </span>
                                <span v-if="agency.experience_years">{{ $t('agencies.experience', { years: agency.experience_years }) }}</span>
                                <!-- Рейтинг -->
                                <span v-if="agency.rating" class="flex items-center gap-1">
                                    <span class="flex gap-0.5">
                                        <svg v-for="n in 5" :key="n"
                                            class="w-3 h-3"
                                            :class="n <= Math.round(agency.rating) ? 'text-amber-400' : 'text-gray-200'"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </span>
                                    <span class="font-semibold text-gray-600">{{ Number(agency.rating).toFixed(1) }}</span>
                                    <span v-if="agency.reviews_count" class="text-gray-300">({{ agency.reviews_count }})</span>
                                </span>
                                <span v-else class="text-gray-300 italic">{{ noReviewsLabel }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Описание -->
                    <p v-if="agency.description" class="text-sm text-gray-500 leading-relaxed mt-3 line-clamp-2">
                        {{ agency.description }}
                    </p>

                    <!-- Highlight лучшего критерия -->
                    <div v-if="agency.top_criterion && agency.reviews_count >= 1"
                        class="mt-2 flex items-center gap-1.5 text-xs text-[#1BA97F] font-medium">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ topCriterionMessage(agency.top_criterion) }}
                    </div>

                    <!-- Страны + минимальная цена из пакетов -->
                    <div v-if="countryPrices(agency).length" class="mt-3 flex flex-wrap gap-1.5">
                        <span v-for="cp in countryPrices(agency)" :key="cp.code"
                            class="inline-flex items-center gap-1 text-xs bg-blue-50 text-blue-700 border border-blue-100 px-2.5 py-1 rounded-full font-medium">
                            <span>{{ cp.flag }}</span>
                            <span>{{ cp.name }}</span>
                            <span class="text-blue-400 mx-0.5">—</span>
                            <span v-if="cp.minPrice" class="text-blue-600 font-semibold">{{ formatPriceFrom(cp.minPrice) }}</span>
                            <span v-else class="text-blue-400 font-normal">{{ byRequestLabel }}</span>
                        </span>
                    </div>

                    <!-- Статс-строка -->
                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-400">
                        <span v-if="agency.packages?.length" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            {{ plu(agency.packages.length, packagesPlurals[0], packagesPlurals[1], packagesPlurals[2]) }}
                        </span>
                        <span v-if="agency.phone" class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ agency.phone }}
                        </span>
                        <a v-if="agency.website_url" :href="agency.website_url" target="_blank" rel="noopener"
                            @click.stop class="flex items-center gap-1 text-[#1BA97F] hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            {{ $t('agencies.site') }}
                        </a>
                    </div>
                </div>

                <!-- ── Кнопки раскрытия ── -->
                <div class="px-5 pb-4 flex flex-wrap gap-2">
                    <!-- Пакеты -->
                    <button v-if="agency.packages?.length"
                        @click="togglePackages(agency.id)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                        :class="expandedPackages[agency.id]
                            ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        {{ $t('agencies.packages') }} ({{ agency.packages.length }})
                        <svg class="w-3 h-3 transition-transform" :class="expandedPackages[agency.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <!-- Подробнее -->
                    <button @click="toggleDetails(agency)"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                        :class="expandedDetails[agency.id]
                            ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                            : 'bg-white text-gray-600 border-gray-200 hover:border-gray-400'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ aboutAndReviewsLabel }}
                        <svg class="w-3 h-3 transition-transform" :class="expandedDetails[agency.id] ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <!-- Отправить заявку -->
                    <button @click="openConfirm(agency, null)"
                        class="ml-auto flex items-center gap-1.5 px-4 py-1.5 bg-[#1BA97F] hover:bg-[#17956f] text-white text-xs font-semibold rounded-full transition-colors">
                        {{ $t('agencies.sendApplication') }}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </button>
                </div>

                <!-- ── Пакеты услуг (раскрывается) ── -->
                <div v-show="expandedPackages[agency.id]" class="border-t border-gray-50">
                    <div v-if="!agency.packages?.length" class="px-5 py-4 text-sm text-gray-400 text-center">
                        {{ $t('agencies.noPackagesHint') }}
                    </div>
                    <div v-else class="divide-y divide-gray-50">
                        <div v-for="pkg in agency.packages" :key="pkg.id"
                            class="px-5 py-4 flex items-start gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="font-semibold text-[#0A1F44] text-sm">{{ pkg.name }}</span>
                                    <span v-if="pkg.visa_type" class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full">{{ pkg.visa_type }}</span>
                                    <span v-if="pkg.processing_days" class="text-xs bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full">{{ pkg.processing_days }} {{ $t('common.days') }}</span>
                                </div>
                                <p v-if="pkg.description" class="text-xs text-gray-400 mb-2 leading-relaxed">{{ pkg.description }}</p>
                                <div v-if="pkg.services?.length" class="flex flex-wrap gap-1">
                                    <span v-for="svc in pkg.services.slice(0, 6)" :key="svc.name"
                                        class="text-xs bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full">{{ svc.name }}</span>
                                    <span v-if="pkg.services.length > 6" class="text-xs text-gray-400 px-1">+{{ pkg.services.length - 6 }}</span>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="font-bold text-[#0A1F44] text-base whitespace-nowrap">
                                    {{ pkg.price ? formatPriceSom(pkg.price) : $t('common.byRequest') }}
                                </div>
                                <button @click="openConfirm(agency, pkg)"
                                    class="mt-2 px-4 py-1.5 bg-[#1BA97F] hover:bg-[#17956f] text-white text-xs font-semibold rounded-lg transition-colors">
                                    {{ $t('agencies.select') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Подробнее об агентстве + отзывы (раскрывается) ── -->
                <div v-show="expandedDetails[agency.id]" class="border-t border-gray-50">

                    <!-- Контакты -->
                    <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-sm border-b border-gray-50">
                        <a v-if="agency.phone" :href="`tel:${agency.phone}`"
                            class="flex items-center gap-2 text-gray-600 hover:text-[#0A1F44]">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ agency.phone }}
                        </a>
                        <a v-if="agency.email" :href="`mailto:${agency.email}`"
                            class="flex items-center gap-2 text-gray-600 hover:text-[#0A1F44]">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ agency.email }}
                        </a>
                        <span v-if="agency.address" class="flex items-center gap-2 text-gray-500 sm:col-span-2">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ agency.address }}
                        </span>
                    </div>

                    <!-- Полное описание если есть -->
                    <div v-if="agency.description" class="px-5 py-4 border-b border-gray-50">
                        <p class="text-sm text-gray-600 leading-relaxed">{{ agency.description }}</p>
                    </div>

                    <!-- Блок отзывов -->
                    <div class="px-5 py-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-bold text-[#0A1F44] text-sm">{{ clientReviewsLabel }}</h3>
                            <!-- Итоговый рейтинг -->
                            <div v-if="reviewsData[agency.id]?.stats?.avg_rating" class="flex items-center gap-1.5">
                                <span class="text-lg font-bold text-[#0A1F44]">{{ reviewsData[agency.id].stats.avg_rating }}</span>
                                <div class="flex gap-0.5">
                                    <svg v-for="n in 5" :key="n" class="w-3.5 h-3.5"
                                        :class="n <= Math.round(reviewsData[agency.id].stats.avg_rating) ? 'text-amber-400' : 'text-gray-200'"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                <span class="text-xs text-gray-400">({{ reviewsData[agency.id].stats.total }})</span>
                            </div>
                        </div>

                        <!-- Табы сортировки -->
                        <div class="flex gap-1.5 mb-3 overflow-x-auto scrollbar-hide">
                            <button v-for="tab in reviewTabs" :key="tab.value"
                                @click="setReviewSort(agency.id, tab.value)"
                                class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-medium border transition-colors"
                                :class="(reviewSort[agency.id] || 'latest') === tab.value
                                    ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                                    : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300'">
                                {{ tab.label }}
                            </button>
                        </div>

                        <!-- Загрузка отзывов -->
                        <div v-if="reviewsLoading[agency.id]" class="py-6 text-center">
                            <div class="w-5 h-5 border-2 border-[#0A1F44] border-t-transparent rounded-full animate-spin mx-auto"></div>
                        </div>

                        <!-- Нет отзывов -->
                        <div v-else-if="!reviewsData[agency.id]?.reviews?.length"
                            class="py-6 text-center text-sm text-gray-400">
                            {{ noReviewsYetLabel }}
                        </div>

                        <!-- Список отзывов -->
                        <div v-else class="space-y-3 mb-3">
                            <div v-for="review in reviewsData[agency.id].reviews" :key="review.id"
                                class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div>
                                        <div class="font-semibold text-[#0A1F44] text-sm">{{ review.client_name }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ review.created_at }}</div>
                                    </div>
                                    <div class="flex gap-0.5 shrink-0">
                                        <svg v-for="n in 5" :key="n" class="w-3.5 h-3.5"
                                            :class="n <= review.rating ? 'text-amber-400' : 'text-gray-200'"
                                            fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p v-if="review.comment" class="text-sm text-gray-600 leading-relaxed">{{ review.comment }}</p>
                                <p v-else class="text-xs text-gray-400 italic">{{ noCommentLabel }}</p>
                            </div>
                        </div>

                        <!-- Пагинация -->
                        <div v-if="reviewsData[agency.id]?.pagination?.last_page > 1"
                            class="flex items-center justify-between mb-4">
                            <button v-if="reviewsData[agency.id].pagination.current_page > 1"
                                @click="loadReviewsPage(agency.id, reviewsData[agency.id].pagination.current_page - 1)"
                                class="text-xs text-[#0A1F44] font-medium hover:underline">
                                {{ prevLabel }}
                            </button>
                            <span class="text-xs text-gray-400">
                                {{ reviewsData[agency.id].pagination.current_page }} / {{ reviewsData[agency.id].pagination.last_page }}
                            </span>
                            <button v-if="reviewsData[agency.id].pagination.current_page < reviewsData[agency.id].pagination.last_page"
                                @click="loadReviewsPage(agency.id, reviewsData[agency.id].pagination.current_page + 1)"
                                class="text-xs text-[#0A1F44] font-medium hover:underline">
                                {{ nextLabel }}
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- ── Карта агентств (Яндекс) ── -->
        <div v-if="!loading && agenciesWithCoords.length" class="mt-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-50">
                    <h2 class="font-bold text-[#0A1F44] text-sm">{{ mapTitle }}</h2>
                    <p class="text-xs text-gray-400 mt-0.5">{{ plu(agenciesWithCoords.length, agenciesPlurals[0], agenciesPlurals[1], agenciesPlurals[2]) }} {{ $t('agencies.mapWithAddress') }}</p>
                </div>
                <div class="relative" style="height: 420px;">
                    <iframe
                        :src="yandexMapUrl"
                        width="100%"
                        height="420"
                        frameborder="0"
                        allowfullscreen
                        class="block"
                    ></iframe>
                </div>
            </div>
        </div>

    </div>

    <!-- ── Модал подтверждения заявки ── -->
    <div v-if="confirm.show"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
        @click.self="confirm.show = false">
        <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-[#0A1F44]">{{ $t('agencies.confirmTitle') }}</h3>
                    <button @click="confirm.show = false"
                        class="text-gray-400 hover:text-gray-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ $t('agencies.confirmAgency') }}</span>
                        <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.agency?.name }}</span>
                    </div>
                    <div v-if="countryCode" class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ $t('agencies.country') }}</span>
                        <span class="font-semibold text-[#0A1F44]">{{ countryFlag(countryCode) }} {{ countryName(countryCode) }}</span>
                    </div>
                    <div v-if="confirm.pkg" class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ $t('agencies.confirmPackage') }}</span>
                        <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.pkg.name }}</span>
                    </div>
                    <div v-if="confirm.pkg?.price" class="flex justify-between gap-3">
                        <span class="text-gray-400 shrink-0">{{ $t('agencies.confirmPrice') }}</span>
                        <span class="font-bold text-[#0A1F44]">{{ formatPriceSom(confirm.pkg.price) }}</span>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mb-5 leading-relaxed">
                    {{ $t('agencies.confirmDesc') }}
                </p>
                <div class="flex gap-3">
                    <button @click="confirm.show = false"
                        class="flex-1 py-3 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-50">
                        {{ $t('common.cancel') }}
                    </button>
                    <button @click="submitLead" :disabled="submitting"
                        class="flex-1 py-3 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold rounded-xl disabled:opacity-60 flex items-center justify-center gap-2">
                        <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ submitting ? $t('agencies.sending') : $t('agencies.send') }}
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
import { ref, computed, reactive, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { countryName as getCountryName, codeToFlag } from '@/utils/countries';
import i18n from '@/i18n';

const { t } = useI18n();
const route  = useRoute();
const router = useRouter();

const caseId = ref(route.query.case_id ?? null);
const selectedCountryCode = ref((route.query.country_code ?? '').toUpperCase());
const countryCode = computed(() => selectedCountryCode.value);

const agencies     = ref([]);
const loading      = ref(false);
const selectedVisaType = ref(route.query.visa_type ?? '');
const priceSort   = ref('none'); // 'none' | 'asc' | 'desc'
const reviewsSort = ref('none'); // 'none' | 'count' | 'rating'
const submitting  = ref(false);
const toast      = ref('');
const toastTitle = ref('');

const confirm = ref({ show: false, agency: null, pkg: null });
const allCountries = ref([]);

// ── Autocomplete для стран ────────────────────────────────────────────────────
const countryQuery    = ref('');
const countryDropOpen = ref(false);
const highlightedIdx  = ref(-1);
const countryInputRef = ref(null);
const countryPickerRef = ref(null);

// Подсказки: если нет поиска — все страны (кроме "Все страны"); если есть — фильтруем
const countrySuggestions = computed(() => {
    const q = countryQuery.value.trim().toLowerCase();
    const list = allCountries.value.filter(c => c.code !== ''); // без "Все страны"
    if (!q) return list;
    return list.filter(c =>
        c.label.toLowerCase().includes(q) ||
        c.code.toLowerCase().startsWith(q)
    );
});

function toggleCountryDrop() {
    if (countryDropOpen.value) {
        countryDropOpen.value = false;
    } else {
        countryDropOpen.value = true;
        countryQuery.value = '';
        highlightedIdx.value = -1;
        nextTick(() => countryInputRef.value?.focus());
    }
}

function clearCountry() {
    selectedCountryCode.value = '';
    countryQuery.value = '';
    countryDropOpen.value = false;
    router.replace({ query: { ...route.query, country_code: undefined } });
    loadAgencies();
}

function moveSuggestion(dir) {
    const max = countrySuggestions.value.length - 1;
    if (max < 0) return;
    highlightedIdx.value = Math.max(0, Math.min(max, highlightedIdx.value + dir));
}

function pickFirstSuggestion() {
    const idx = highlightedIdx.value >= 0 ? highlightedIdx.value : 0;
    const c   = countrySuggestions.value[idx];
    if (c) selectCountry(c.code);
}

// Закрыть дропдаун при клике за пределами
function onClickOutside(e) {
    if (countryPickerRef.value && !countryPickerRef.value.contains(e.target)) {
        countryDropOpen.value = false;
    }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));

// Раскрытие карточек
const expandedPackages = reactive({});
const expandedDetails  = reactive({});

// Отзывы
const reviewsData    = reactive({});
const reviewsLoading = reactive({});
const reviewSort     = reactive({});

const visaTypes = computed(() => [
    { value: '',          label: t('agencies.allTypes') },
    { value: 'tourist',   label: t('agencies.tourist') },
    { value: 'business',  label: t('agencies.business') },
    { value: 'student',   label: t('agencies.student') },
    { value: 'work',      label: t('agencies.work') },
    { value: 'transit',   label: t('agencies.transit') },
]);

const reviewTabs = computed(() => [
    { value: 'latest',   label: t('agencies.latest') },
    { value: 'positive', label: t('agencies.positive') },
    { value: 'negative', label: t('agencies.negative') },
]);

// Computed labels for template strings that need reactivity
const priceSortLabels = computed(() => ({
    none: t('agencies.byPrice'),
    asc: t('agencies.cheaper'),
    desc: t('agencies.moreExpensive'),
}));

const reviewsSortLabels = computed(() => ({
    none: t('agencies.byReviews'),
    count: t('agencies.moreReviews'),
    rating: t('agencies.bestRating'),
}));

const noReviewsLabel = computed(() => t('agencies.noReviews'));
const aboutAndReviewsLabel = computed(() => t('agencies.aboutAndReviews'));
const clientReviewsLabel = computed(() => t('agencies.clientReviews'));
const noReviewsYetLabel = computed(() => t('agencies.noReviewsYet'));
const noCommentLabel = computed(() => t('agencies.noComment'));
const prevLabel = computed(() => t('agencies.previous'));
const nextLabel = computed(() => t('agencies.next'));
const byRequestLabel = computed(() => t('common.byRequest').toLowerCase());
const mapTitle = computed(() => t('landing.agenciesMapTitle'));
const packagesPlurals = computed(() => [
    t('agencies.packageOne'),
    t('agencies.packageFew'),
    t('agencies.packageMany'),
]);
const agenciesPlurals = computed(() => [
    t('agencies.agencyOne'),
    t('agencies.agencyFew'),
    t('agencies.agencyMany'),
]);

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
function countryName(code) { return getCountryName(code) ?? code; }
function countryFlag(code) { return codeToFlag(code); }

function plu(n, one, few, many) {
    if (i18n.global.locale.value === 'uz') return `${n} ${one}`;
    const mod10  = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return `${n} ${one}`;
    if (mod10 >= 2 && mod10 <= 4 && (mod100 < 10 || mod100 >= 20)) return `${n} ${few}`;
    return `${n} ${many}`;
}

// Минимальная цена пакета агентства (null если все "по запросу")
function minPrice(agency) {
    const prices = (agency.packages ?? [])
        .map(p => p.price)
        .filter(p => p !== null && p !== undefined && p > 0)
        .map(Number);
    return prices.length ? Math.min(...prices) : null;
}

// Сортировка агентств
const sortedAgencies = computed(() => {
    let list = agencies.value;
    // Сортировка по отзывам
    if (reviewsSort.value === 'count') {
        list = [...list].sort((a, b) => (b.reviews_count ?? 0) - (a.reviews_count ?? 0));
    } else if (reviewsSort.value === 'rating') {
        list = [...list].sort((a, b) => (b.rating ?? 0) - (a.rating ?? 0));
    }
    // Сортировка по цене (если активна)
    if (priceSort.value !== 'none') {
        list = [...list].sort((a, b) => {
            const pa = minPrice(a) ?? (priceSort.value === 'asc' ? Infinity : -Infinity);
            const pb = minPrice(b) ?? (priceSort.value === 'asc' ? Infinity : -Infinity);
            return priceSort.value === 'asc' ? pa - pb : pb - pa;
        });
    }
    return list;
});

// Переключение цены: none -> asc -> desc -> none (сбрасывает reviewsSort)
function cyclePriceSort() {
    reviewsSort.value = 'none';
    priceSort.value = priceSort.value === 'none' ? 'asc'
        : priceSort.value === 'asc'  ? 'desc'
        : 'none';
}

// Переключение отзывов: none -> count -> rating -> none (сбрасывает priceSort)
function cycleReviewsSort() {
    priceSort.value = 'none';
    reviewsSort.value = reviewsSort.value === 'none'  ? 'count'
        : reviewsSort.value === 'count' ? 'rating'
        : 'none';
}

// Страны + минимальные цены из пакетов агентства
function countryPrices(agency) {
    const pkgs = agency.packages ?? [];
    const map  = {};
    for (const pkg of pkgs) {
        const code = (pkg.country_code ?? '').toUpperCase();
        if (!code) continue;
        const price = pkg.price ? Number(pkg.price) : null;
        if (!map[code]) {
            map[code] = {
                code,
                name:     getCountryName(code) ?? code,
                flag:     codeToFlag(code) ?? '',
                minPrice: price,
            };
        } else if (price !== null && (map[code].minPrice === null || price < map[code].minPrice)) {
            map[code].minPrice = price;
        }
    }
    return Object.values(map);
}

// Форматирование цены с "от": от 1,5 млн сум
function formatPriceFrom(price) {
    if (!price) return '';
    const prefix = i18n.global.locale.value === 'uz' ? '' : 'от ';
    return prefix + formatPrice(price);
}

// Форматирование цены: 1 500 000 -> "1,5 млн сум"; < 1 млн -> "500 000 сум"
function formatPrice(price) {
    if (!price) return '';
    const n = Number(price);
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    const somLabel = i18n.global.locale.value === 'uz' ? 'so\'m' : 'сум';
    const mlnLabel = i18n.global.locale.value === 'uz' ? 'mln' : 'млн';
    if (n >= 1_000_000) {
        const millions = n / 1_000_000;
        const formatted = millions % 1 === 0 ? millions.toFixed(0) : millions.toFixed(1);
        return `${formatted} ${mlnLabel} ${somLabel}`;
    }
    return `${n.toLocaleString(locale)} ${somLabel}`;
}

// Форматирование цены в сумах: "1 500 000 сум"
function formatPriceSom(price) {
    if (!price) return '';
    const n = Number(price);
    const locale = i18n.global.locale.value === 'uz' ? 'uz-UZ' : 'ru-RU';
    const somLabel = i18n.global.locale.value === 'uz' ? 'so\'m' : 'сум';
    return `${n.toLocaleString(locale)} ${somLabel}`;
}

// Агентства с координатами для карты
const agenciesWithCoords = computed(() =>
    agencies.value.filter(a => a.latitude && a.longitude)
);

// URL для Яндекс Карты
const yandexMapUrl = computed(() => {
    if (!agenciesWithCoords.value.length) return '';
    const pts = agenciesWithCoords.value
        .map((a, i) => `${a.longitude},${a.latitude},pm2rd`)
        .join('~');
    // Центр карты — первое агентство
    const first = agenciesWithCoords.value[0];
    const zoom = agenciesWithCoords.value.length === 1 ? 14 : 7;
    return `https://yandex.ru/map-widget/v1/?lang=ru_RU&ll=${first.longitude},${first.latitude}&z=${zoom}&pt=${pts}&l=map`;
});

async function loadCountries() {
    try {
        const res = await publicPortalApi.countries();
        const list = res.data.data ?? [];
        allCountries.value = [
            { code: '', label: t('scoring.allCountries'), flag: '' },
            ...list.map(c => ({
                code:  c.code ?? c.country_code,
                label: c.name ?? c.code ?? c.country_code,
                flag:  c.flag ?? c.flag_emoji ?? '',
            })),
        ];
    } catch { /* ignore */ }
}

function selectCountry(code) {
    selectedCountryCode.value = code;
    countryQuery.value    = '';
    countryDropOpen.value = false;
    highlightedIdx.value  = -1;
    router.replace({ query: { ...route.query, country_code: code || undefined } });
    loadAgencies();
}

async function loadAgencies() {
    loading.value = true;
    try {
        const params = {};
        if (countryCode.value)   params.country_code = countryCode.value;
        if (selectedVisaType.value) params.visa_type = selectedVisaType.value;
        const res = await publicPortalApi.agencies(params);
        agencies.value = res.data.data?.agencies ?? [];
    } catch {
        agencies.value = [];
    } finally {
        loading.value = false;
    }
}

function togglePackages(agencyId) {
    expandedPackages[agencyId] = !expandedPackages[agencyId];
}

async function toggleDetails(agency) {
    const id = agency.id;
    expandedDetails[id] = !expandedDetails[id];
    if (expandedDetails[id] && !reviewsData[id]) {
        await loadReviews(id);
    }
}

async function loadReviews(agencyId, page = 1) {
    reviewsLoading[agencyId] = true;
    try {
        const sort = reviewSort[agencyId] ?? 'latest';
        const res  = await publicPortalApi.agencyReviews(agencyId, { sort, page });
        reviewsData[agencyId] = res.data.data;
    } catch {
        reviewsData[agencyId] = { reviews: [], pagination: {}, stats: {} };
    } finally {
        reviewsLoading[agencyId] = false;
    }
}

async function loadReviewsPage(agencyId, page) {
    await loadReviews(agencyId, page);
}

async function setReviewSort(agencyId, sort) {
    reviewSort[agencyId] = sort;
    await loadReviews(agencyId, 1);
}

function openConfirm(agency, pkg) {
    confirm.value = { show: true, agency, pkg };
}

function cancelCaseLink() {
    caseId.value = null;
    router.replace({ name: 'me.agencies', query: { country_code: route.query.country_code } });
}

const VISA_TYPE_LABELS_MAP = computed(() => ({
    tourist: t('portal.touristVisa'), business: t('portal.businessVisa'),
    student: t('portal.studentVisaFull'), work: t('portal.workVisa'),
    transit: t('portal.transitVisa'), immigrant: t('portal.immigrantVisa'),
}));
function visaTypeLabel(type) { return VISA_TYPE_LABELS_MAP.value[type] || type || ''; }

async function submitLead() {
    submitting.value = true;
    try {
        const payload = {
            agency_id:    confirm.value.agency.id,
            country_code: countryCode.value || confirm.value.pkg?.country_code || 'UZ',
            visa_type:    confirm.value.pkg?.visa_type || selectedVisaType.value || 'tourist',
            package_id:   confirm.value.pkg?.id ?? null,
        };
        if (caseId.value) {
            payload.case_id = caseId.value;
        }
        const res = await publicPortalApi.submitLead(payload);
        confirm.value.show = false;
        showToast(t('agencies.sent'), t('agencies.sentDesc', { name: confirm.value.agency?.name ?? '' }));
        const targetCaseId = res.data?.data?.case_id ?? caseId.value;
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

watch(selectedVisaType, loadAgencies);
onMounted(async () => {
    await loadCountries();
    loadAgencies();
});
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

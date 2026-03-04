<template>
    <div class="space-y-5">

        <!-- Приветственный баннер для новых пользователей -->
        <div v-if="!publicAuth.user?.name"
            class="bg-gradient-to-r from-[#1BA97F] to-[#0d7a5c] rounded-2xl p-5 sm:p-6 text-white">
            <h2 class="text-lg font-bold mb-1">{{ $t('profile.welcomeTitle') }}</h2>
            <p class="text-sm text-white/70 mb-4">{{ $t('profile.welcomeDesc') }}</p>
            <button @click="showWizard = true"
                class="inline-flex items-center gap-2 bg-[#1BA97F] hover:bg-[#169B72] text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                {{ $t('profile.quickFill') }}
            </button>
        </div>

        <!-- Основные данные (паспорт) -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">{{ $t('profile.personalData') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $t('profile.personalDataHint') }}</p>
            </div>

            <div class="p-5 space-y-4">
                <!-- Предупреждение: латиница как в загранпаспорте -->
                <div class="flex items-start gap-2 p-3 rounded-xl bg-blue-50 border border-blue-100">
                    <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-blue-700">{{ $t('profile.latinWarning') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.firstName') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input v-model="firstName" @input="onLatinInput('firstName', $event)" :placeholder="$t('profile.firstNamePlaceholder')"
                                class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors pr-8"
                                :class="firstName && isLatinOnly(firstName) ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'"/>
                            <div v-if="firstName && isLatinOnly(firstName)" class="absolute right-2.5 top-1/2 -translate-y-1/2">
                                <div class="w-4 h-4 rounded-full bg-[#1BA97F] flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                        </div>
                        <p v-if="firstName && !isLatinOnly(firstName)" class="text-[11px] text-red-500 mt-1">{{ $t('profile.latinOnly') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.lastName') }} <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input v-model="lastName" @input="onLatinInput('lastName', $event)" :placeholder="$t('profile.lastNamePlaceholder')"
                                class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors pr-8"
                                :class="lastName && isLatinOnly(lastName) ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'"/>
                            <div v-if="lastName && isLatinOnly(lastName)" class="absolute right-2.5 top-1/2 -translate-y-1/2">
                                <div class="w-4 h-4 rounded-full bg-[#1BA97F] flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </div>
                            </div>
                        </div>
                        <p v-if="lastName && !isLatinOnly(lastName)" class="text-[11px] text-red-500 mt-1">{{ $t('profile.latinOnly') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.dob') }} <span class="text-red-500">*</span></label>
                        <input v-model="form.dob" type="date" :max="maxDob"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                            :class="form.dob ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'"/>
                        <!-- Чат-сообщение от VisaBor о возрасте -->
                        <div v-if="ageMessage" class="relative mt-3">
                            <!-- Хвостик пузыря -->
                            <div class="absolute -top-1.5 left-4 w-3 h-3 bg-gradient-to-br from-violet-50 to-indigo-50 border-l border-t border-violet-200 rotate-45"></div>
                            <div class="relative bg-gradient-to-br from-violet-50 via-indigo-50 to-purple-50 border border-violet-200 rounded-2xl p-3.5 shadow-sm">
                                <div class="flex items-center gap-1.5 mb-1.5">
                                    <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0">
                                        <span class="text-[8px] font-bold text-white">VB</span>
                                    </div>
                                    <span class="text-[10px] font-semibold text-violet-500">VisaBor</span>
                                </div>
                                <p class="text-xs text-[#0A1F44] leading-relaxed">{{ ageMessage }}</p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.citizenship') }} <span class="text-red-500">*</span></label>
                        <select v-model="form.citizenship"
                            class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                            :class="form.citizenship ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'">
                            <option value="">{{ $t('profile.selectCountry') }}</option>
                            <option value="UZ">🇺🇿 {{ $t('countries.UZ') }}</option>
                            <option value="KZ">🇰🇿 {{ $t('countries.KZ') }}</option>
                            <option value="KG">🇰🇬 {{ $t('countries.KG') }}</option>
                            <option value="TJ">🇹🇯 {{ $t('countries.TJ') }}</option>
                            <option value="TM">🇹🇲 {{ $t('countries.TM') }}</option>
                            <option value="RU">🇷🇺 {{ $t('countries.RU') }}</option>
                            <option value="UA">🇺🇦 {{ $t('countries.UA') }}</option>
                            <option value="GE">🇬🇪 {{ $t('countries.GE') }}</option>
                            <option value="AZ">🇦🇿 {{ $t('countries.AZ') }}</option>
                            <option value="AM">🇦🇲 {{ $t('countries.AM') }}</option>
                            <option value="MD">🇲🇩 {{ $t('countries.MD') }}</option>
                            <option value="BY">🇧🇾 {{ $t('countries.BY') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.gender') }}</label>
                        <div class="flex gap-2">
                            <button type="button" @click="form.gender = 'M'"
                                :class="form.gender === 'M' ? 'bg-[#1BA97F] text-white border-[#1BA97F]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                                class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                                {{ $t('profile.male') }}
                            </button>
                            <button type="button" @click="form.gender = 'F'"
                                :class="form.gender === 'F' ? 'bg-[#1BA97F] text-white border-[#1BA97F]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                                class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                                {{ $t('profile.female') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Паспорт -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">{{ $t('profile.passportData') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $t('profile.passportHint') }}</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.passportNumber') }}</label>
                    <!-- Раздельный ввод: [AA] — [1234567] -->
                    <div class="flex items-stretch rounded-xl border transition-colors overflow-hidden"
                         :class="passportValid ? 'border-[#1BA97F]' : 'border-gray-200 focus-within:border-[#1BA97F]'">
                        <!-- Серия: 2 латинских буквы -->
                        <input
                            ref="passportSeriesInput"
                            :value="passportSeries"
                            @input="handleSeriesInput"
                            placeholder="AA"
                            maxlength="2"
                            autocomplete="off"
                            spellcheck="false"
                            class="w-14 py-2.5 text-center text-sm font-mono uppercase outline-none
                                   bg-gray-50 border-r border-gray-200 tracking-[0.3em] text-[#0A1F44] font-bold"
                        />
                        <!-- Разделитель -->
                        <div class="flex items-center px-2.5 text-gray-300 text-sm select-none font-light">—</div>
                        <!-- Номер: 7 цифр -->
                        <input
                            ref="passportNumberInput"
                            :value="passportDigits"
                            @input="handleDigitsInput"
                            @keydown="handleDigitsKeydown"
                            placeholder="1234567"
                            maxlength="7"
                            inputmode="numeric"
                            autocomplete="off"
                            class="flex-1 px-1 py-2.5 text-sm font-mono outline-none tracking-[0.2em] text-[#0A1F44]"
                        />
                        <!-- Галочка если формат верный -->
                        <div class="flex items-center pr-3 shrink-0">
                            <div v-if="passportValid"
                                 class="w-4 h-4 rounded-full bg-[#1BA97F] flex items-center justify-center">
                                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $t('profile.passportFormat') }}</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.passportExpiry') }}</label>
                    <input v-model="form.passport_expires_at" type="date" :min="minPassportExpiry"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                        :class="passportExpiryColor.border"/>
                    <!-- Чат-сообщение от VisaBor о паспорте -->
                    <div v-if="form.passport_expires_at && passportExpiryColor.msg" class="relative mt-3">
                        <div class="absolute -top-1.5 left-4 w-3 h-3 rotate-45 border-l border-t"
                             :class="passportExpiryColor.bubbleBg + ' ' + passportExpiryColor.bubbleBorder"></div>
                        <div class="relative rounded-2xl p-3.5 shadow-sm border"
                             :class="passportExpiryColor.bubbleBg + ' ' + passportExpiryColor.bubbleBorder">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0">
                                    <span class="text-[8px] font-bold text-white">VB</span>
                                </div>
                                <span class="text-[10px] font-semibold" :class="passportExpiryColor.labelColor">VisaBor</span>
                            </div>
                            <p class="text-xs text-[#0A1F44] leading-relaxed">{{ passportExpiryColor.msg }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Занятость и доходы -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">{{ $t('profile.employment') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $t('profile.employmentHint') }}</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.employmentStatus') }} <span class="text-red-500">*</span></label>
                    <select v-model="form.employment_type"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                        :class="form.employment_type ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'">
                        <option value="">{{ $t('common.notSpecified') }}</option>
                        <option v-for="et in employmentTypes" :key="et.code" :value="et.code">{{ refLabel(et) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        {{ $t('profile.tenure') }}
                        <span class="text-gray-400 font-normal">({{ $t('profile.tenureBoost') }})</span>
                    </label>
                    <select v-model="form.employed_years"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                        :class="form.employed_years !== '' ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'"
                        :disabled="form.employment_type === 'unemployed' || form.employment_type === 'student'">
                        <option value="">{{ $t('common.notSpecified') }}</option>
                        <option :value="0">{{ $t('profile.lessThan1') }}</option>
                        <option :value="1">{{ $t('profile.years12') }}</option>
                        <option :value="3">{{ $t('profile.years25') }}</option>
                        <option :value="5">{{ $t('profile.years510') }}</option>
                        <option :value="10">{{ $t('profile.yearsOver10') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        {{ $t('profile.income') }} <span class="text-red-500">*</span>
                        <span class="text-gray-400 font-normal">({{ $t('profile.incomeImportant') }})</span>
                    </label>
                    <select v-model="form.monthly_income_usd"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                        :class="form.monthly_income_usd ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'">
                        <option value="">{{ $t('common.notSpecified') }}</option>
                        <option :value="300">{{ $t('profile.incomeTo500') }}</option>
                        <option :value="800">{{ $t('profile.income5001000') }}</option>
                        <option :value="1500">{{ $t('profile.income10002000') }}</option>
                        <option :value="3000">{{ $t('profile.income20004000') }}</option>
                        <option :value="5000">{{ $t('profile.incomeOver4000') }}</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $t('profile.incomeHint') }}</p>
                </div>
            </div>
        </div>

        <!-- Семья и привязанность к стране -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">{{ $t('profile.familyTitle') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $t('profile.familyHint') }}</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.maritalStatus') }} <span class="text-red-500">*</span></label>
                    <select v-model="form.marital_status"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                        :class="form.marital_status ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'">
                        <option value="">{{ $t('common.notSpecified') }}</option>
                        <option v-for="ms in maritalStatuses" :key="ms.code" :value="ms.code">{{ refLabel(ms) }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.children') }}</label>
                    <div class="flex gap-2">
                        <button type="button" @click="form.has_children = false; form.children_count = 0"
                            :class="!form.has_children ? 'bg-[#1BA97F] text-white border-[#1BA97F]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                            {{ $t('profile.noChildren') }}
                        </button>
                        <button type="button" @click="form.has_children = true; if (!form.children_count) form.children_count = 1"
                            :class="form.has_children ? 'bg-[#1BA97F] text-white border-[#1BA97F]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                            {{ $t('profile.hasChildren') }}
                        </button>
                    </div>
                </div>
                <div v-if="form.has_children">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.childrenCount') }}</label>
                    <select v-model="form.children_count"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors border-[#1BA97F]">
                        <option :value="1">{{ $t('profile.child1') }}</option>
                        <option :value="2">{{ $t('profile.children2') }}</option>
                        <option :value="3">{{ $t('profile.children3') }}</option>
                        <option :value="4">{{ $t('profile.children4plus') }}</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ $t('profile.propertyTitle') }}</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form.has_property ? 'border-[#1BA97F]/40 bg-[#1BA97F]/5' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <input type="checkbox" v-model="form.has_property" class="sr-only"/>
                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                 :class="form.has_property ? 'bg-[#1BA97F] border-[#1BA97F]' : 'border-gray-300'">
                                <svg v-if="form.has_property" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-medium" :class="form.has_property ? 'text-[#0A1F44]' : 'text-gray-600'">{{ $t('profile.realEstate') }}</span>
                                <p class="text-[11px] text-gray-400">{{ $t('profile.realEstateHint') }}</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form.has_car ? 'border-[#1BA97F]/40 bg-[#1BA97F]/5' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <input type="checkbox" v-model="form.has_car" class="sr-only"/>
                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                 :class="form.has_car ? 'bg-[#1BA97F] border-[#1BA97F]' : 'border-gray-300'">
                                <svg v-if="form.has_car" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-medium" :class="form.has_car ? 'text-[#0A1F44]' : 'text-gray-600'">{{ $t('profile.car') }}</span>
                                <p class="text-[11px] text-gray-400">{{ $t('profile.carHint') }}</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Визовая история -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">{{ $t('profile.visaHistory') }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">{{ $t('profile.visaHistoryHint') }}</p>
            </div>
            <div class="p-5 space-y-4">
                <!-- Количество полученных виз -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ $t('profile.visasObtained') }}</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="opt in visasObtainedOptions" :key="opt.value" type="button"
                            @click="form.visas_obtained_count = opt.value"
                            :class="form.visas_obtained_count === opt.value
                                ? 'bg-[#1BA97F] text-white border-[#1BA97F]'
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="px-2 py-2.5 rounded-xl text-sm border transition-colors font-medium text-center">
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Сильные визы -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ $t('profile.keyVisas') }} <span class="text-gray-400 font-normal">({{ $t('profile.keyVisasHint') }})</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form.has_schengen_visa ? 'border-[#1BA97F]/40 bg-[#1BA97F]/5' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <input type="checkbox" v-model="form.has_schengen_visa" class="sr-only"/>
                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                 :class="form.has_schengen_visa ? 'bg-[#1BA97F] border-[#1BA97F]' : 'border-gray-300'">
                                <svg v-if="form.has_schengen_visa" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-medium" :class="form.has_schengen_visa ? 'text-[#0A1F44]' : 'text-gray-600'">{{ $t('profile.schengenVisa') }}</span>
                                <p class="text-[11px] text-gray-400">{{ $t('profile.schengenVisaHint') }}</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form.has_us_visa ? 'border-[#1BA97F]/40 bg-[#1BA97F]/5' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <input type="checkbox" v-model="form.has_us_visa" class="sr-only"/>
                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                 :class="form.has_us_visa ? 'bg-[#1BA97F] border-[#1BA97F]' : 'border-gray-300'">
                                <svg v-if="form.has_us_visa" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-medium" :class="form.has_us_visa ? 'text-[#0A1F44]' : 'text-gray-600'">{{ $t('profile.usVisa') }}</span>
                                <p class="text-[11px] text-gray-400">{{ $t('profile.usVisaHint') }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Отказы -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">
                        {{ $t('profile.refusals') }}
                        <span class="text-red-500 font-normal">({{ $t('profile.refusalsWarning') }})</span>
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="opt in refusalsOptions" :key="opt.value" type="button"
                            @click="form.refusals_count = opt.value; if (opt.value === 0) form.last_refusal_year = null"
                            :class="form.refusals_count === opt.value
                                ? (opt.value > 0 ? 'bg-red-600 text-white border-red-600' : 'bg-[#1BA97F] text-white border-[#1BA97F]')
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="px-2 py-2.5 rounded-xl text-sm border transition-colors font-medium text-center">
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Год последнего отказа (показывается если есть отказы) -->
                <div v-if="form.refusals_count > 0">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.lastRefusalYear') }}</label>
                    <select v-model="form.last_refusal_year"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-red-400 transition-colors">
                        <option :value="null">{{ $t('common.notSpecified') }}</option>
                        <option v-for="y in recentYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <p class="text-[11px] text-amber-600 mt-1">{{ $t('profile.refusalYearHint') }}</p>
                </div>

                <!-- Нарушения -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">{{ $t('profile.violations') }}</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form.had_overstay ? 'border-amber-300 bg-amber-50' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <input type="checkbox" v-model="form.had_overstay" class="sr-only"/>
                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                 :class="form.had_overstay ? 'bg-amber-500 border-amber-500' : 'border-gray-300'">
                                <svg v-if="form.had_overstay" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-medium" :class="form.had_overstay ? 'text-amber-800' : 'text-gray-600'">{{ $t('profile.overstay') }}</span>
                                <p class="text-[11px]" :class="form.had_overstay ? 'text-amber-600' : 'text-gray-400'">{{ $t('profile.overstayHint') }}</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
                            :class="form.had_deportation ? 'border-red-300 bg-red-50' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                            <input type="checkbox" v-model="form.had_deportation" class="sr-only"/>
                            <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                                 :class="form.had_deportation ? 'bg-red-500 border-red-500' : 'border-gray-300'">
                                <svg v-if="form.had_deportation" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div>
                                <span class="text-sm font-medium" :class="form.had_deportation ? 'text-red-700' : 'text-gray-600'">{{ $t('profile.deportation') }}</span>
                                <p class="text-[11px]" :class="form.had_deportation ? 'text-red-500' : 'text-gray-400'">{{ $t('profile.deportationHint') }}</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопка сохранить -->
        <div class="flex items-center justify-between pb-4">
            <p v-if="saveMsg" class="text-sm font-medium"
               :class="saveError ? 'text-red-500' : 'text-[#1BA97F]'">{{ saveMsg }}</p>
            <div v-else></div>
            <button @click="save" :disabled="saving"
                class="flex items-center gap-2 bg-[#1BA97F] hover:bg-[#169B72] disabled:opacity-60 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg v-if="saving" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ saving ? $t('profile.savingProfile') : $t('profile.saveProfile') }}
            </button>
        </div>
    </div>

    <!-- Quick Wizard Modal -->
    <div v-if="showWizard"
        class="fixed inset-0 bg-[#0A1F44]/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
        @click.self="showWizard = false">
        <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-xl">
            <!-- Progress bar -->
            <div class="h-1.5 bg-gray-100 rounded-t-2xl overflow-hidden">
                <div class="h-full bg-[#1BA97F] transition-all duration-300"
                     :style="{ width: ((wizardStep + 1) / wizardSteps.length * 100) + '%' }"></div>
            </div>

            <div class="p-6">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-400 font-medium">{{ wizardStep + 1 }} / {{ wizardSteps.length }}</span>
                    <button @click="showWizard = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <h3 class="text-base font-bold text-[#0A1F44] mb-1">{{ currentWizardStep.question }}</h3>
                <p v-if="currentWizardStep.hint" class="text-xs text-gray-400 mb-4">{{ currentWizardStep.hint }}</p>

                <!-- Answer buttons -->
                <div class="grid gap-2" :class="currentWizardStep.options.length <= 4 ? 'grid-cols-2' : 'grid-cols-1'">
                    <button v-for="opt in currentWizardStep.options" :key="opt.value"
                        @click="selectWizardAnswer(opt.value)"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 text-left transition-all hover:scale-[1.01]"
                        :class="wizardAnswers[currentWizardStep.field] === opt.value
                            ? 'border-[#1BA97F] bg-[#1BA97F]/10 text-[#0A1F44] font-semibold'
                            : 'border-gray-100 bg-gray-50 text-gray-700 hover:border-gray-200 hover:bg-white'">
                        <span class="text-xl shrink-0">{{ opt.icon }}</span>
                        <span class="text-sm leading-tight">{{ opt.label }}</span>
                    </button>
                </div>

                <!-- Navigate buttons -->
                <div class="flex items-center justify-between mt-5 pt-4 border-t border-gray-50">
                    <button v-if="wizardStep > 0" @click="wizardStep--"
                        class="text-sm text-gray-500 hover:text-gray-700 transition-colors flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ $t('common.back') }}
                    </button>
                    <div v-else></div>

                    <button v-if="wizardStep < wizardSteps.length - 1"
                        @click="wizardStep++"
                        :disabled="!wizardAnswers[currentWizardStep.field]"
                        class="text-sm bg-[#1BA97F] text-white px-4 py-2 rounded-xl font-medium disabled:opacity-40 hover:bg-[#169B72] transition-colors">
                        {{ $t('common.next') }}
                    </button>
                    <button v-else
                        @click="finishWizard"
                        :disabled="!wizardAnswers[currentWizardStep.field]"
                        class="text-sm bg-[#1BA97F] text-white px-4 py-2 rounded-xl font-semibold disabled:opacity-40 hover:bg-[#169B72] transition-colors">
                        {{ $t('profile.wizardDone') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { usePublicReferences } from '@/composables/usePublicReferences';
import { ageMessages } from '@/data/ageMessages';

const { t, locale } = useI18n();
const router     = useRouter();
const publicAuth = usePublicAuthStore();
const { activeItems: refItems } = usePublicReferences();

const employmentTypes = computed(() => refItems('employment_type'));
const maritalStatuses = computed(() => refItems('marital_status'));

function refLabel(item) {
    if (locale.value === 'uz' && item.label_uz) return item.label_uz;
    return item.label_ru;
}

const saving    = ref(false);
const saveMsg   = ref('');
const saveError = ref(false);

const showWizard = ref(false);
const wizardStep = ref(0);
const wizardAnswers = reactive({});

// Имя и Фамилия (Latin only) — хранятся раздельно, объединяются в name при сохранении
function splitName(fullName) {
    const parts = (fullName || '').trim().split(/\s+/);
    return { first: parts[0] || '', last: parts.slice(1).join(' ') || '' };
}
const _nameParts = splitName(publicAuth.user?.name);
const firstName = ref(_nameParts.first);
const lastName  = ref(_nameParts.last);

function isLatinOnly(val) {
    return /^[A-Za-z\s\-']+$/.test(val || '');
}

function onLatinInput(field, e) {
    // Убираем кириллицу и спецсимволы, оставляем только латиницу, пробелы, дефис, апостроф
    const clean = e.target.value.replace(/[^A-Za-z\s\-']/g, '');
    if (field === 'firstName') firstName.value = clean;
    else lastName.value = clean;
    e.target.value = clean;
}

const form = reactive({
    name:                 publicAuth.user?.name ?? '',
    dob:                  publicAuth.user?.dob?.slice(0, 10) ?? '',
    citizenship:          publicAuth.user?.citizenship ?? '',
    gender:               publicAuth.user?.gender ?? '',
    passport_number:      publicAuth.user?.passport_number ?? '',
    passport_expires_at:  publicAuth.user?.passport_expires_at?.slice(0, 10) ?? '',
    employment_type:      publicAuth.user?.employment_type ?? '',
    employed_years:       publicAuth.user?.employed_years ?? '',
    monthly_income_usd:   publicAuth.user?.monthly_income_usd ?? '',
    marital_status:       publicAuth.user?.marital_status ?? '',
    has_children:         !!publicAuth.user?.has_children,
    children_count:       publicAuth.user?.children_count ?? 1,
    has_property:         !!publicAuth.user?.has_property,
    has_car:              !!publicAuth.user?.has_car,
    visas_obtained_count: publicAuth.user?.visas_obtained_count ?? 0,
    has_schengen_visa:    !!publicAuth.user?.has_schengen_visa,
    has_us_visa:          !!publicAuth.user?.has_us_visa,
    refusals_count:       publicAuth.user?.refusals_count ?? 0,
    last_refusal_year:    publicAuth.user?.last_refusal_year ?? null,
    had_overstay:         !!publicAuth.user?.had_overstay,
    had_deportation:      !!publicAuth.user?.had_deportation,
});

// Максимальная дата рождения (18 лет назад)
const maxDob = computed(() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 18);
    return d.toISOString().slice(0, 10);
});

// Минимальная дата паспорта (сегодня + 1 день)
const minPassportExpiry = computed(() => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return d.toISOString().slice(0, 10);
});

// Цветовая логика срока паспорта
const passportExpiryColor = computed(() => {
    if (!form.passport_expires_at) return { border: 'border-gray-200 focus:border-[#1BA97F]', msg: '', textClass: '', bubbleBg: '', bubbleBorder: '', labelColor: '' };
    const days = Math.floor((new Date(form.passport_expires_at) - new Date()) / 86400000);
    if (days < 0) return {
        border: 'border-red-500',
        msg: t('profile.passportExpired'),
        textClass: 'text-red-600',
        bubbleBg: 'bg-gradient-to-br from-red-50 to-rose-50',
        bubbleBorder: 'border-red-200',
        labelColor: 'text-red-500',
    };
    if (days < 180) return {
        border: 'border-red-500',
        msg: t('profile.passportCritical'),
        textClass: 'text-red-600',
        bubbleBg: 'bg-gradient-to-br from-red-50 to-rose-50',
        bubbleBorder: 'border-red-200',
        labelColor: 'text-red-500',
    };
    if (days < 365) return {
        border: 'border-amber-400',
        msg: t('profile.passportWarning'),
        textClass: 'text-amber-600',
        bubbleBg: 'bg-gradient-to-br from-amber-50 to-orange-50',
        bubbleBorder: 'border-amber-200',
        labelColor: 'text-amber-500',
    };
    return {
        border: 'border-[#1BA97F]',
        msg: t('profile.passportGreat'),
        textClass: 'text-[#1BA97F]',
        bubbleBg: 'bg-gradient-to-br from-emerald-50 to-teal-50',
        bubbleBorder: 'border-emerald-200',
        labelColor: 'text-emerald-500',
    };
});

// Возраст и сообщение о возрасте
const userAge = computed(() => {
    if (!form.dob) return null;
    const birth = new Date(form.dob);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    return age >= 1 && age <= 100 ? age : null;
});

const ageMessage = computed(() => {
    if (!userAge.value) return '';
    const lang = locale.value === 'uz' ? 'uz' : 'ru';
    return ageMessages[lang]?.[userAge.value] ?? '';
});

// Паспорт — раздельный ввод серии и номера
const passportSeriesInput = ref(null);
const passportNumberInput = ref(null);
const passportSeries = ref('');
const passportDigits = ref('');

const passportValid = computed(() => /^[A-Z]{2}[0-9]{7}$/.test(form.passport_number || ''));

function initPassportFields(pn) {
    const clean = (pn || '').toUpperCase();
    passportSeries.value = clean.slice(0, 2);
    passportDigits.value = clean.slice(2, 9);
}

function handleSeriesInput(e) {
    const clean = e.target.value.replace(/[^a-zA-Z]/g, '').toUpperCase().slice(0, 2);
    passportSeries.value = clean;
    e.target.value = clean;
    form.passport_number = clean + passportDigits.value;
    if (clean.length === 2) nextTick(() => passportNumberInput.value?.focus());
}

function handleDigitsInput(e) {
    const clean = e.target.value.replace(/[^0-9]/g, '').slice(0, 7);
    passportDigits.value = clean;
    e.target.value = clean;
    form.passport_number = passportSeries.value + clean;
}

function handleDigitsKeydown(e) {
    if (e.key === 'Backspace' && passportDigits.value === '') {
        nextTick(() => passportSeriesInput.value?.focus());
    }
}

const visasObtainedOptions = computed(() => [
    { value: 0,  label: t('common.no') },
    { value: 1,  label: '1\u20132' },
    { value: 3,  label: '3\u20135' },
    { value: 6,  label: '6+' },
]);

const refusalsOptions = computed(() => [
    { value: 0,  label: t('common.no') },
    { value: 1,  label: '1' },
    { value: 2,  label: '2' },
    { value: 3,  label: '3+' },
]);

const recentYears = Array.from({ length: 8 }, (_, i) => new Date().getFullYear() - i);

// Быстрый мастер — 5 шагов
const wizardSteps = computed(() => [
    {
        field: 'employment_type',
        question: t('profile.wizardEmployment'),
        hint: t('profile.wizardEmploymentHint'),
        options: [
            { value: 'employed',       icon: '\uD83D\uDCBC', label: t('profile.employed') },
            { value: 'business_owner', icon: '\uD83C\uDFE2', label: t('profile.businessOwner') },
            { value: 'self_employed',  icon: '\uD83D\uDEE0', label: t('profile.selfEmployed') },
            { value: 'student',        icon: '\uD83C\uDF93', label: t('profile.student') },
            { value: 'retired',        icon: '\uD83C\uDFD6', label: t('profile.retired') },
            { value: 'unemployed',     icon: '\uD83D\uDD0D', label: t('profile.unemployed') },
        ],
    },
    {
        field: 'employed_years',
        question: t('profile.wizardTenure'),
        hint: t('profile.wizardTenureHint'),
        options: [
            { value: 0,  icon: '\uD83C\uDD95', label: t('profile.lessThan1') },
            { value: 1,  icon: '\uD83D\uDCC5', label: t('profile.years12') },
            { value: 3,  icon: '\uD83D\uDCC8', label: t('profile.years25') },
            { value: 5,  icon: '\uD83C\uDFC6', label: t('profile.years510') },
            { value: 10, icon: '\uD83E\uDD47', label: t('profile.yearsOver10') },
        ],
    },
    {
        field: 'monthly_income_usd',
        question: t('profile.wizardIncome'),
        hint: t('profile.wizardIncomeHint'),
        options: [
            { value: 300,  icon: '\uD83D\uDCB5', label: t('profile.incomeTo500') },
            { value: 800,  icon: '\uD83D\uDCB5', label: t('profile.income5001000') },
            { value: 1500, icon: '\uD83D\uDCB0', label: t('profile.income10002000') },
            { value: 3000, icon: '\uD83D\uDCB0', label: t('profile.income20004000') },
            { value: 5000, icon: '\uD83D\uDC8E', label: t('profile.incomeOver4000') },
        ],
    },
    {
        field: 'marital_status',
        question: t('profile.wizardFamily'),
        hint: t('profile.wizardFamilyHint'),
        options: [
            { value: 'single',   icon: '\uD83D\uDC64', label: t('profile.single') },
            { value: 'married',  icon: '\uD83D\uDC6B', label: t('profile.married') },
            { value: 'divorced', icon: '\uD83D\uDCC4', label: t('profile.divorced') },
            { value: 'widowed',  icon: '\uD83D\uDD4A', label: t('profile.widowed') },
        ],
    },
    {
        field: 'visaHistory',
        question: t('profile.wizardVisa'),
        hint: t('profile.wizardVisaHint'),
        options: [
            { value: 'none',    icon: '\uD83C\uDD95', label: t('profile.wizardNoVisa') },
            { value: 'schengen',icon: '\uD83C\uDDEA\uD83C\uDDFA', label: t('profile.wizardSchengen') },
            { value: 'us',      icon: '\uD83C\uDDFA\uD83C\uDDF8', label: t('profile.wizardUs') },
            { value: 'both',    icon: '\u2708\uFE0F',  label: t('profile.wizardBoth') },
        ],
    },
]);

const currentWizardStep = computed(() => wizardSteps.value[wizardStep.value]);

function selectWizardAnswer(value) {
    wizardAnswers[currentWizardStep.value.field] = value;
    // Автопереход на следующий шаг через небольшую задержку
    if (wizardStep.value < wizardSteps.value.length - 1) {
        setTimeout(() => { wizardStep.value++; }, 250);
    }
}

async function finishWizard() {
    if (wizardAnswers.employment_type)   form.employment_type   = wizardAnswers.employment_type;
    if (wizardAnswers.employed_years !== undefined) form.employed_years = wizardAnswers.employed_years;
    if (wizardAnswers.monthly_income_usd) form.monthly_income_usd = wizardAnswers.monthly_income_usd;
    if (wizardAnswers.marital_status)    form.marital_status    = wizardAnswers.marital_status;
    const vh = wizardAnswers.visaHistory;
    if (vh === 'schengen' || vh === 'both') form.has_schengen_visa = true;
    if (vh === 'us' || vh === 'both') form.has_us_visa = true;

    await save();
    showWizard.value = false;
    router.push({ name: 'me.scoring' });
}

async function save() {
    saving.value = true;
    saveMsg.value = '';
    saveError.value = false;
    try {
        // Собираем name из firstName + lastName
        form.name = [firstName.value, lastName.value].filter(Boolean).join(' ').trim();
        const payload = { ...form };
        if (!payload.monthly_income_usd) delete payload.monthly_income_usd;
        const { data } = await publicPortalApi.updateProfile(payload);
        publicAuth.user = data.data.user;
        localStorage.setItem('public_user', JSON.stringify(data.data.user));
        saveMsg.value = t('profile.profileSaved');
        setTimeout(() => { saveMsg.value = ''; }, 3000);
    } catch (e) {
        saveError.value = true;
        saveMsg.value = e.response?.data?.message ?? t('profile.saveError');
    } finally {
        saving.value = false;
    }
}

// Инициализация серии/номера из формы при старте
initPassportFields(form.passport_number);

onMounted(async () => {
    try {
        await publicAuth.fetchMe();
        Object.keys(form).forEach(key => {
            const val = publicAuth.user?.[key];
            if (val !== undefined && val !== null && val !== '') {
                form[key] = (key === 'passport_expires_at' || key === 'dob') ? val?.slice(0, 10) ?? '' : val;
            }
        });
        // Разбить name на firstName/lastName
        const parts = splitName(form.name);
        firstName.value = parts.first;
        lastName.value = parts.last;
        initPassportFields(form.passport_number);
    } catch { /* ignore */ }
});
</script>

<style scoped>
/* Принудительно светлая тема для нативных select (macOS dark mode fix) */
select {
    color-scheme: light;
    background-color: white;
}
</style>

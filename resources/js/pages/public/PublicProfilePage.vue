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
                            <div class="absolute -top-[6px] left-5 w-3 h-3 rotate-45 border-l border-t bg-violet-50 border-violet-200 z-10"></div>
                            <div class="absolute top-[-1px] left-[22px] w-[10px] h-[2px] bg-violet-50 z-10"></div>
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

                <!-- Предупреждение: телефон — главный ключ -->
                <div class="flex items-start gap-2 p-3 rounded-xl bg-amber-50 border border-amber-200 mt-4">
                    <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <div>
                        <p class="text-[11px] text-amber-700 leading-relaxed">{{ $t('profile.phoneWarningTitle') }}. {{ $t('profile.phoneWarningDesc') }}</p>
                    </div>
                </div>

                <!-- Телефон -->
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.phone') }}</label>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 border border-[#1BA97F] bg-[#1BA97F]/5 rounded-xl px-3 py-2.5 text-sm text-[#0A1F44] font-medium flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#1BA97F] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            {{ formatPhone(publicAuth.user?.phone) }}
                            <span class="ml-auto text-[10px] bg-[#1BA97F]/10 text-[#1BA97F] px-2 py-0.5 rounded-full font-semibold">{{ $t('profile.phoneVerified') }}</span>
                        </div>
                        <button @click="showPhoneModal = true" type="button"
                            class="shrink-0 text-xs text-[#1BA97F] hover:text-[#169B72] font-medium px-3 py-2.5 border border-[#1BA97F]/30 rounded-xl hover:bg-[#1BA97F]/5 transition-colors">
                            {{ $t('profile.changePhone') }}
                        </button>
                    </div>
                </div>

                <!-- Email для восстановления -->
                <div class="mt-3">
                    <label class="block text-xs font-medium text-amber-800 mb-1">
                        {{ $t('profile.recoveryEmail') }}
                        <span class="text-amber-600 font-normal">({{ $t('profile.recoveryEmailHint') }})</span>
                    </label>

                    <!-- Режим просмотра: email сохранён и не в режиме редактирования -->
                    <div v-if="savedEmail && !emailEditing && !emailVerifying" class="flex items-center gap-2">
                        <div class="flex-1 rounded-xl px-3 py-2.5 text-sm font-medium flex items-center gap-2"
                            :class="emailVerified
                                ? 'border border-[#1BA97F] bg-[#1BA97F]/5 text-[#0A1F44]'
                                : 'border border-amber-400 bg-amber-50 text-amber-900'">
                            <svg class="w-4 h-4 shrink-0" :class="emailVerified ? 'text-[#1BA97F]' : 'text-amber-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ savedEmail }}
                            <span v-if="emailVerified" class="ml-auto text-[10px] bg-[#1BA97F]/10 text-[#1BA97F] px-2 py-0.5 rounded-full font-semibold">{{ $t('profile.emailVerified') }}</span>
                            <span v-else class="ml-auto text-[10px] bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-semibold cursor-pointer hover:bg-amber-200" @click="resendVerification">{{ $t('profile.emailUnverified') }}</span>
                        </div>
                        <button @click="startEmailEdit" type="button"
                            class="shrink-0 text-xs text-[#1BA97F] hover:text-[#169B72] font-medium px-3 py-2.5 border border-[#1BA97F]/30 rounded-xl hover:bg-[#1BA97F]/5 transition-colors">
                            {{ $t('profile.changePhone') }}
                        </button>
                    </div>

                    <!-- Режим верификации: ввод 4-значного кода -->
                    <div v-else-if="emailVerifying" class="space-y-3">
                        <div class="bg-blue-50 border border-blue-200 rounded-xl px-4 py-3">
                            <p class="text-xs text-blue-800 font-medium mb-2">{{ $t('profile.verifyEmailHint', { email: savedEmail }) }}</p>
                            <div class="flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <input v-for="i in 4" :key="i" :ref="el => { if (el) verifyInputs[i-1] = el }"
                                        type="text" inputmode="numeric" maxlength="1"
                                        class="w-10 h-10 text-center text-lg font-bold border-2 rounded-lg outline-none transition-colors"
                                        :class="verifyCode[i-1] ? 'border-[#1BA97F] bg-white' : 'border-gray-300 bg-white'"
                                        :value="verifyCode[i-1] || ''"
                                        @input="onVerifyInput($event, i-1)"
                                        @keydown.backspace="onVerifyBackspace($event, i-1)"
                                        @paste="onVerifyPaste" />
                                </div>
                                <button @click="submitVerifyCode" :disabled="verifyCode.join('').length < 4 || emailSaving"
                                    class="shrink-0 p-2.5 rounded-xl transition-colors"
                                    :class="verifyCode.join('').length === 4
                                        ? 'bg-[#1BA97F] text-white hover:bg-[#169B72]'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                    <svg v-if="!emailSaving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </button>
                            </div>
                            <button @click="emailVerifying = false; verifyCode = ['','','',''];" class="text-[11px] text-blue-600 hover:text-blue-800 mt-2">{{ $t('common.cancel') }}</button>
                        </div>
                    </div>

                    <!-- Режим ввода/редактирования -->
                    <div v-else class="flex items-center gap-2">
                        <input v-model="emailDraft" type="text" inputmode="email" autocomplete="email" placeholder="example@gmail.com"
                            class="flex-1 border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                            :class="emailDraft
                                ? (isValidEmail(emailDraft) ? 'border-[#1BA97F] bg-white' : 'border-red-400 bg-red-50')
                                : 'border-amber-300 bg-amber-50'"
                            @keyup.enter="saveEmailField" />
                        <button @click="saveEmailField" type="button" :disabled="emailSaving || !emailDraft || !isValidEmail(emailDraft)"
                            class="shrink-0 p-2.5 rounded-xl transition-colors"
                            :class="emailDraft && isValidEmail(emailDraft)
                                ? 'bg-[#1BA97F] text-white hover:bg-[#169B72]'
                                : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                            <svg v-if="!emailSaving" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </button>
                        <button v-if="savedEmail" @click="cancelEmailEdit" type="button"
                            class="shrink-0 p-2.5 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <p v-if="emailDraft && !isValidEmail(emailDraft) && (!savedEmail || emailEditing)" class="text-[11px] text-red-500 mt-1">{{ $t('profile.invalidEmail') }}</p>
                    <p v-if="emailMsg" class="text-[11px] mt-1" :class="emailMsgError ? 'text-red-500' : 'text-[#1BA97F]'">{{ emailMsg }}</p>
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
                            class="w-16 py-2.5 text-center text-sm font-mono uppercase outline-none
                                   bg-gray-50 border-r border-gray-200 text-[#0A1F44] font-bold"
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
                        <div class="absolute -top-[6px] left-5 w-3 h-3 rotate-45 border-l border-t z-10"
                             :class="passportExpiryColor.solidBg + ' ' + passportExpiryColor.bubbleBorder"></div>
                        <div class="absolute top-[-1px] left-[22px] w-[10px] h-[2px] z-10"
                             :class="passportExpiryColor.solidBg"></div>
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
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        {{ $t('profile.educationLevel') }}
                        <span class="text-gray-400 font-normal">({{ $t('profile.educationBoost') }})</span>
                    </label>
                    <select v-model="form.education_level"
                        class="w-full border rounded-xl px-3 py-2.5 text-sm outline-none transition-colors"
                        :class="form.education_level ? 'border-[#1BA97F]' : 'border-gray-200 focus:border-[#1BA97F]'">
                        <option value="">{{ $t('common.notSpecified') }}</option>
                        <option v-for="el in educationLevels" :key="el.code" :value="el.code">{{ refLabel(el) }}</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">{{ $t('profile.educationHint') }}</p>
                </div>
                <!-- Чат-пузырь: оценка занятости -->
                <div v-if="employmentBubble" class="sm:col-span-2 relative mt-1">
                    <div class="absolute -top-[6px] left-5 w-3 h-3 rotate-45 border-l border-t z-10"
                         :class="employmentBubble.solidBg + ' ' + employmentBubble.bubbleBorder"></div>
                    <div class="absolute top-[-1px] left-[22px] w-[10px] h-[2px] z-10"
                         :class="employmentBubble.solidBg"></div>
                    <div class="relative rounded-2xl p-3.5 shadow-sm border"
                         :class="employmentBubble.bubbleBg + ' ' + employmentBubble.bubbleBorder">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0">
                                <span class="text-[8px] font-bold text-white">VB</span>
                            </div>
                            <span class="text-[10px] font-semibold" :class="employmentBubble.labelColor">VisaBor</span>
                        </div>
                        <p class="text-xs text-[#0A1F44] leading-relaxed">{{ employmentBubble.msg }}</p>
                    </div>
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
                <!-- Чат-пузырь: оценка привязанности -->
                <div v-if="familyBubble" class="sm:col-span-2 relative mt-1">
                    <div class="absolute -top-[6px] left-5 w-3 h-3 rotate-45 border-l border-t z-10"
                         :class="familyBubble.solidBg + ' ' + familyBubble.bubbleBorder"></div>
                    <div class="absolute top-[-1px] left-[22px] w-[10px] h-[2px] z-10"
                         :class="familyBubble.solidBg"></div>
                    <div class="relative rounded-2xl p-3.5 shadow-sm border"
                         :class="familyBubble.bubbleBg + ' ' + familyBubble.bubbleBorder">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0">
                                <span class="text-[8px] font-bold text-white">VB</span>
                            </div>
                            <span class="text-[10px] font-semibold" :class="familyBubble.labelColor">VisaBor</span>
                        </div>
                        <p class="text-xs text-[#0A1F44] leading-relaxed">{{ familyBubble.msg }}</p>
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
                <!-- Чат-пузырь: оценка визовой истории -->
                <div v-if="visaBubble" class="relative mt-1">
                    <div class="absolute -top-[6px] left-5 w-3 h-3 rotate-45 border-l border-t z-10"
                         :class="visaBubble.solidBg + ' ' + visaBubble.bubbleBorder"></div>
                    <div class="absolute top-[-1px] left-[22px] w-[10px] h-[2px] z-10"
                         :class="visaBubble.solidBg"></div>
                    <div class="relative rounded-2xl p-3.5 shadow-sm border"
                         :class="visaBubble.bubbleBg + ' ' + visaBubble.bubbleBorder">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#1BA97F] to-[#0d7a5c] flex items-center justify-center shrink-0">
                                <span class="text-[8px] font-bold text-white">VB</span>
                            </div>
                            <span class="text-[10px] font-semibold" :class="visaBubble.labelColor">VisaBor</span>
                        </div>
                        <p class="text-xs text-[#0A1F44] leading-relaxed">{{ visaBubble.msg }}</p>
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

        <!-- Члены семьи -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-[#0A1F44] text-sm">{{ $t('profile.familyMembersTitle') }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $t('profile.familyMembersHint') }}</p>
                </div>
                <button @click="openFamilyForm()" type="button"
                    class="shrink-0 flex items-center gap-1.5 text-xs font-semibold text-[#1BA97F] hover:text-[#169B72] px-3 py-2 border border-[#1BA97F]/30 rounded-xl hover:bg-[#1BA97F]/5 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    {{ $t('profile.addFamilyMember') }}
                </button>
            </div>

            <div class="p-5">
                <!-- Пусто -->
                <div v-if="!familyMembers.length && !familyLoading" class="text-center py-6">
                    <svg class="w-10 h-10 text-gray-200 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                    <p class="text-xs text-gray-400">{{ $t('profile.noFamilyMembers') }}</p>
                </div>

                <!-- Список членов семьи -->
                <div v-else class="space-y-3">
                    <div v-for="member in familyMembers" :key="member.id"
                        class="border border-gray-100 rounded-xl p-4 hover:border-gray-200 transition-colors">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-semibold text-sm text-[#0A1F44]">{{ member.name }}</span>
                                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full"
                                        :class="relationshipColor(member.relationship)">
                                        {{ $t('profile.rel' + capitalize(member.relationship)) }}
                                    </span>
                                    <span v-if="member.is_minor" class="text-[10px] font-medium px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">
                                        {{ $t('profile.minor') }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-400">
                                    <span v-if="member.dob">{{ member.dob }}</span>
                                    <span v-if="member.gender">{{ member.gender === 'M' ? $t('profile.male') : $t('profile.female') }}</span>
                                    <span v-if="member.passport_number">{{ member.passport_number }}</span>
                                    <span v-if="member.passport_expires_at">{{ $t('profile.memberPassportExpiry') }}: {{ member.passport_expires_at }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <button @click="openFamilyForm(member)" type="button"
                                    class="p-1.5 text-gray-400 hover:text-[#1BA97F] rounded-lg hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                </button>
                                <button @click="deleteFamilyMember(member)" type="button"
                                    class="p-1.5 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модалка смены телефона -->
    <div v-if="showPhoneModal"
        class="fixed inset-0 bg-[#0A1F44]/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="showPhoneModal = false">
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl p-6">
            <h3 class="text-base font-bold text-[#0A1F44] mb-4">{{ $t('profile.changePhone') }}</h3>

            <div v-if="!phoneOtpSent">
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.newPhone') }}</label>
                <div class="flex gap-2">
                    <span class="flex items-center px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-500 font-medium font-mono">+998</span>
                    <input :value="newPhoneDisplay" type="tel" maxlength="12" inputmode="numeric" placeholder="90 123 45 67"
                        class="flex-1 border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors font-mono tracking-wider"
                        @input="onNewPhoneInput" @keydown="onNewPhoneKeydown"/>
                </div>
                <p v-if="phoneError" class="text-xs text-red-500 mt-2">{{ phoneError }}</p>
                <button @click="sendPhoneOtp" :disabled="newPhoneDigits.length < 9 || phoneSending"
                    class="w-full mt-4 bg-[#1BA97F] hover:bg-[#169B72] disabled:opacity-50 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                    {{ phoneSending ? '...' : $t('profile.sendCode') }}
                </button>
            </div>

            <div v-else>
                <p class="text-sm text-gray-500 mb-3">{{ $t('profile.codeSent') }}: <span class="font-medium text-[#0A1F44] font-mono">+998 {{ newPhoneDisplay }}</span></p>
                <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.enterCode') }}</label>
                <div class="flex gap-2 justify-center mb-3">
                    <input v-for="i in 4" :key="i" :ref="el => { if (el) otpInputs[i-1] = el }"
                        type="text" maxlength="1" inputmode="numeric"
                        class="w-12 h-12 text-center text-lg font-bold border-2 rounded-xl outline-none transition-colors"
                        :class="otpCode[i-1] ? 'border-[#1BA97F] bg-[#1BA97F]/5' : 'border-gray-200 focus:border-[#1BA97F]'"
                        :value="otpCode[i-1] || ''"
                        @input="handleOtpInput(i-1, $event)"
                        @keydown.backspace="handleOtpBackspace(i-1, $event)"/>
                </div>
                <p v-if="phoneError" class="text-xs text-red-500 mb-3 text-center">{{ phoneError }}</p>
                <button @click="verifyPhoneOtp" :disabled="otpCode.join('').length < 4 || phoneSending"
                    class="w-full bg-[#1BA97F] hover:bg-[#169B72] disabled:opacity-50 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                    {{ phoneSending ? '...' : $t('profile.confirmChange') }}
                </button>
            </div>

            <button @click="showPhoneModal = false; phoneOtpSent = false; phoneError = ''"
                class="w-full mt-2 text-sm text-gray-400 hover:text-gray-600 py-2 transition-colors">
                {{ $t('common.cancel') }}
            </button>
        </div>
    </div>

    <!-- Модалка добавления/редактирования члена семьи -->
    <div v-if="showFamilyForm"
        class="fixed inset-0 bg-[#0A1F44]/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
        @click.self="showFamilyForm = false">
        <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-xl p-6 max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-bold text-[#0A1F44] mb-4">
                {{ editingMember ? $t('common.edit') : $t('profile.addFamilyMember') }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.memberName') }} <span class="text-red-500">*</span></label>
                    <input v-model="familyForm.name" @input="familyForm.name = familyForm.name.replace(/[^A-Za-z\s\-']/g, '').toUpperCase()"
                        :placeholder="$t('profile.firstNamePlaceholder') + ' ' + $t('profile.lastNamePlaceholder')"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.relationship') }} <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="rel in relationships" :key="rel.value" type="button"
                            @click="familyForm.relationship = rel.value"
                            :class="familyForm.relationship === rel.value ? 'border-[#1BA97F] bg-[#1BA97F]/10 text-[#0A1F44] font-semibold' : 'border-gray-100 bg-gray-50 text-gray-600 hover:border-gray-200'"
                            class="px-2 py-2 rounded-xl text-xs border transition-colors text-center">
                            {{ rel.label }}
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.memberDob') }}</label>
                        <input v-model="familyForm.dob" type="date" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.memberGender') }}</label>
                        <div class="flex gap-2">
                            <button type="button" @click="familyForm.gender = 'M'"
                                :class="familyForm.gender === 'M' ? 'bg-[#1BA97F] text-white border-[#1BA97F]' : 'bg-white text-gray-600 border-gray-200'"
                                class="flex-1 px-2 py-2.5 rounded-xl text-xs border transition-colors font-medium">{{ $t('profile.male') }}</button>
                            <button type="button" @click="familyForm.gender = 'F'"
                                :class="familyForm.gender === 'F' ? 'bg-[#1BA97F] text-white border-[#1BA97F]' : 'bg-white text-gray-600 border-gray-200'"
                                class="flex-1 px-2 py-2.5 rounded-xl text-xs border transition-colors font-medium">{{ $t('profile.female') }}</button>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.memberCitizenship') }}</label>
                    <select v-model="familyForm.citizenship"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">{{ $t('profile.selectCountry') }}</option>
                        <option value="UZ">{{ $t('countries.UZ') }}</option>
                        <option value="KZ">{{ $t('countries.KZ') }}</option>
                        <option value="KG">{{ $t('countries.KG') }}</option>
                        <option value="TJ">{{ $t('countries.TJ') }}</option>
                        <option value="TM">{{ $t('countries.TM') }}</option>
                        <option value="RU">{{ $t('countries.RU') }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.memberPassport') }}</label>
                        <input v-model="familyForm.passport_number" maxlength="20" placeholder="AA1234567"
                            @input="familyForm.passport_number = familyForm.passport_number.toUpperCase()"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors font-mono"/>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('profile.memberPassportExpiry') }}</label>
                        <input v-model="familyForm.passport_expires_at" type="date"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button @click="showFamilyForm = false" type="button"
                    class="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                    {{ $t('common.cancel') }}
                </button>
                <button @click="saveFamilyMember" :disabled="!familyForm.name || !familyForm.relationship || familySaving" type="button"
                    class="flex-1 bg-[#1BA97F] hover:bg-[#169B72] disabled:opacity-50 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                    {{ familySaving ? '...' : $t('profile.saveMember') }}
                </button>
            </div>
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
import { formatPhone } from '@/utils/format';

const { t, locale } = useI18n();
const router     = useRouter();
const publicAuth = usePublicAuthStore();
const { activeItems: refItems } = usePublicReferences();

const employmentTypes  = computed(() => refItems('employment_type'));
const maritalStatuses  = computed(() => refItems('marital_status'));
const educationLevels  = computed(() => refItems('education_level'));

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
const firstName = ref((_nameParts.first || '').toUpperCase());
const lastName  = ref((_nameParts.last || '').toUpperCase());

function isLatinOnly(val) {
    return /^[A-Za-z\s\-']+$/.test(val || '');
}

function isValidEmail(val) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val || '');
}

// --- Email: отдельная логика сохранения + верификация ---
const savedEmail     = ref(publicAuth.user?.recovery_email ?? '');
const emailVerified  = ref(!!publicAuth.user?.email_verified_at);
const emailDraft     = ref(savedEmail.value || '');
const emailEditing   = ref(false);
const emailVerifying = ref(false);
const emailSaving    = ref(false);
const emailMsg       = ref('');
const emailMsgError  = ref(false);
const verifyCode     = ref(['', '', '', '']);
const verifyInputs   = ref([]);

function startEmailEdit() {
    emailDraft.value = savedEmail.value;
    emailEditing.value = true;
    emailVerifying.value = false;
    emailMsg.value = '';
}

function cancelEmailEdit() {
    emailEditing.value = false;
    emailDraft.value = savedEmail.value;
    emailMsg.value = '';
}

async function saveEmailField() {
    if (!emailDraft.value || !isValidEmail(emailDraft.value)) return;
    emailSaving.value = true;
    emailMsg.value = '';
    emailMsgError.value = false;
    try {
        const { data } = await publicPortalApi.saveEmail(emailDraft.value);
        const updatedUser = data?.data?.user;
        if (updatedUser) {
            publicAuth.user = updatedUser;
            try { localStorage.setItem('public_user', JSON.stringify(updatedUser)); } catch {}
            form.recovery_email = updatedUser.recovery_email ?? '';
            emailVerified.value = !!updatedUser.email_verified_at;
        }
        savedEmail.value = emailDraft.value;
        emailEditing.value = false;
        // Переключаемся на ввод кода верификации
        emailVerifying.value = true;
        verifyCode.value = ['', '', '', ''];
        emailMsg.value = t('profile.verificationSent');
        setTimeout(() => { emailMsg.value = ''; }, 5000);
        nextTick(() => { if (verifyInputs.value[0]) verifyInputs.value[0].focus(); });
    } catch (e) {
        emailMsgError.value = true;
        emailMsg.value = e.response?.data?.message ?? t('profile.saveError');
    } finally {
        emailSaving.value = false;
    }
}

async function resendVerification() {
    if (!savedEmail.value) return;
    emailSaving.value = true;
    try {
        await publicPortalApi.saveEmail(savedEmail.value);
        emailVerifying.value = true;
        verifyCode.value = ['', '', '', ''];
        emailMsg.value = t('profile.verificationSent');
        emailMsgError.value = false;
        setTimeout(() => { emailMsg.value = ''; }, 5000);
        nextTick(() => { if (verifyInputs.value[0]) verifyInputs.value[0].focus(); });
    } catch (e) {
        emailMsgError.value = true;
        emailMsg.value = e.response?.data?.message ?? t('profile.saveError');
    } finally {
        emailSaving.value = false;
    }
}

function onVerifyInput(e, idx) {
    const val = e.target.value.replace(/\D/g, '');
    verifyCode.value[idx] = val.charAt(0) || '';
    e.target.value = verifyCode.value[idx];
    if (val && idx < 3 && verifyInputs.value[idx + 1]) {
        verifyInputs.value[idx + 1].focus();
    }
    if (verifyCode.value.join('').length === 4) {
        submitVerifyCode();
    }
}

function onVerifyBackspace(e, idx) {
    if (!verifyCode.value[idx] && idx > 0) {
        verifyInputs.value[idx - 1].focus();
    }
}

function onVerifyPaste(e) {
    const text = (e.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 4);
    for (let i = 0; i < 4; i++) verifyCode.value[i] = text[i] || '';
    e.preventDefault();
    if (text.length === 4) submitVerifyCode();
}

async function submitVerifyCode() {
    const code = verifyCode.value.join('');
    if (code.length < 4) return;
    emailSaving.value = true;
    emailMsg.value = '';
    emailMsgError.value = false;
    try {
        const { data } = await publicPortalApi.verifyEmail(code);
        const updatedUser = data?.data?.user;
        if (updatedUser) {
            publicAuth.user = updatedUser;
            try { localStorage.setItem('public_user', JSON.stringify(updatedUser)); } catch {}
            emailVerified.value = !!updatedUser.email_verified_at;
        }
        emailVerifying.value = false;
        emailMsg.value = t('profile.emailVerifiedSuccess');
        setTimeout(() => { emailMsg.value = ''; }, 4000);
    } catch (e) {
        emailMsgError.value = true;
        emailMsg.value = e.response?.data?.message ?? t('profile.verifyError');
        verifyCode.value = ['', '', '', ''];
        nextTick(() => { if (verifyInputs.value[0]) verifyInputs.value[0].focus(); });
    } finally {
        emailSaving.value = false;
    }
}

function onLatinInput(field, e) {
    // Убираем кириллицу и спецсимволы, оставляем только латиницу, пробелы, дефис, апостроф
    // Автоматически переводим в UPPERCASE (как в загранпаспорте)
    const clean = e.target.value.replace(/[^A-Za-z\s\-']/g, '').toUpperCase();
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
    education_level:      publicAuth.user?.education_level ?? '',
    recovery_email:       publicAuth.user?.recovery_email ?? '',
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
        solidBg: 'bg-red-50',
    };
    if (days < 180) return {
        border: 'border-red-500',
        msg: t('profile.passportCritical'),
        textClass: 'text-red-600',
        bubbleBg: 'bg-gradient-to-br from-red-50 to-rose-50',
        bubbleBorder: 'border-red-200',
        labelColor: 'text-red-500',
        solidBg: 'bg-red-50',
    };
    if (days < 365) return {
        border: 'border-amber-400',
        msg: t('profile.passportWarning'),
        textClass: 'text-amber-600',
        bubbleBg: 'bg-gradient-to-br from-amber-50 to-orange-50',
        bubbleBorder: 'border-amber-200',
        labelColor: 'text-amber-500',
        solidBg: 'bg-amber-50',
    };
    return {
        border: 'border-[#1BA97F]',
        msg: t('profile.passportGreat'),
        textClass: 'text-[#1BA97F]',
        bubbleBg: 'bg-gradient-to-br from-emerald-50 to-teal-50',
        bubbleBorder: 'border-emerald-200',
        labelColor: 'text-emerald-500',
        solidBg: 'bg-emerald-50',
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

// Стили пузырей — helper
const BUBBLE_GREEN  = { bubbleBg: 'bg-gradient-to-br from-emerald-50 to-teal-50', bubbleBorder: 'border-emerald-200', labelColor: 'text-emerald-500', solidBg: 'bg-emerald-50' };
const BUBBLE_YELLOW = { bubbleBg: 'bg-gradient-to-br from-amber-50 to-orange-50', bubbleBorder: 'border-amber-200', labelColor: 'text-amber-500', solidBg: 'bg-amber-50' };
const BUBBLE_RED    = { bubbleBg: 'bg-gradient-to-br from-red-50 to-rose-50', bubbleBorder: 'border-red-200', labelColor: 'text-red-500', solidBg: 'bg-red-50' };

// Пузырь: Занятость и доходы
const employmentBubble = computed(() => {
    const emp = form.employment_type;
    const inc = Number(form.monthly_income_usd) || 0;
    const tenure = Number(form.employed_years);
    if (!emp && !inc) return null;
    // Студент
    if (emp === 'student') return { ...BUBBLE_YELLOW, msg: t('profile.empBubbleStudent') };
    // Пенсионер
    if (emp === 'retired') return { ...BUBBLE_YELLOW, msg: t('profile.empBubbleRetired') };
    // Безработный или нет работы + маленький доход
    if (emp === 'unemployed' || (!emp && inc <= 300)) return { ...BUBBLE_RED, msg: t('profile.empBubbleLow') };
    // Есть работа
    if (emp) {
        if (inc >= 1500 && tenure >= 3) return { ...BUBBLE_GREEN, msg: t('profile.empBubbleGreat') };
        if (inc >= 800) return { ...BUBBLE_GREEN, msg: t('profile.empBubbleGood') };
        if (inc > 0 && inc < 800) return { ...BUBBLE_YELLOW, msg: t('profile.empBubbleAverage') };
    }
    // Только доход указан
    if (inc >= 1500) return { ...BUBBLE_GREEN, msg: t('profile.empBubbleGood') };
    if (inc > 0) return { ...BUBBLE_YELLOW, msg: t('profile.empBubbleAverage') };
    return null;
});

// Пузырь: Семья и привязанность
const familyBubble = computed(() => {
    const ms = form.marital_status;
    const ch = form.has_children;
    const pr = form.has_property;
    const car = form.has_car;
    if (!ms && !ch && !pr && !car) return null;
    let score = 0;
    if (ms === 'married') score += 2;
    else if (ms === 'divorced' || ms === 'widowed') score += 1;
    if (ch) score += 2;
    if (pr) score += 2;
    if (car) score += 1;
    if (score >= 5) return { ...BUBBLE_GREEN, msg: t('profile.familyBubbleGreat') };
    if (score >= 3) return { ...BUBBLE_GREEN, msg: t('profile.familyBubbleGood') };
    if (score >= 1) return { ...BUBBLE_YELLOW, msg: t('profile.familyBubbleAverage') };
    return { ...BUBBLE_RED, msg: t('profile.familyBubbleLow') };
});

// Пузырь: Визовая история
const visaBubble = computed(() => {
    const visas = form.visas_obtained_count;
    const sch = form.has_schengen_visa;
    const us = form.has_us_visa;
    const ref = form.refusals_count;
    const overstay = form.had_overstay;
    const deport = form.had_deportation;
    // Ничего не заполнено
    if (!visas && !sch && !us && !ref && !overstay && !deport) return null;
    // Нарушения — приоритет
    if (deport || overstay) return { ...BUBBLE_RED, msg: t('profile.visaBubbleViolation') };
    // Отказы
    if (ref > 0) return { ...BUBBLE_RED, msg: t('profile.visaBubbleRefusal') };
    // Шенген + США
    if (sch && us) return { ...BUBBLE_GREEN, msg: t('profile.visaBubbleGreat') };
    // Есть визы или одна сильная
    if (visas >= 3 || sch || us) return { ...BUBBLE_GREEN, msg: t('profile.visaBubbleGood') };
    if (visas >= 1) return { ...BUBBLE_GREEN, msg: t('profile.visaBubbleGood') };
    // Нет виз
    return { ...BUBBLE_YELLOW, msg: t('profile.visaBubbleFirst') };
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
    // Копируем ответы визарда в form
    if (wizardAnswers.employment_type)   form.employment_type   = wizardAnswers.employment_type;
    if (wizardAnswers.employed_years !== undefined) form.employed_years = wizardAnswers.employed_years;
    if (wizardAnswers.monthly_income_usd) form.monthly_income_usd = wizardAnswers.monthly_income_usd;
    if (wizardAnswers.marital_status)    form.marital_status    = wizardAnswers.marital_status;
    const vh = wizardAnswers.visaHistory;
    if (vh === 'schengen' || vh === 'both') form.has_schengen_visa = true;
    if (vh === 'us' || vh === 'both') form.has_us_visa = true;

    // Сохраняем ТОЛЬКО поля визарда (не весь form — там могут быть невалидные пустые поля)
    saving.value = true;
    try {
        const wizardPayload = {};
        if (wizardAnswers.employment_type)   wizardPayload.employment_type   = wizardAnswers.employment_type;
        if (wizardAnswers.employed_years !== undefined) wizardPayload.employed_years = Number(wizardAnswers.employed_years);
        if (wizardAnswers.monthly_income_usd) wizardPayload.monthly_income_usd = Number(wizardAnswers.monthly_income_usd);
        if (wizardAnswers.marital_status)    wizardPayload.marital_status    = wizardAnswers.marital_status;
        if (vh === 'schengen' || vh === 'both') wizardPayload.has_schengen_visa = true;
        if (vh === 'us' || vh === 'both') wizardPayload.has_us_visa = true;
        if (vh === 'none') { wizardPayload.has_schengen_visa = false; wizardPayload.has_us_visa = false; }

        const { data } = await publicPortalApi.updateProfile(wizardPayload);
        publicAuth.user = data.data.user;
        localStorage.setItem('public_user', JSON.stringify(data.data.user));
    } catch (e) {
        /* silent */
    } finally {
        saving.value = false;
    }

    showWizard.value = false;
    router.push({ name: 'me.scoring' });
}

async function save() {
    saving.value = true;
    saveMsg.value = '';
    saveError.value = false;
    try {
        // Собираем name из firstName + lastName
        form.name = [firstName.value, lastName.value].filter(Boolean).join(' ').trim().toUpperCase();
        const payload = { ...form };
        // Email сохраняется отдельно — убираем из общего payload
        delete payload.recovery_email;
        // Удаляем пустые строки для integer-полей (бэкенд требует integer, '' не пройдёт)
        const intFields = ['monthly_income_usd', 'employed_years', 'children_count', 'visas_obtained_count', 'refusals_count', 'last_refusal_year'];
        for (const f of intFields) {
            if (payload[f] === '' || payload[f] === null || payload[f] === undefined) delete payload[f];
        }
        // Удаляем пустые строки
        for (const [k, v] of Object.entries(payload)) {
            if (v === '') delete payload[k];
        }
        const { data } = await publicPortalApi.updateProfile(payload);
        const updatedUser = data?.data?.user;
        if (updatedUser) {
            publicAuth.user = updatedUser;
            try { localStorage.setItem('public_user', JSON.stringify(updatedUser)); } catch {}
        }
        saveMsg.value = t('profile.profileSaved');
        setTimeout(() => { saveMsg.value = ''; }, 3000);
    } catch (e) {
        saveError.value = true;
        saveMsg.value = e.response?.data?.message ?? t('profile.saveError');
    } finally {
        saving.value = false;
    }
}

// --- Смена телефона ---
const showPhoneModal = ref(false);
const newPhoneDigits = ref('');
const phoneOtpSent   = ref(false);
const phoneSending   = ref(false);
const phoneError     = ref('');
const otpCode        = ref(['', '', '', '']);
const otpInputs      = ref([]);

// Форматирование: 9 цифр → XX XXX XX XX
function formatPhoneDigits(d) {
    let r = '';
    if (d.length > 0) r += d.slice(0, 2);
    if (d.length > 2) r += ' ' + d.slice(2, 5);
    if (d.length > 5) r += ' ' + d.slice(5, 7);
    if (d.length > 7) r += ' ' + d.slice(7, 9);
    return r;
}
const newPhoneDisplay = computed(() => formatPhoneDigits(newPhoneDigits.value));

function onNewPhoneInput(e) {
    const oldVal = e.target.value;
    const cursorPos = e.target.selectionStart;
    // Считаем сколько цифр было до курсора в старом значении
    const digitsBeforeCursor = oldVal.slice(0, cursorPos).replace(/\D/g, '').length;
    const raw = oldVal.replace(/\D/g, '').slice(0, 9);
    newPhoneDigits.value = raw;
    const formatted = formatPhoneDigits(raw);
    e.target.value = formatted;
    // Находим позицию в новой строке, где столько же цифр
    let digitsSeen = 0;
    let newPos = formatted.length;
    for (let i = 0; i < formatted.length; i++) {
        if (/\d/.test(formatted[i])) digitsSeen++;
        if (digitsSeen === digitsBeforeCursor) { newPos = i + 1; break; }
    }
    e.target.setSelectionRange(newPos, newPos);
}

function onNewPhoneKeydown(e) {
    const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
    if (allowed.includes(e.key)) return;
    if (e.key >= '0' && e.key <= '9') return;
    e.preventDefault();
}

async function sendPhoneOtp() {
    phoneError.value = '';
    phoneSending.value = true;
    try {
        await publicPortalApi.changePhoneSendOtp('+998' + newPhoneDigits.value);
        phoneOtpSent.value = true;
    } catch (e) {
        phoneError.value = e.response?.data?.message || t('profile.saveError');
    } finally { phoneSending.value = false; }
}

function handleOtpInput(idx, e) {
    const val = e.target.value.replace(/\D/g, '');
    otpCode.value[idx] = val.slice(0, 1);
    e.target.value = otpCode.value[idx];
    if (val && idx < 3) nextTick(() => otpInputs.value[idx + 1]?.focus());
}

function handleOtpBackspace(idx, e) {
    if (!otpCode.value[idx] && idx > 0) {
        e.preventDefault();
        otpCode.value[idx - 1] = '';
        nextTick(() => otpInputs.value[idx - 1]?.focus());
    }
}

async function verifyPhoneOtp() {
    phoneError.value = '';
    phoneSending.value = true;
    try {
        const { data } = await publicPortalApi.changePhoneVerify('+998' + newPhoneDigits.value, otpCode.value.join(''));
        publicAuth.user = data.data.user;
        localStorage.setItem('public_user', JSON.stringify(data.data.user));
        showPhoneModal.value = false;
        phoneOtpSent.value = false;
        newPhoneDigits.value = '';
        otpCode.value = ['', '', '', ''];
    } catch (e) {
        phoneError.value = e.response?.data?.message || t('profile.saveError');
    } finally { phoneSending.value = false; }
}

// --- Члены семьи ---
const familyMembers  = ref([]);
const familyLoading  = ref(false);
const showFamilyForm = ref(false);
const familySaving   = ref(false);
const editingMember  = ref(null);
const familyForm     = reactive({ name: '', relationship: '', dob: '', gender: '', citizenship: '', passport_number: '', passport_expires_at: '' });

const relationships = computed(() => [
    { value: 'child',   label: t('profile.relChild') },
    { value: 'spouse',  label: t('profile.relSpouse') },
    { value: 'parent',  label: t('profile.relParent') },
    { value: 'sibling', label: t('profile.relSibling') },
    { value: 'other',   label: t('profile.relOther') },
]);

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

function relationshipColor(rel) {
    const map = { child: 'bg-blue-50 text-blue-600', spouse: 'bg-pink-50 text-pink-600', parent: 'bg-purple-50 text-purple-600', sibling: 'bg-teal-50 text-teal-600' };
    return map[rel] || 'bg-gray-50 text-gray-600';
}

function openFamilyForm(member = null) {
    editingMember.value = member;
    if (member) {
        Object.assign(familyForm, { name: member.name, relationship: member.relationship, dob: member.dob || '', gender: member.gender || '', citizenship: member.citizenship || '', passport_number: member.passport_number || '', passport_expires_at: member.passport_expires_at || '' });
    } else {
        Object.assign(familyForm, { name: '', relationship: '', dob: '', gender: '', citizenship: '', passport_number: '', passport_expires_at: '' });
    }
    showFamilyForm.value = true;
}

async function loadFamilyMembers() {
    familyLoading.value = true;
    try {
        const { data } = await publicPortalApi.familyMembers();
        familyMembers.value = data.data;
    } catch { /* ignore */ }
    finally { familyLoading.value = false; }
}

async function saveFamilyMember() {
    familySaving.value = true;
    try {
        const payload = { ...familyForm };
        for (const [k, v] of Object.entries(payload)) { if (v === '') delete payload[k]; }
        if (editingMember.value) {
            await publicPortalApi.updateFamilyMember(editingMember.value.id, payload);
        } else {
            await publicPortalApi.addFamilyMember(payload);
        }
        showFamilyForm.value = false;
        await loadFamilyMembers();
    } catch {
        /* silent */
    } finally { familySaving.value = false; }
}

async function deleteFamilyMember(member) {
    if (!confirm(t('profile.confirmDeleteMember'))) return;
    try {
        await publicPortalApi.deleteFamilyMember(member.id);
        await loadFamilyMembers();
    } catch { /* ignore */ }
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
        // Разбить name на firstName/lastName + UPPERCASE (как в загранпаспорте)
        const parts = splitName(form.name);
        firstName.value = (parts.first || '').toUpperCase();
        lastName.value = (parts.last || '').toUpperCase();
        initPassportFields(form.passport_number);
        // Синхронизируем email-поле
        savedEmail.value = publicAuth.user?.recovery_email ?? '';
        emailDraft.value = savedEmail.value || '';
        emailVerified.value = !!publicAuth.user?.email_verified_at;
    } catch { /* ignore */ }
    loadFamilyMembers();
});
</script>

<style scoped>
/* Принудительно светлая тема для нативных select (macOS dark mode fix) */
select {
    color-scheme: light;
    background-color: white;
}
</style>

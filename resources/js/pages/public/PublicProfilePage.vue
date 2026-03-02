<template>
    <div class="max-w-2xl mx-auto space-y-5">

        <!-- Приветственный баннер для новых пользователей -->
        <div v-if="!publicAuth.user?.name"
            class="bg-gradient-to-r from-[#0A1F44] to-[#1a3a6e] rounded-2xl p-5 sm:p-6 text-white">
            <h2 class="text-lg font-bold mb-1">Добро пожаловать!</h2>
            <p class="text-sm text-white/70 mb-4">Расскажите немного о себе — займёт 2 минуты. Это поможет рассчитать шансы на визу.</p>
            <button @click="showWizard = true"
                class="inline-flex items-center gap-2 bg-[#1BA97F] hover:bg-[#169B72] text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Быстрое заполнение (2 мин)
            </button>
        </div>

        <!-- Основные данные (паспорт) -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-[#0A1F44] text-sm">Личные данные</h3>
                    <p class="text-xs text-gray-400 mt-0.5">ФИО, дата рождения, гражданство</p>
                </div>
                <!-- Passport photo upload -->
                <label class="flex items-center gap-1.5 text-xs text-[#1BA97F] font-medium cursor-pointer hover:text-[#169B72] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="hidden sm:inline">Загрузить фото паспорта</span>
                    <span class="sm:hidden">Фото</span>
                    <input type="file" accept="image/*" class="hidden" @change="uploadPassport"/>
                </label>
            </div>

            <div v-if="ocrStatus === 'pending'" class="px-5 py-3 bg-amber-50 border-b border-amber-100 flex items-center gap-2 text-sm text-amber-700">
                <div class="w-4 h-4 border-2 border-amber-500 border-t-transparent rounded-full animate-spin shrink-0"></div>
                Распознаём данные паспорта... Обновите страницу через 30 секунд.
            </div>

            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">ФИО <span class="text-red-500">*</span></label>
                        <input v-model="form.name" placeholder="Иванов Иван Иванович"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Дата рождения <span class="text-red-500">*</span></label>
                        <input v-model="form.dob" type="date" :max="maxDob"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Гражданство <span class="text-red-500">*</span></label>
                        <select v-model="form.citizenship"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                            <option value="">Выберите страну</option>
                            <option value="UZ">🇺🇿 Узбекистан</option>
                            <option value="KZ">🇰🇿 Казахстан</option>
                            <option value="KG">🇰🇬 Кыргызстан</option>
                            <option value="TJ">🇹🇯 Таджикистан</option>
                            <option value="TM">🇹🇲 Туркменистан</option>
                            <option value="RU">🇷🇺 Россия</option>
                            <option value="UA">🇺🇦 Украина</option>
                            <option value="GE">🇬🇪 Грузия</option>
                            <option value="AZ">🇦🇿 Азербайджан</option>
                            <option value="AM">🇦🇲 Армения</option>
                            <option value="MD">🇲🇩 Молдова</option>
                            <option value="BY">🇧🇾 Беларусь</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Пол</label>
                        <div class="flex gap-2">
                            <button type="button" @click="form.gender = 'M'"
                                :class="form.gender === 'M' ? 'bg-[#0A1F44] text-white border-[#0A1F44]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                                class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                                Мужской
                            </button>
                            <button type="button" @click="form.gender = 'F'"
                                :class="form.gender === 'F' ? 'bg-[#0A1F44] text-white border-[#0A1F44]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                                class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                                Женский
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Паспорт -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">Данные паспорта</h3>
                <p class="text-xs text-gray-400 mt-0.5">Загрузите первую страницу паспорта или заполните вручную</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Серия и номер паспорта</label>
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
                    <p class="text-[11px] text-gray-400 mt-1">2 буквы серии + 7 цифр номера — например, FA1234567</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Паспорт действителен до</label>
                    <input v-model="form.passport_expires_at" type="date" :min="minPassportExpiry"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"/>
                    <p v-if="passportExpiringSoon" class="text-xs text-amber-600 mt-1">
                        Паспорт скоро истекает. Большинство стран требуют срок действия 6+ месяцев.
                    </p>
                </div>
            </div>
        </div>

        <!-- Занятость и доходы -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">Занятость и доходы</h3>
                <p class="text-xs text-gray-400 mt-0.5">Консульство оценивает стабильность занятости и финансовую состоятельность</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Статус занятости <span class="text-red-500">*</span></label>
                    <select v-model="form.employment_type"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">Не указано</option>
                        <option value="employed">Наёмный работник</option>
                        <option value="business_owner">Владелец бизнеса</option>
                        <option value="self_employed">Самозанятый / ИП</option>
                        <option value="retired">Пенсионер</option>
                        <option value="student">Студент</option>
                        <option value="unemployed">Безработный / без работы</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Стаж на текущем месте работы
                        <span class="text-gray-400 font-normal">(повышает скоринг)</span>
                    </label>
                    <select v-model="form.employed_years"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors"
                        :disabled="form.employment_type === 'unemployed' || form.employment_type === 'student'">
                        <option value="">Не указано</option>
                        <option :value="0">Менее 1 года</option>
                        <option :value="1">1–2 года</option>
                        <option :value="3">2–5 лет</option>
                        <option :value="5">5–10 лет</option>
                        <option :value="10">Более 10 лет</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Ежемесячный доход <span class="text-red-500">*</span>
                        <span class="text-gray-400 font-normal">(самый важный параметр)</span>
                    </label>
                    <select v-model="form.monthly_income_usd"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">Не указано</option>
                        <option :value="300">До $500 (до 6 млн сум)</option>
                        <option :value="800">$500–1 000 (6–12 млн сум)</option>
                        <option :value="1500">$1 000–2 000 (12–25 млн сум)</option>
                        <option :value="3000">$2 000–4 000 (25–50 млн сум)</option>
                        <option :value="5000">Более $4 000 (от 50 млн сум)</option>
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Совокупный доход семьи, включая аренду, дивиденды и т.д.</p>
                </div>
            </div>
        </div>

        <!-- Семья и привязанность к стране -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">Семья и привязанность к стране</h3>
                <p class="text-xs text-gray-400 mt-0.5">Семья, имущество и корни в стране убеждают консульство, что вы вернётесь</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Семейное положение <span class="text-red-500">*</span></label>
                    <select v-model="form.marital_status"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">Не указано</option>
                        <option value="single">Холост / не замужем</option>
                        <option value="married">Женат / замужем</option>
                        <option value="divorced">Разведён / разведена</option>
                        <option value="widowed">Вдовец / вдова</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Дети</label>
                    <div class="flex gap-2">
                        <button type="button" @click="form.has_children = false; form.children_count = 0"
                            :class="!form.has_children ? 'bg-[#0A1F44] text-white border-[#0A1F44]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                            Нет детей
                        </button>
                        <button type="button" @click="form.has_children = true; if (!form.children_count) form.children_count = 1"
                            :class="form.has_children ? 'bg-[#0A1F44] text-white border-[#0A1F44]' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="flex-1 px-3 py-2.5 rounded-xl text-sm border transition-colors font-medium">
                            Есть дети
                        </button>
                    </div>
                </div>
                <div v-if="form.has_children">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Количество детей</label>
                    <select v-model="form.children_count"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option :value="1">1 ребёнок</option>
                        <option :value="2">2 ребёнка</option>
                        <option :value="3">3 ребёнка</option>
                        <option :value="4">4 и более</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Имущество в стране проживания</label>
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
                                <span class="text-sm font-medium" :class="form.has_property ? 'text-[#0A1F44]' : 'text-gray-600'">Недвижимость</span>
                                <p class="text-[11px] text-gray-400">Квартира, дом, земля</p>
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
                                <span class="text-sm font-medium" :class="form.has_car ? 'text-[#0A1F44]' : 'text-gray-600'">Автомобиль</span>
                                <p class="text-[11px] text-gray-400">Зарегистрирован на вас</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Визовая история -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">Визовая история</h3>
                <p class="text-xs text-gray-400 mt-0.5">Прошлые визы — сильнейший положительный фактор. Отказы снижают скоринг.</p>
            </div>
            <div class="p-5 space-y-4">
                <!-- Количество полученных виз -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Сколько виз вы уже получали?</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="opt in visasObtainedOptions" :key="opt.value" type="button"
                            @click="form.visas_obtained_count = opt.value"
                            :class="form.visas_obtained_count === opt.value
                                ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="px-2 py-2.5 rounded-xl text-sm border transition-colors font-medium text-center">
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Сильные визы -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Наличие ключевых виз <span class="text-gray-400 font-normal">(значительно повышают скоринг)</span></label>
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
                                <span class="text-sm font-medium" :class="form.has_schengen_visa ? 'text-[#0A1F44]' : 'text-gray-600'">Шенгенская виза</span>
                                <p class="text-[11px] text-gray-400">Действующая или была ранее</p>
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
                                <span class="text-sm font-medium" :class="form.has_us_visa ? 'text-[#0A1F44]' : 'text-gray-600'">Виза США</span>
                                <p class="text-[11px] text-gray-400">Действующая или была ранее</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Отказы -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">
                        Отказы в визе за последние 5 лет
                        <span class="text-red-500 font-normal">(снижают скоринг)</span>
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        <button v-for="opt in refusalsOptions" :key="opt.value" type="button"
                            @click="form.refusals_count = opt.value; if (opt.value === 0) form.last_refusal_year = null"
                            :class="form.refusals_count === opt.value
                                ? (opt.value > 0 ? 'bg-red-600 text-white border-red-600' : 'bg-[#0A1F44] text-white border-[#0A1F44]')
                                : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-gray-300'"
                            class="px-2 py-2.5 rounded-xl text-sm border transition-colors font-medium text-center">
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Год последнего отказа (показывается если есть отказы) -->
                <div v-if="form.refusals_count > 0">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Год последнего отказа</label>
                    <select v-model="form.last_refusal_year"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-red-400 transition-colors">
                        <option :value="null">Не указано</option>
                        <option v-for="y in recentYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <p class="text-[11px] text-amber-600 mt-1">Отказ 3+ года назад учитывается слабее, чем недавний</p>
                </div>

                <!-- Нарушения -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Нарушения визового режима</label>
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
                                <span class="text-sm font-medium" :class="form.had_overstay ? 'text-amber-800' : 'text-gray-600'">Задерживался сверх срока визы</span>
                                <p class="text-[11px]" :class="form.had_overstay ? 'text-amber-600' : 'text-gray-400'">Остался в стране дольше разрешённого срока</p>
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
                                <span class="text-sm font-medium" :class="form.had_deportation ? 'text-red-700' : 'text-gray-600'">Был депортирован</span>
                                <p class="text-[11px]" :class="form.had_deportation ? 'text-red-500' : 'text-gray-400'">Принудительно выдворен из страны — серьёзный фактор</p>
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
                {{ saving ? 'Сохраняем...' : 'Сохранить профиль' }}
            </button>
        </div>
    </div>

    <!-- Quick Wizard Modal -->
    <div v-if="showWizard"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
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
                        Назад
                    </button>
                    <div v-else></div>

                    <button v-if="wizardStep < wizardSteps.length - 1"
                        @click="wizardStep++"
                        :disabled="!wizardAnswers[currentWizardStep.field]"
                        class="text-sm bg-[#0A1F44] text-white px-4 py-2 rounded-xl font-medium disabled:opacity-40 hover:bg-[#0d2a5e] transition-colors">
                        Далее
                    </button>
                    <button v-else
                        @click="finishWizard"
                        :disabled="!wizardAnswers[currentWizardStep.field]"
                        class="text-sm bg-[#1BA97F] text-white px-4 py-2 rounded-xl font-semibold disabled:opacity-40 hover:bg-[#169B72] transition-colors">
                        Готово — показать скоринг
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { publicPortalApi } from '@/api/public';
import { usePublicAuthStore } from '@/stores/publicAuth';

const router     = useRouter();
const publicAuth = usePublicAuthStore();

const saving    = ref(false);
const saveMsg   = ref('');
const saveError = ref(false);
const ocrStatus = ref(publicAuth.user?.ocr_status ?? null);

const showWizard = ref(false);
const wizardStep = ref(0);
const wizardAnswers = reactive({});

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

const passportExpiringSoon = computed(() => {
    if (!form.passport_expires_at) return false;
    const days = Math.floor((new Date(form.passport_expires_at) - new Date()) / 86400000);
    return days < 180;
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

const visasObtainedOptions = [
    { value: 0,  label: 'Нет' },
    { value: 1,  label: '1–2' },
    { value: 3,  label: '3–5' },
    { value: 6,  label: '6+' },
];

const refusalsOptions = [
    { value: 0,  label: 'Нет' },
    { value: 1,  label: '1' },
    { value: 2,  label: '2' },
    { value: 3,  label: '3+' },
];

const recentYears = Array.from({ length: 8 }, (_, i) => new Date().getFullYear() - i);

// Быстрый мастер — 5 шагов
const wizardSteps = [
    {
        field: 'employment_type',
        question: 'Ваша занятость?',
        hint: 'Стабильность занятости — ключевой фактор доверия консульства',
        options: [
            { value: 'employed',       icon: '💼', label: 'Работаю по найму' },
            { value: 'business_owner', icon: '🏢', label: 'Владелец бизнеса' },
            { value: 'self_employed',  icon: '🛠', label: 'Самозанятый / ИП' },
            { value: 'student',        icon: '🎓', label: 'Студент' },
            { value: 'retired',        icon: '🏖', label: 'Пенсионер' },
            { value: 'unemployed',     icon: '🔍', label: 'Не работаю' },
        ],
    },
    {
        field: 'employed_years',
        question: 'Стаж на текущем месте?',
        hint: 'Долгий стаж означает стабильность — консульства это ценят',
        options: [
            { value: 0,  icon: '🆕', label: 'Менее 1 года' },
            { value: 1,  icon: '📅', label: '1–2 года' },
            { value: 3,  icon: '📈', label: '2–5 лет' },
            { value: 5,  icon: '🏆', label: '5–10 лет' },
            { value: 10, icon: '🥇', label: 'Более 10 лет' },
        ],
    },
    {
        field: 'monthly_income_usd',
        question: 'Ваш доход в месяц?',
        hint: 'Совокупный доход — самый важный финансовый параметр',
        options: [
            { value: 300,  icon: '💵', label: 'До $500' },
            { value: 800,  icon: '💵', label: '$500 – $1 000' },
            { value: 1500, icon: '💰', label: '$1 000 – $2 000' },
            { value: 3000, icon: '💰', label: '$2 000 – $4 000' },
            { value: 5000, icon: '💎', label: 'Более $4 000' },
        ],
    },
    {
        field: 'marital_status',
        question: 'Семейное положение?',
        hint: 'Семья в стране — доказательство намерения вернуться',
        options: [
            { value: 'single',   icon: '👤', label: 'Холост / не замужем' },
            { value: 'married',  icon: '👫', label: 'Женат / замужем' },
            { value: 'divorced', icon: '📄', label: 'Разведён / разведена' },
            { value: 'widowed',  icon: '🕊', label: 'Вдовец / вдова' },
        ],
    },
    {
        field: 'visaHistory',
        question: 'Шенген или виза США?',
        hint: 'Наличие этих виз резко повышает шансы во всех консульствах',
        options: [
            { value: 'none',    icon: '🆕', label: 'Ещё не было' },
            { value: 'schengen',icon: '🇪🇺', label: 'Есть шенгенская' },
            { value: 'us',      icon: '🇺🇸', label: 'Есть виза США' },
            { value: 'both',    icon: '✈️',  label: 'Обе визы' },
        ],
    },
];

const currentWizardStep = computed(() => wizardSteps[wizardStep.value]);

function selectWizardAnswer(value) {
    wizardAnswers[currentWizardStep.value.field] = value;
    // Автопереход на следующий шаг через небольшую задержку
    if (wizardStep.value < wizardSteps.length - 1) {
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
        const payload = { ...form };
        if (!payload.monthly_income_usd) delete payload.monthly_income_usd;
        const { data } = await publicPortalApi.updateProfile(payload);
        publicAuth.user = data.data.user;
        localStorage.setItem('public_user', JSON.stringify(data.data.user));
        saveMsg.value = 'Профиль сохранён';
        setTimeout(() => { saveMsg.value = ''; }, 3000);
    } catch (e) {
        saveError.value = true;
        saveMsg.value = e.response?.data?.message ?? 'Ошибка при сохранении';
    } finally {
        saving.value = false;
    }
}

async function uploadPassport(e) {
    const file = e.target.files[0];
    if (!file) return;
    try {
        await publicPortalApi.uploadPassport(file);
        ocrStatus.value = 'pending';
    } catch {
        // ignore
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
        initPassportFields(form.passport_number);
        ocrStatus.value = publicAuth.user?.ocr_status ?? null;
    } catch { /* ignore */ }
});
</script>

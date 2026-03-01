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
                    <input v-model="form.passport_number" placeholder="AB1234567" maxlength="20"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] font-mono transition-colors"/>
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

        <!-- Занятость и доход (ключевые для скоринга) -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">Занятость и доход</h3>
                <p class="text-xs text-gray-400 mt-0.5">Влияют на скоринг — консульство оценивает финансовую состоятельность</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Занятость</label>
                    <select v-model="form.employment_type"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">Не указано</option>
                        <option value="employed">Наёмный работник</option>
                        <option value="business_owner">Владелец бизнеса</option>
                        <option value="self_employed">Самозанятый / ИП</option>
                        <option value="retired">Пенсионер</option>
                        <option value="student">Студент</option>
                        <option value="unemployed">Безработный</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Доход в месяц</label>
                    <select v-model="form.monthly_income_usd"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">Не указано</option>
                        <option :value="300">До $500</option>
                        <option :value="800">$500 – 1 000</option>
                        <option :value="1500">$1 000 – 2 000</option>
                        <option :value="3000">$2 000 – 4 000</option>
                        <option :value="5000">Более $4 000</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Семейное положение</label>
                    <select v-model="form.marital_status"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] transition-colors">
                        <option value="">Не указано</option>
                        <option value="single">Холост / не замужем</option>
                        <option value="married">Женат / замужем</option>
                        <option value="divorced">Разведён / разведена</option>
                        <option value="widowed">Вдовец / вдова</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Имущество и визовая история -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
            <div class="px-5 py-4 border-b border-gray-50">
                <h3 class="font-bold text-[#0A1F44] text-sm">Имущество и визовая история</h3>
                <p class="text-xs text-gray-400 mt-0.5">Наличие имущества и предыдущие визы повышают шансы</p>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <label v-for="cb in checkboxItems" :key="cb.key"
                        class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition-colors"
                        :class="form[cb.key] ? 'border-[#1BA97F]/40 bg-[#1BA97F]/5' : 'border-gray-100 bg-gray-50 hover:bg-gray-100'">
                        <input type="checkbox" v-model="form[cb.key]" class="sr-only"/>
                        <div class="w-4 h-4 rounded border-2 flex items-center justify-center shrink-0 transition-colors"
                             :class="form[cb.key] ? 'bg-[#1BA97F] border-[#1BA97F]' : 'border-gray-300'">
                            <svg v-if="form[cb.key]" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span class="text-sm leading-tight" :class="form[cb.key] ? 'text-[#0A1F44] font-medium' : 'text-gray-600'">{{ cb.label }}</span>
                    </label>
                </div>

                <!-- Отказ в визе — предупреждение -->
                <div v-if="form.had_visa_refusal"
                    class="mt-3 p-3 bg-amber-50 rounded-xl text-xs text-amber-700">
                    Отказы влияют на скоринг. Укажите это в запросе к агентству — специалисты помогут улучшить профиль.
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
import { ref, computed, reactive, onMounted } from 'vue';
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
    name:               publicAuth.user?.name ?? '',
    dob:                publicAuth.user?.dob ?? '',
    citizenship:        publicAuth.user?.citizenship ?? '',
    gender:             publicAuth.user?.gender ?? '',
    passport_number:    publicAuth.user?.passport_number ?? '',
    passport_expires_at: publicAuth.user?.passport_expires_at?.slice(0, 10) ?? '',
    employment_type:    publicAuth.user?.employment_type ?? '',
    monthly_income_usd: publicAuth.user?.monthly_income_usd ?? '',
    marital_status:     publicAuth.user?.marital_status ?? '',
    has_children:       !!publicAuth.user?.has_children,
    has_property:       !!publicAuth.user?.has_property,
    has_car:            !!publicAuth.user?.has_car,
    has_schengen_visa:  !!publicAuth.user?.has_schengen_visa,
    has_us_visa:        !!publicAuth.user?.has_us_visa,
    had_visa_refusal:   !!publicAuth.user?.had_visa_refusal,
    had_overstay:       !!publicAuth.user?.had_overstay,
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

const checkboxItems = [
    { key: 'has_children',     label: 'Есть дети' },
    { key: 'has_property',     label: 'Есть недвижимость' },
    { key: 'has_car',          label: 'Есть автомобиль' },
    { key: 'has_schengen_visa',label: 'Шенгенская виза' },
    { key: 'has_us_visa',      label: 'Виза США' },
    { key: 'had_visa_refusal', label: 'Был отказ в визе' },
];

// Быстрый мастер — 4 шага
const wizardSteps = [
    {
        field: 'employment_type',
        question: 'Ваша занятость?',
        hint: 'Консульство оценивает стабильность занятости',
        options: [
            { value: 'employed',       icon: '💼', label: 'Работаю по найму' },
            { value: 'business_owner', icon: '🏢', label: 'Владелец бизнеса' },
            { value: 'self_employed',  icon: '🛠', label: 'Самозанятый / ИП' },
            { value: 'student',        icon: '🎓', label: 'Студент' },
            { value: 'retired',        icon: '🏖', label: 'Пенсионер' },
            { value: 'unemployed',     icon: '🔍', label: 'Безработный' },
        ],
    },
    {
        field: 'monthly_income_usd',
        question: 'Ваш доход в месяц?',
        hint: 'Финансы — самый важный фактор скоринга',
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
        hint: 'Семейные связи показывают намерение вернуться',
        options: [
            { value: 'single',   icon: '👤', label: 'Холост / не замужем' },
            { value: 'married',  icon: '👫', label: 'Женат / замужем' },
            { value: 'divorced', icon: '📄', label: 'Разведён / разведена' },
            { value: 'widowed',  icon: '🕊', label: 'Вдовец / вдова' },
        ],
    },
    {
        field: 'visaHistory',
        question: 'Есть ли у вас визы?',
        hint: 'Шенген и США значительно повышают шансы',
        options: [
            { value: 'none',    icon: '🆕', label: 'Ещё нет виз' },
            { value: 'schengen',icon: '🇪🇺', label: 'Есть шенгенская виза' },
            { value: 'us',      icon: '🇺🇸', label: 'Есть американская виза' },
            { value: 'both',    icon: '✈️',  label: 'Есть и та, и другая' },
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
    // Применяем ответы мастера к форме
    if (wizardAnswers.employment_type) form.employment_type = wizardAnswers.employment_type;
    if (wizardAnswers.monthly_income_usd) form.monthly_income_usd = wizardAnswers.monthly_income_usd;
    if (wizardAnswers.marital_status) form.marital_status = wizardAnswers.marital_status;
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

onMounted(async () => {
    // Обновляем данные профиля из API
    try {
        await publicAuth.fetchMe();
        Object.keys(form).forEach(key => {
            const val = publicAuth.user?.[key];
            if (val !== undefined && val !== null && val !== '') {
                form[key] = key === 'passport_expires_at' ? val?.slice(0, 10) ?? '' : val;
            }
        });
        ocrStatus.value = publicAuth.user?.ocr_status ?? null;
    } catch { /* ignore */ }
});
</script>

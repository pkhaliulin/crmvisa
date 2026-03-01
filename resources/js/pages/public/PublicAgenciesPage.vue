<template>
    <div class="min-h-0">
        <div class="max-w-3xl mx-auto">

            <!-- Хедер страницы -->
            <div class="mb-5 sm:mb-6">
                <button @click="router.back()"
                    class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Назад к скорингу
                </button>

                <h1 class="text-xl sm:text-2xl font-bold text-[#0A1F44]">
                    <span v-if="countryCode">{{ countryFlag(countryCode) }} Агентства для {{ countryName(countryCode) }}</span>
                    <span v-else>Агентства</span>
                </h1>
                <p class="text-gray-500 text-sm mt-0.5">Выберите агентство и отправьте заявку</p>
            </div>

            <!-- Фильтр по типу визы -->
            <div class="flex gap-2 mb-5 overflow-x-auto pb-1 scrollbar-hide">
                <button v-for="vt in visaTypes" :key="vt.value"
                    @click="selectedVisaType = vt.value"
                    class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium border transition-colors"
                    :class="selectedVisaType === vt.value
                        ? 'bg-[#0A1F44] text-white border-[#0A1F44]'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                    {{ vt.label }}
                </button>
            </div>

            <!-- Загрузка -->
            <div v-if="loading" class="space-y-4">
                <div v-for="i in 3" :key="i"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-6 animate-pulse">
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
                <div class="font-semibold text-[#0A1F44] mb-1">Агентств не найдено</div>
                <p class="text-sm text-gray-500">
                    По выбранным параметрам агентств пока нет. Попробуйте другой тип визы.
                </p>
            </div>

            <!-- Список агентств -->
            <div v-else class="space-y-4">
                <div v-for="agency in agencies" :key="agency.id"
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

                    <!-- Агентство: заголовок -->
                    <div class="p-5 sm:p-6 pb-4">
                        <div class="flex items-start gap-4 mb-3">
                            <!-- Логотип / инициалы -->
                            <div class="shrink-0 w-14 h-14 rounded-xl overflow-hidden border border-gray-100 flex items-center justify-center bg-gray-50">
                                <img v-if="agency.logo_url" :src="agency.logo_url" :alt="agency.name"
                                    class="w-full h-full object-cover">
                                <span v-else class="text-xl font-bold text-gray-300">
                                    {{ agency.name?.[0]?.toUpperCase() }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="font-bold text-[#0A1F44] text-base">{{ agency.name }}</h2>
                                    <span v-if="agency.is_verified"
                                        class="flex items-center gap-1 text-xs text-[#1BA97F] bg-[#1BA97F]/10 px-2 py-0.5 rounded-full font-medium shrink-0">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                        Верифицировано
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-sm text-gray-400 flex-wrap">
                                    <span v-if="agency.city">{{ agency.city }}</span>
                                    <span v-if="agency.experience_years" class="flex items-center gap-1">
                                        {{ agency.experience_years }} лет опыта
                                    </span>
                                    <!-- Рейтинг -->
                                    <span v-if="agency.rating" class="flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        {{ Number(agency.rating).toFixed(1) }}
                                        <span v-if="agency.reviews_count" class="text-gray-300">({{ agency.reviews_count }})</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-if="agency.description" class="text-sm text-gray-500 leading-relaxed line-clamp-2">
                            {{ agency.description }}
                        </p>
                    </div>

                    <!-- Пакеты услуг -->
                    <div class="border-t border-gray-50">
                        <div class="px-5 sm:px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wide">
                            Пакеты услуг
                        </div>
                        <div class="divide-y divide-gray-50">
                            <div v-for="pkg in agency.packages" :key="pkg.id"
                                class="px-5 sm:px-6 py-4 flex items-start gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[#0A1F44] text-sm mb-1">{{ pkg.name }}</div>
                                    <p v-if="pkg.description" class="text-xs text-gray-400 mb-2 line-clamp-2">
                                        {{ pkg.description }}
                                    </p>
                                    <!-- Услуги пакета -->
                                    <div v-if="pkg.services?.length" class="flex flex-wrap gap-1.5 mb-2">
                                        <span v-for="svc in pkg.services.slice(0, 5)" :key="svc.name"
                                            class="text-xs bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full">
                                            {{ svc.name }}
                                        </span>
                                        <span v-if="pkg.services.length > 5"
                                            class="text-xs text-gray-400 px-2 py-0.5">
                                            +{{ pkg.services.length - 5 }} ещё
                                        </span>
                                    </div>
                                    <!-- Срок -->
                                    <div v-if="pkg.processing_days" class="text-xs text-gray-400">
                                        Срок: {{ pkg.processing_days }} рабочих дней
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="font-bold text-[#0A1F44] text-base">
                                        {{ pkg.price ? `$${Number(pkg.price).toLocaleString()}` : 'По запросу' }}
                                    </div>
                                    <button @click="openConfirm(agency, pkg)"
                                        class="mt-2 px-4 py-2 bg-[#1BA97F] hover:bg-[#17956f] active:scale-[0.97]
                                               text-white text-xs font-semibold rounded-lg transition-all">
                                        Выбрать
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
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
                        <h3 class="text-base font-bold text-[#0A1F44]">Подтверждение заявки</h3>
                        <button @click="confirm.show = false"
                            class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Краткое резюме -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-400 shrink-0">Агентство</span>
                            <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.agency?.name }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-400 shrink-0">Страна</span>
                            <span class="font-semibold text-[#0A1F44]">
                                {{ countryFlag(countryCode) }} {{ countryName(countryCode) }}
                            </span>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-400 shrink-0">Пакет</span>
                            <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.pkg?.name }}</span>
                        </div>
                        <div v-if="confirm.pkg?.price" class="flex items-start justify-between gap-3">
                            <span class="text-gray-400 shrink-0">Стоимость</span>
                            <span class="font-bold text-[#0A1F44]">${{ Number(confirm.pkg.price).toLocaleString() }}</span>
                        </div>
                    </div>

                    <p class="text-xs text-gray-400 mb-5 leading-relaxed">
                        Агентство получит вашу контактную информацию и свяжется с вами.
                        Заявка появится в разделе «Мои заявки».
                    </p>

                    <div class="flex gap-3">
                        <button @click="confirm.show = false"
                            class="flex-1 py-3 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl
                                   hover:bg-gray-50 transition-colors">
                            Отмена
                        </button>
                        <button @click="submitLead"
                            :disabled="submitting"
                            class="flex-1 py-3 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold
                                   rounded-xl transition-colors disabled:opacity-60 flex items-center justify-center gap-2">
                            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ submitting ? 'Отправляем...' : 'Отправить заявку' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast успеха -->
        <div v-if="toast"
            class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] max-w-sm w-[calc(100%-2rem)]
                   bg-[#1BA97F] text-white rounded-2xl shadow-xl px-5 py-4 flex items-start gap-3">
            <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
            <div>
                <div class="font-semibold text-sm">Заявка отправлена!</div>
                <div class="text-xs text-white/80 mt-0.5">{{ toast }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { publicPortalApi } from '@/api/public';
import { countryName as getCountryName, codeToFlag } from '@/utils/countries';

const route  = useRoute();
const router = useRouter();

const countryCode = computed(() => (route.query.country_code ?? '').toUpperCase());

const agencies = ref([]);
const loading  = ref(false);
const selectedVisaType = ref('');
const submitting = ref(false);
const toast = ref('');

const confirm = ref({ show: false, agency: null, pkg: null });

const visaTypes = [
    { value: '',          label: 'Все типы' },
    { value: 'tourist',   label: 'Туристическая' },
    { value: 'business',  label: 'Бизнес' },
    { value: 'student',   label: 'Студенческая' },
    { value: 'work',      label: 'Рабочая' },
    { value: 'transit',   label: 'Транзитная' },
];

function countryName(code) { return getCountryName(code) ?? code; }
function countryFlag(code) { return codeToFlag(code); }

async function loadAgencies() {
    if (!countryCode.value) return;
    loading.value = true;
    try {
        const params = { country_code: countryCode.value };
        if (selectedVisaType.value) params.visa_type = selectedVisaType.value;
        const res = await publicPortalApi.agencies(params);
        agencies.value = res.data.data?.agencies ?? [];
    } catch {
        agencies.value = [];
    } finally {
        loading.value = false;
    }
}

function openConfirm(agency, pkg) {
    confirm.value = { show: true, agency, pkg };
}

async function submitLead() {
    submitting.value = true;
    try {
        await publicPortalApi.submitLead({
            agency_id:    confirm.value.agency.id,
            country_code: countryCode.value,
            visa_type:    confirm.value.pkg?.visa_type || selectedVisaType.value || 'tourist',
            package_id:   confirm.value.pkg?.id ?? null,
        });
        confirm.value.show = false;
        toast.value = `Агентство ${confirm.value.agency?.name ?? ''} получило вашу заявку`;
        setTimeout(() => {
            toast.value = '';
            router.push({ name: 'me.cases' });
        }, 2500);
    } catch (e) {
        const msg = e?.response?.data?.message;
        if (e?.response?.status === 409) {
            toast.value = 'Заявка в это агентство уже отправлена';
            confirm.value.show = false;
            setTimeout(() => { toast.value = ''; }, 3000);
        } else {
            alert(msg ?? 'Ошибка отправки. Попробуйте ещё раз.');
        }
    } finally {
        submitting.value = false;
    }
}

watch(selectedVisaType, loadAgencies);
onMounted(loadAgencies);
</script>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<template>
    <div class="min-h-0">
        <div class="max-w-2xl mx-auto">

            <!-- Назад -->
            <button @click="router.back()"
                class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors mb-5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                Назад к агентствам
            </button>

            <!-- Загрузка -->
            <div v-if="loading" class="space-y-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 animate-pulse">
                    <div class="flex items-start gap-4 mb-5">
                        <div class="w-20 h-20 bg-gray-100 rounded-2xl shrink-0"></div>
                        <div class="flex-1 space-y-3">
                            <div class="h-6 bg-gray-100 rounded w-56"></div>
                            <div class="h-4 bg-gray-50 rounded w-36"></div>
                        </div>
                    </div>
                    <div class="h-4 bg-gray-50 rounded mb-2"></div>
                    <div class="h-4 bg-gray-50 rounded w-3/4"></div>
                </div>
            </div>

            <!-- Не найдено -->
            <div v-else-if="!agency"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-10 text-center">
                <div class="text-4xl mb-3">🏢</div>
                <div class="font-semibold text-[#0A1F44] mb-1">Агентство не найдено</div>
                <p class="text-sm text-gray-500">Возможно, оно было удалено или недоступно.</p>
                <button @click="router.push({ name: 'me.agencies' })"
                    class="mt-4 px-5 py-2.5 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl hover:bg-[#0d2a5e] transition-colors">
                    Все агентства
                </button>
            </div>

            <template v-else>
                <!-- Карточка агентства -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-4">
                    <div class="flex items-start gap-4 mb-4">
                        <!-- Логотип -->
                        <div class="shrink-0 w-20 h-20 rounded-2xl overflow-hidden border border-gray-100 flex items-center justify-center bg-gray-50">
                            <img v-if="agency.logo_url" :src="agency.logo_url" :alt="agency.name"
                                class="w-full h-full object-cover">
                            <span v-else class="text-3xl font-bold text-gray-200">
                                {{ agency.name?.[0]?.toUpperCase() }}
                            </span>
                        </div>
                        <!-- Название и статус -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <h1 class="text-xl font-bold text-[#0A1F44]">{{ agency.name }}</h1>
                                <span v-if="agency.is_verified"
                                    class="flex items-center gap-1 text-xs text-[#1BA97F] bg-[#1BA97F]/10 px-2 py-0.5 rounded-full font-medium shrink-0">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    Верифицировано
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-400 flex-wrap">
                                <span v-if="agency.city">{{ agency.city }}</span>
                                <span v-if="agency.experience_years">{{ agency.experience_years }} лет опыта</span>
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

                    <!-- Описание -->
                    <p v-if="agency.description" class="text-sm text-gray-600 leading-relaxed mb-4">
                        {{ agency.description }}
                    </p>

                    <!-- Контакты -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-sm">
                        <a v-if="agency.phone" :href="`tel:${agency.phone}`"
                            class="flex items-center gap-2 text-gray-600 hover:text-[#0A1F44] transition-colors">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ agency.phone }}
                        </a>
                        <a v-if="agency.email" :href="`mailto:${agency.email}`"
                            class="flex items-center gap-2 text-gray-600 hover:text-[#0A1F44] transition-colors">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ agency.email }}
                        </a>
                        <span v-if="agency.address" class="flex items-center gap-2 text-gray-500">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ agency.address }}
                        </span>
                        <a v-if="agency.website" :href="agency.website" target="_blank" rel="noopener noreferrer"
                            class="flex items-center gap-2 text-[#1BA97F] hover:underline transition-colors">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Сайт агентства
                        </a>
                    </div>
                </div>

                <!-- Пакеты услуг -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-4">
                    <div class="px-6 py-4 border-b border-gray-50">
                        <h2 class="font-bold text-[#0A1F44] text-base">Пакеты услуг</h2>
                    </div>

                    <!-- Нет пакетов -->
                    <div v-if="!agency.packages?.length" class="px-6 py-8 text-center">
                        <div class="text-sm text-gray-500 mb-1">Агентство принимает заявки.</div>
                        <div class="text-xs text-gray-400">Подробности уточняйте напрямую.</div>
                        <button @click="openConfirm(agency, null)"
                            class="mt-4 px-5 py-2.5 bg-[#1BA97F] hover:bg-[#17956f] text-white text-sm font-semibold rounded-xl transition-colors">
                            Отправить заявку
                        </button>
                    </div>

                    <!-- Список пакетов -->
                    <div v-else class="divide-y divide-gray-50">
                        <div v-for="pkg in agency.packages" :key="pkg.id" class="p-6">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[#0A1F44] text-sm mb-1">{{ pkg.name }}</div>
                                    <!-- Мета: страна, тип визы -->
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <span v-if="pkg.country_code"
                                            class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full">
                                            {{ pkg.country_code }}
                                        </span>
                                        <span v-if="pkg.visa_type"
                                            class="text-xs bg-purple-50 text-purple-700 px-2 py-0.5 rounded-full">
                                            {{ pkg.visa_type }}
                                        </span>
                                        <span v-if="pkg.processing_days"
                                            class="text-xs bg-gray-50 text-gray-500 px-2 py-0.5 rounded-full">
                                            {{ pkg.processing_days }} дней
                                        </span>
                                    </div>
                                    <p v-if="pkg.description" class="text-xs text-gray-400 leading-relaxed mb-2">
                                        {{ pkg.description }}
                                    </p>
                                    <!-- Услуги пакета -->
                                    <div v-if="pkg.services?.length" class="flex flex-wrap gap-1.5">
                                        <span v-for="svc in pkg.services" :key="svc.name ?? svc.id"
                                            class="text-xs bg-gray-50 border border-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                                            {{ svc.name }}
                                        </span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="font-bold text-[#0A1F44] text-lg">
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
            </template>
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

                    <div class="bg-gray-50 rounded-xl p-4 mb-4 space-y-2.5 text-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-gray-400 shrink-0">Агентство</span>
                            <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.agency?.name }}</span>
                        </div>
                        <div v-if="confirm.pkg" class="flex items-start justify-between gap-3">
                            <span class="text-gray-400 shrink-0">Пакет</span>
                            <span class="font-semibold text-[#0A1F44] text-right">{{ confirm.pkg.name }}</span>
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
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { publicPortalApi } from '@/api/public';

const route  = useRoute();
const router = useRouter();

const agencyId = route.params.id;

const agency     = ref(null);
const loading    = ref(true);
const submitting = ref(false);
const toast      = ref('');

const confirm = ref({ show: false, agency: null, pkg: null });

async function load() {
    loading.value = true;
    try {
        // Запрашиваем список с фильтром по agency_id — берём первый результат
        const res = await publicPortalApi.agencies({ agency_id: agencyId });
        const list = res.data.data?.agencies ?? [];
        agency.value = list[0] ?? null;
    } catch {
        agency.value = null;
    } finally {
        loading.value = false;
    }
}

function openConfirm(ag, pkg) {
    confirm.value = { show: true, agency: ag, pkg };
}

async function submitLead() {
    submitting.value = true;
    try {
        await publicPortalApi.submitLead({
            agency_id:    confirm.value.agency.id,
            country_code: confirm.value.pkg?.country_code ?? '',
            visa_type:    confirm.value.pkg?.visa_type ?? 'tourist',
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

onMounted(load);
</script>

<style scoped>
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

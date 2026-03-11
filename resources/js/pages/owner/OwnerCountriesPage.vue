<template>
    <div class="space-y-5">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold text-[#0A1F44]">{{ $t('countriesPage.title') }}</h1>
                <p class="text-sm text-gray-500">{{ $t('countriesPage.subtitle') }}</p>
            </div>
            <button @click="openAdd = true"
                class="px-4 py-2 bg-[#0A1F44] text-white text-sm font-semibold rounded-xl
                       hover:bg-[#0d2a5e] transition-colors">
                + {{ $t('countriesPage.addCountry') }}
            </button>
        </div>

        <!-- Поиск + Фильтры + Сортировка -->
        <div class="flex items-center gap-4 flex-wrap">
            <!-- Поиск -->
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input v-model="search" :placeholder="$t('countriesPage.search')"
                    class="w-full pl-9 pr-3 py-2 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#1BA97F]" />
            </div>

            <!-- Фильтр по континенту -->
            <SearchSelect v-model="filterContinent" compact
                :items="continentOptions" allow-all :all-label="$t('countriesPage.allContinents')" />

            <!-- Фильтр по визовому режиму -->
            <SearchSelect v-model="filterRegime" compact
                :items="regimeFilterOptions" allow-all :all-label="$t('countriesPage.allRegimes')" />

            <!-- Фильтр по статусу -->
            <SearchSelect v-model="filterStatus" compact
                :items="filterStatusOptions" allow-all :all-label="$t('countriesPage.allStatuses')" />

            <!-- Сортировка -->
            <SearchSelect v-model="sortBy" compact
                :items="sortOptions" />
        </div>

        <!-- Таблица стран -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">{{ $t('countriesPage.country') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countriesPage.continent') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countriesPage.visaRegime') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countryDetail.minIncome') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countriesPage.riskLevel') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countriesPage.views') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countriesPage.casesCount') }}</th>
                        <th class="px-3 py-3 text-center">{{ $t('countryDetail.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 5" :key="i">
                        <td colspan="8" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-full"></div>
                        </td>
                    </tr>
                    <tr v-else-if="!filteredCountries.length">
                        <td colspan="8" class="px-5 py-8 text-center text-gray-400">{{ $t('countriesPage.noResults') }}</td>
                    </tr>
                    <tr v-else v-for="c in filteredCountries" :key="c.country_code"
                        @click="goToDetail(c.country_code)"
                        class="hover:bg-blue-50/50 transition-colors cursor-pointer">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ c.flag_emoji }}</span>
                                <div>
                                    <div class="font-medium text-gray-800">{{ c.name }}</div>
                                    <div class="text-xs text-gray-400">{{ c.country_code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 text-center text-xs text-gray-500">{{ c.continent || '---' }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full"
                                :class="regimeClass(c.visa_regime)">
                                {{ regimeLabel(c.visa_regime) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center text-gray-600">${{ c.min_monthly_income_usd }}</td>
                        <td class="px-3 py-3 text-center">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium"
                                :class="riskClass(c.risk_level)">
                                {{ riskLabel(c.risk_level) }}
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center text-gray-500">{{ c.view_count || 0 }}</td>
                        <td class="px-3 py-3 text-center text-gray-500">{{ c.case_count || 0 }}</td>
                        <td class="px-3 py-3 text-center" @click.stop>
                            <button @click="toggleActive(c)"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none"
                                :class="c.is_active ? 'bg-[#1BA97F]' : 'bg-gray-300'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200"
                                    :class="c.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-100">
                    <tr>
                        <td class="px-5 py-2 text-xs text-gray-400">
                            {{ $t('countriesPage.total') }}: {{ filteredCountries.length }} {{ $t('countriesPage.ofTotal') }} {{ countries.length }}
                        </td>
                        <td colspan="7"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Модалка добавления страны -->
        <div v-if="openAdd" class="fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="font-bold text-[#0A1F44] text-lg mb-4">{{ $t('countriesPage.addCountry') }}</h3>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countriesPage.codeLabel') }}</label>
                            <input v-model="addForm.country_code" maxlength="2" placeholder="DE"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F] uppercase"
                                @input="addForm.country_code = addForm.country_code.toUpperCase()" />
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-1 block">{{ $t('countriesPage.flagLabel') }}</label>
                            <input v-model="addForm.flag_emoji" placeholder="flag"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">{{ $t('countryDetail.name') }} *</label>
                        <input v-model="addForm.name" :placeholder="$t('countriesPage.namePlaceholder')"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:border-[#1BA97F]" />
                    </div>
                    <p v-if="addError" class="text-xs text-red-600">{{ addError }}</p>
                </div>
                <div class="flex gap-3 mt-5">
                    <button @click="addCountry" :disabled="saving"
                        class="flex-1 py-2.5 bg-[#0A1F44] text-white font-semibold rounded-xl hover:bg-[#0d2a5e] disabled:opacity-60">
                        {{ saving ? $t('common.loading') : $t('common.add') }}
                    </button>
                    <button @click="openAdd = false; addError = ''"
                        class="px-5 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50">
                        {{ $t('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const router = useRouter();

const countries = ref([]);
const loading   = ref(true);
const saving    = ref(false);
const openAdd   = ref(false);
const addError  = ref('');
const addForm   = ref({ country_code: '', name: '', flag_emoji: '' });

// Фильтры и поиск
const search          = ref('');
const filterContinent = ref('');
const filterRegime    = ref('');
const filterStatus    = ref('');
const sortBy          = ref('name');

const filterStatusOptions = computed(() => [
    { value: 'active', label: t('countryDetail.active') },
    { value: 'inactive', label: t('countryDetail.inactive') },
]);

const sortOptions = computed(() => [
    { value: 'name', label: t('countriesPage.sortName') },
    { value: 'views', label: t('countriesPage.sortViews') },
    { value: 'cases', label: t('countriesPage.sortCases') },
    { value: 'leads', label: t('countriesPage.sortLeads') },
]);

const regimeFilterOptions = computed(() => [
    { value: 'visa_required',   label: t('countriesPage.visaRequired') },
    { value: 'visa_free',       label: t('countriesPage.visaFree') },
    { value: 'visa_on_arrival', label: t('countriesPage.visaOnArrival') },
    { value: 'evisa',           label: t('countriesPage.evisa') },
]);

const continentOptions = computed(() => [
    { value: 'Europe', label: t('countriesPage.europe') },
    { value: 'Asia', label: t('countriesPage.asia') },
    { value: 'North America', label: t('countriesPage.northAmerica') },
    { value: 'South America', label: t('countriesPage.southAmerica') },
    { value: 'Africa', label: t('countriesPage.africa') },
    { value: 'Oceania', label: t('countriesPage.oceania') },
]);

const filteredCountries = computed(() => {
    let list = [...countries.value];

    // Поиск
    if (search.value.length >= 2) {
        const q = search.value.toLowerCase();
        list = list.filter(c =>
            c.name?.toLowerCase().includes(q) ||
            c.country_code?.toLowerCase().includes(q)
        );
    }

    // Фильтр по континенту
    if (filterContinent.value) {
        list = list.filter(c => c.continent === filterContinent.value);
    }

    // Фильтр по визовому режиму
    if (filterRegime.value) {
        list = list.filter(c => c.visa_regime === filterRegime.value);
    }

    // Фильтр по статусу
    if (filterStatus.value === 'active') {
        list = list.filter(c => c.is_active);
    } else if (filterStatus.value === 'inactive') {
        list = list.filter(c => !c.is_active);
    }

    // Сортировка
    list.sort((a, b) => {
        if (sortBy.value === 'views') return (b.view_count || 0) - (a.view_count || 0);
        if (sortBy.value === 'cases') return (b.case_count || 0) - (a.case_count || 0);
        if (sortBy.value === 'leads') return (b.lead_count || 0) - (a.lead_count || 0);
        return (a.name || '').localeCompare(b.name || '', 'ru');
    });

    return list;
});

function regimeLabel(regime) {
    const map = {
        visa_required: t('countriesPage.visaRequired'),
        visa_free: t('countriesPage.visaFree'),
        visa_on_arrival: t('countriesPage.visaOnArrival'),
        evisa: t('countriesPage.evisa'),
    };
    return map[regime] || regime || '---';
}

function regimeClass(regime) {
    if (regime === 'visa_free') return 'bg-green-50 text-green-700';
    if (regime === 'visa_on_arrival') return 'bg-yellow-50 text-yellow-700';
    if (regime === 'evisa') return 'bg-blue-50 text-blue-700';
    return 'bg-gray-100 text-gray-500';
}

function riskLabel(level) {
    if (level === 'low') return t('countryDetail.riskLow');
    if (level === 'high') return t('countryDetail.riskHigh');
    return t('countryDetail.riskMedium');
}

function riskClass(level) {
    if (level === 'low') return 'bg-green-50 text-green-700';
    if (level === 'high') return 'bg-red-50 text-red-700';
    return 'bg-yellow-50 text-yellow-700';
}

function goToDetail(code) {
    router.push({ name: 'owner.country.detail', params: { code } });
}

async function toggleActive(c) {
    try {
        await api.patch(`/owner/countries/${c.country_code}`, { is_active: !c.is_active });
        c.is_active = !c.is_active;
    } catch { /* ignore */ }
}

async function load() {
    loading.value = true;
    try {
        const { data } = await api.get('/owner/countries');
        countries.value = data.data;
    } finally {
        loading.value = false;
    }
}

async function addCountry() {
    addError.value = '';
    if (!addForm.value.country_code || addForm.value.country_code.length !== 2) {
        addError.value = t('countriesPage.codeError'); return;
    }
    if (!addForm.value.name) {
        addError.value = t('countriesPage.nameError'); return;
    }
    saving.value = true;
    try {
        await api.post('/owner/countries', addForm.value);
        openAdd.value = false;
        addForm.value = { country_code: '', name: '', flag_emoji: '' };
        load();
    } catch (err) {
        addError.value = err.response?.data?.message ?? t('common.error');
    } finally {
        saving.value = false;
    }
}

onMounted(load);
</script>

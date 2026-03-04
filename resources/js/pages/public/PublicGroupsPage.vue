<template>
    <div class="space-y-5">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-[#0A1F44]">{{ $t('group.title') }}</h2>
                <p class="text-sm text-gray-400 mt-0.5">{{ $t('group.subtitle') }}</p>
            </div>
            <button @click="showCreate = true"
                class="flex items-center gap-2 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ $t('group.create') }}
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-16">
            <div class="w-8 h-8 border-2 border-[#1BA97F] border-t-transparent rounded-full animate-spin"></div>
        </div>

        <!-- Groups list -->
        <template v-else-if="groups.length">
            <button v-for="g in groups" :key="g.id"
                @click="router.push({ name: 'me.groups.show', params: { id: g.id } })"
                class="w-full text-left bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden
                       hover:border-[#1BA97F]/30 hover:shadow-md active:scale-[0.99] transition-all cursor-pointer">
                <div class="px-5 py-4 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl shrink-0">{{ codeToFlag(g.country_code) }}</span>
                        <div class="min-w-0">
                            <div class="font-bold text-[#0A1F44] text-sm leading-tight">
                                {{ g.name || $t('group.unnamed') }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ countryName(g.country_code) }} — {{ g.visa_type }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                            :class="statusBadge(g.status)">
                            {{ $t('group.status_' + g.status) }}
                        </span>
                        <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 flex items-center gap-4 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ $t('group.membersCount', { count: g.members_count }) }}
                    </span>
                    <span v-if="g.is_initiator" class="text-[#1BA97F] font-medium">{{ $t('group.youAreInitiator') }}</span>
                    <span class="ml-auto text-gray-300">{{ g.created_at }}</span>
                </div>
            </button>
        </template>

        <!-- Empty state -->
        <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 sm:p-10 text-center">
            <div class="w-16 h-16 bg-[#0A1F44]/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-[#0A1F44]/40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="font-bold text-[#0A1F44] text-base mb-2">{{ $t('group.emptyTitle') }}</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-sm mx-auto">{{ $t('group.emptyDesc') }}</p>
            <button @click="showCreate = true"
                class="inline-flex items-center gap-2 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white px-6 py-3 rounded-xl text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ $t('group.create') }}
            </button>
        </div>

        <!-- Create Group Modal -->
        <div v-if="showCreate"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="showCreate = false">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-base font-bold text-[#0A1F44]">{{ $t('group.createTitle') }}</h3>
                        <button @click="showCreate = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mb-5">{{ $t('group.createDesc') }}</p>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('group.groupName') }}</label>
                            <input v-model="form.name" type="text"
                                :placeholder="$t('group.groupNamePlaceholder')"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('portal.destinationCountry') }}</label>
                            <select v-model="form.country_code"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white">
                                <option value="">{{ $t('profile.selectCountry') }}</option>
                                <option value="DE">{{ $t('countries.DE') }}</option>
                                <option value="ES">{{ $t('countries.ES') }}</option>
                                <option value="FR">{{ $t('countries.FR') }}</option>
                                <option value="IT">{{ $t('countries.IT') }}</option>
                                <option value="PL">{{ $t('countries.PL') }}</option>
                                <option value="CZ">{{ $t('countries.CZ') }}</option>
                                <option value="GB">{{ $t('countries.GB') }}</option>
                                <option value="US">{{ $t('countries.US') }}</option>
                                <option value="CA">{{ $t('countries.CA') }}</option>
                                <option value="KR">{{ $t('countries.KR') }}</option>
                                <option value="AE">{{ $t('countries.AE') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('portal.visaTypeLabel') }}</label>
                            <select v-model="form.visa_type"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white">
                                <option value="">{{ $t('portal.selectType') }}</option>
                                <option value="tourist">{{ $t('portal.tourist') }}</option>
                                <option value="business">{{ $t('portal.business') }}</option>
                                <option value="student">{{ $t('portal.studentVisa') }}</option>
                                <option value="work">{{ $t('portal.work') }}</option>
                                <option value="transit">{{ $t('portal.transit') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('group.paymentStrategy') }}</label>
                            <select v-model="form.payment_strategy"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors bg-white">
                                <option value="individual">{{ $t('group.paymentIndividual') }}</option>
                                <option value="initiator_pays">{{ $t('group.paymentInitiator') }}</option>
                            </select>
                        </div>
                        <div v-if="createError" class="text-xs text-red-500">{{ createError }}</div>
                        <button @click="createGroup"
                            :disabled="creating || !form.country_code || !form.visa_type"
                            class="w-full py-3 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-sm font-semibold rounded-xl
                                   transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="creating" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ creating ? $t('group.creating') : $t('group.create') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';

const { t } = useI18n();
const router = useRouter();
const loading = ref(true);
const groups = ref([]);
const showCreate = ref(false);
const creating = ref(false);
const createError = ref('');

const form = ref({
    name: '',
    country_code: '',
    visa_type: '',
    payment_strategy: 'individual',
});

function countryName(code) {
    return t(`countries.${code}`) !== `countries.${code}` ? t(`countries.${code}`) : code;
}

function statusBadge(status) {
    const map = {
        forming:   'bg-blue-50 text-blue-600',
        active:    'bg-green-50 text-green-700',
        completed: 'bg-gray-100 text-gray-600',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
}

async function createGroup() {
    if (!form.value.country_code || !form.value.visa_type) return;
    creating.value = true;
    createError.value = '';
    try {
        const { data } = await publicPortalApi.createGroup(form.value);
        const groupId = data?.data?.group?.id;
        showCreate.value = false;
        form.value = { name: '', country_code: '', visa_type: '', payment_strategy: 'individual' };
        if (groupId) {
            router.push({ name: 'me.groups.show', params: { id: groupId } });
        } else {
            await loadGroups();
        }
    } catch (e) {
        createError.value = e?.response?.data?.message ?? t('group.createError');
    } finally {
        creating.value = false;
    }
}

async function loadGroups() {
    try {
        const { data } = await publicPortalApi.groups();
        groups.value = data.data ?? [];
    } catch {
        groups.value = [];
    } finally {
        loading.value = false;
    }
}

onMounted(loadGroups);
</script>

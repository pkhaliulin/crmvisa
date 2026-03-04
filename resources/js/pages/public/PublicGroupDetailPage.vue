<template>
    <div class="space-y-4">

        <!-- Назад -->
        <button @click="router.push({ name: 'me.groups' })"
            class="flex items-center gap-1.5 text-sm text-gray-400 hover:text-[#0A1F44] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $t('group.myGroups') }}
        </button>

        <!-- Loading -->
        <div v-if="loading" class="space-y-4">
            <div v-for="i in 3" :key="i" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 animate-pulse">
                <div class="h-5 bg-gray-100 rounded w-48 mb-3"></div>
                <div class="h-4 bg-gray-50 rounded w-full mb-2"></div>
                <div class="h-4 bg-gray-50 rounded w-3/4"></div>
            </div>
        </div>

        <template v-else-if="groupData">

            <!-- Header card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 pt-5 pb-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ codeToFlag(groupData.group.country_code) }}</span>
                            <div>
                                <h2 class="text-lg font-bold text-[#0A1F44]">{{ groupData.group.name || $t('group.unnamed') }}</h2>
                                <div class="text-sm text-gray-400">
                                    {{ countryName(groupData.group.country_code) }} — {{ groupData.group.visa_type }}
                                </div>
                            </div>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                            :class="statusBadge(groupData.group.status)">
                            {{ $t('group.status_' + groupData.group.status) }}
                        </span>
                    </div>
                </div>
                <!-- Stats -->
                <div class="px-5 py-3 border-t border-gray-50 flex items-center gap-6 text-xs">
                    <span class="text-gray-500">
                        {{ $t('group.membersCount', { count: groupData.stats.total_members }) }}
                    </span>
                    <span v-if="groupData.stats.invited > 0" class="text-amber-500">
                        {{ $t('group.waitingCount', { count: groupData.stats.invited }) }}
                    </span>
                    <span v-if="groupData.stats.all_paid" class="text-[#1BA97F] font-medium">
                        {{ $t('group.allPaid') }}
                    </span>
                    <span class="ml-auto text-gray-300">{{ groupData.group.created_at }}</span>
                </div>
            </div>

            <!-- Agency card -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $t('group.agency') }}</div>
                <div v-if="groupData.group.agency" class="flex items-center gap-3">
                    <img v-if="groupData.group.agency.logo_url" :src="groupData.group.agency.logo_url"
                        class="w-10 h-10 rounded-xl object-cover" :alt="groupData.group.agency.name">
                    <div v-else class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 text-sm font-bold">
                        {{ (groupData.group.agency.name || '?')[0] }}
                    </div>
                    <div>
                        <div class="font-semibold text-[#0A1F44] text-sm">{{ groupData.group.agency.name }}</div>
                        <div v-if="groupData.group.agency.city" class="text-xs text-gray-400">{{ groupData.group.agency.city }}</div>
                    </div>
                    <div v-if="groupData.group.agency.rating" class="ml-auto text-sm font-bold text-amber-500">
                        {{ groupData.group.agency.rating }}
                    </div>
                </div>
                <div v-else>
                    <p class="text-sm text-gray-400 mb-3">{{ $t('group.noAgencyYet') }}</p>
                    <button v-if="isInitiator" @click="goChooseAgency"
                        class="w-full py-2.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-xs font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                        </svg>
                        {{ $t('group.chooseAgency') }}
                    </button>
                </div>
            </div>

            <!-- Members -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 pt-4 pb-3 flex items-center justify-between">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ $t('group.members') }}</div>
                    <button v-if="isInitiator" @click="showAddMember = true"
                        class="flex items-center gap-1.5 text-xs font-semibold text-[#1BA97F] hover:text-[#158a65] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('group.addMember') }}
                    </button>
                </div>

                <div v-for="m in groupData.members" :key="m.id"
                    class="px-5 py-3 border-t border-gray-50 flex items-center gap-3 cursor-pointer hover:bg-gray-50/50 transition-colors"
                    @click="isInitiator && m.case_id ? openMemberDetail(m) : null">
                    <!-- Avatar -->
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold shrink-0"
                        :class="m.status === 'joined' ? 'bg-[#1BA97F]/10 text-[#1BA97F]' : 'bg-gray-100 text-gray-400'">
                        {{ (m.name || '?')[0]?.toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-[#0A1F44] text-sm truncate">{{ m.name || $t('group.unknown') }}</span>
                            <span v-if="m.role === 'initiator'" class="text-[10px] font-semibold text-[#1BA97F] bg-[#1BA97F]/10 px-1.5 py-0.5 rounded-full">
                                {{ $t('group.initiator') }}
                            </span>
                        </div>
                        <div class="text-xs text-gray-400">{{ m.phone_masked }}</div>
                    </div>
                    <!-- Status & progress -->
                    <div class="flex items-center gap-3 shrink-0" @click.stop>
                        <div class="text-right">
                            <div class="text-xs font-medium"
                                :class="m.status === 'joined' ? 'text-[#1BA97F]' : 'text-amber-500'">
                                {{ $t('group.memberStatus_' + m.status) }}
                            </div>
                            <div v-if="m.docs_total > 0" class="text-[10px] text-gray-400 mt-0.5">
                                {{ $t('group.docsProgress', { uploaded: m.docs_uploaded, total: m.docs_total }) }}
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full"
                            :class="m.payment_status === 'paid' ? 'bg-green-50 text-green-700' : m.payment_status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400'">
                            {{ $t('group.payment_' + (m.payment_status || 'unpaid')) }}
                        </span>
                        <!-- Remove button -->
                        <button v-if="isInitiator && m.role !== 'initiator'"
                            @click="confirmRemove(m)"
                            class="text-gray-300 hover:text-red-500 transition-colors p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Payment section (initiator_pays) -->
            <div v-if="isInitiator && groupData.group.payment_strategy === 'initiator_pays' && groupData.group.agency && !groupData.stats.all_paid"
                class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">{{ $t('group.payment') }}</div>
                <p class="text-sm text-gray-500 mb-4">{{ $t('group.payForAllDesc') }}</p>
                <div class="flex items-center gap-2">
                    <button @click="payForGroup('click')"
                        :disabled="paying"
                        class="flex-1 py-2.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">
                        Click
                    </button>
                    <button @click="payForGroup('payme')"
                        :disabled="paying"
                        class="flex-1 py-2.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">
                        Payme
                    </button>
                    <button @click="payForGroup('uzum')"
                        :disabled="paying"
                        class="flex-1 py-2.5 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-xs font-semibold rounded-xl transition-colors disabled:opacity-50">
                        Uzum
                    </button>
                </div>
            </div>

        </template>

        <!-- Add Member Modal -->
        <div v-if="showAddMember"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="showAddMember = false">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-1">
                        <h3 class="text-base font-bold text-[#0A1F44]">{{ $t('group.addMemberTitle') }}</h3>
                        <button @click="showAddMember = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mb-5">{{ $t('group.addMemberDesc') }}</p>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('group.phone') }}</label>
                            <input v-model="memberForm.phone" type="tel" placeholder="+998 90 123 45 67"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $t('group.memberName') }}</label>
                            <input v-model="memberForm.name" type="text" :placeholder="$t('group.memberNamePlaceholder')"
                                class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-[#0A1F44] focus:outline-none focus:border-[#1BA97F] transition-colors">
                        </div>
                        <div v-if="addMemberError" class="text-xs text-red-500">{{ addMemberError }}</div>
                        <button @click="doAddMember"
                            :disabled="addingMember || !memberForm.phone"
                            class="w-full py-3 bg-[#0A1F44] hover:bg-[#0d2a5e] text-white text-sm font-semibold rounded-xl
                                   transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <svg v-if="addingMember" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ addingMember ? $t('group.adding') : $t('group.addMember') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove Confirmation Modal -->
        <div v-if="removeTarget"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="removeTarget = null">
            <div class="bg-white w-full sm:max-w-sm sm:rounded-2xl rounded-t-2xl shadow-xl p-6">
                <h3 class="text-base font-bold text-[#0A1F44] mb-2">{{ $t('common.confirmDeleteTitle') }}</h3>
                <p class="text-sm text-gray-500 mb-5">{{ $t('group.removeMemberConfirm', { name: removeTarget.name || removeTarget.phone_masked }) }}</p>
                <div class="flex gap-3">
                    <button @click="removeTarget = null"
                        class="flex-1 py-2.5 border border-gray-200 text-gray-600 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                        {{ $t('common.cancel') }}
                    </button>
                    <button @click="doRemoveMember"
                        :disabled="removing"
                        class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors disabled:opacity-50">
                        {{ $t('common.confirmDeleteBtn') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Member Detail Modal -->
        <div v-if="memberDetail"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="memberDetail = null">
            <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-xl max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-[#0A1F44]">{{ memberDetail.member?.name || $t('group.unknown') }}</h3>
                        <button @click="memberDetail = null" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div v-if="memberDetailLoading" class="flex justify-center py-8">
                        <div class="w-6 h-6 border-2 border-[#1BA97F] border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <template v-else>
                        <!-- Case info -->
                        <div v-if="memberDetail.case" class="space-y-3 mb-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ $t('group.memberDetailStage') }}</span>
                                <span class="font-medium text-[#0A1F44]">{{ stageLabel(memberDetail.case.stage) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ $t('group.memberDetailPayment') }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full"
                                    :class="memberDetail.case.payment_status === 'paid' ? 'bg-green-50 text-green-700' : memberDetail.case.payment_status === 'pending' ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-400'">
                                    {{ $t('group.payment_' + (memberDetail.case.payment_status || 'unpaid')) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">{{ $t('group.memberDetailCreated') }}</span>
                                <span class="text-gray-600">{{ memberDetail.case.created_at }}</span>
                            </div>
                        </div>

                        <!-- Checklist -->
                        <div v-if="memberDetail.checklist?.length" class="border-t border-gray-100 pt-3">
                            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">{{ $t('group.memberDetailDocs') }}</div>
                            <div v-for="doc in memberDetail.checklist" :key="doc.id"
                                class="flex items-center justify-between py-1.5 text-sm">
                                <span class="text-[#0A1F44] truncate">{{ doc.name }}</span>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full shrink-0"
                                    :class="doc.status === 'approved' ? 'bg-green-50 text-green-700' : doc.status === 'uploaded' ? 'bg-blue-50 text-blue-600' : 'bg-gray-50 text-gray-400'">
                                    {{ $t('group.docStatus_' + doc.status) }}
                                </span>
                            </div>
                        </div>

                        <div v-if="!memberDetail.case" class="text-center py-4 text-sm text-gray-400">
                            {{ $t('group.memberNoCaseYet') }}
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Agency selection modal (reuses case agencies pattern) -->
        <div v-if="showAgencyPicker"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center sm:p-4"
            @click.self="showAgencyPicker = false">
            <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-xl max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-[#0A1F44]">{{ $t('group.chooseAgency') }}</h3>
                        <button @click="showAgencyPicker = false" class="text-gray-400 hover:text-gray-600 transition-colors p-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div v-if="agenciesLoading" class="flex justify-center py-8">
                        <div class="w-6 h-6 border-2 border-[#1BA97F] border-t-transparent rounded-full animate-spin"></div>
                    </div>
                    <div v-else-if="agencies.length" class="space-y-2">
                        <button v-for="a in agencies" :key="a.id" @click="selectAgency(a.id)"
                            class="w-full text-left p-4 rounded-xl border border-gray-100 hover:border-[#1BA97F]/30 hover:shadow-sm transition-all">
                            <div class="flex items-center gap-3">
                                <img v-if="a.logo_url" :src="a.logo_url" class="w-10 h-10 rounded-xl object-cover" :alt="a.name">
                                <div v-else class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 text-sm font-bold">
                                    {{ (a.name || '?')[0] }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-[#0A1F44] text-sm">{{ a.name }}</div>
                                    <div class="text-xs text-gray-400">{{ a.city }}</div>
                                </div>
                                <div v-if="a.package" class="text-right shrink-0">
                                    <div class="text-sm font-bold text-[#0A1F44]">{{ a.package.price }} {{ a.package.currency }}</div>
                                    <div v-if="a.package.processing_days" class="text-[10px] text-gray-400">{{ a.package.processing_days }} {{ $t('group.days') }}</div>
                                </div>
                            </div>
                        </button>
                    </div>
                    <div v-else class="text-center py-8 text-sm text-gray-400">{{ $t('group.noAgencies') }}</div>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { usePublicAuthStore } from '@/stores/publicAuth';
import { publicPortalApi } from '@/api/public';
import { codeToFlag } from '@/utils/countries';

const { t } = useI18n();
const router = useRouter();
const route = useRoute();
const publicAuth = usePublicAuthStore();
const loading = ref(true);
const groupData = ref(null);

// Add member
const showAddMember = ref(false);
const addingMember = ref(false);
const addMemberError = ref('');
const memberForm = ref({ phone: '', name: '' });

// Remove member
const removeTarget = ref(null);
const removing = ref(false);

// Agency picker
const showAgencyPicker = ref(false);
const agenciesLoading = ref(false);
const agencies = ref([]);

// Member detail
const memberDetail = ref(null);
const memberDetailLoading = ref(false);

// Payment
const paying = ref(false);

const isInitiator = computed(() => {
    if (!groupData.value || !publicAuth.user) return false;
    const members = groupData.value.members || [];
    return members.some(m => m.role === 'initiator');
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

async function loadGroup() {
    try {
        const { data } = await publicPortalApi.groupDetail(route.params.id);
        groupData.value = data.data;
    } catch {
        router.push({ name: 'me.groups' });
    } finally {
        loading.value = false;
    }
}

async function doAddMember() {
    if (!memberForm.value.phone) return;
    addingMember.value = true;
    addMemberError.value = '';
    try {
        await publicPortalApi.addGroupMember(route.params.id, memberForm.value);
        showAddMember.value = false;
        memberForm.value = { phone: '', name: '' };
        await loadGroup();
    } catch (e) {
        addMemberError.value = e?.response?.data?.message ?? t('group.addMemberError');
    } finally {
        addingMember.value = false;
    }
}

function confirmRemove(member) {
    removeTarget.value = member;
}

async function doRemoveMember() {
    if (!removeTarget.value) return;
    removing.value = true;
    try {
        await publicPortalApi.removeGroupMember(route.params.id, removeTarget.value.id);
        removeTarget.value = null;
        await loadGroup();
    } catch {
        // ignore
    } finally {
        removing.value = false;
    }
}

function stageLabel(stage) {
    const key = `kanban.stage_${stage}`;
    const val = t(key);
    return val !== key ? val : stage;
}

async function openMemberDetail(member) {
    memberDetail.value = { member: { id: member.id, name: member.name } };
    memberDetailLoading.value = true;
    try {
        const { data } = await publicPortalApi.memberCaseDetail(route.params.id, member.id);
        memberDetail.value = data.data;
    } catch {
        memberDetail.value = null;
    } finally {
        memberDetailLoading.value = false;
    }
}

async function goChooseAgency() {
    showAgencyPicker.value = true;
    agenciesLoading.value = true;
    try {
        const { data } = await publicPortalApi.groupAgencies(route.params.id);
        agencies.value = data.data?.agencies ?? [];
    } catch {
        agencies.value = [];
    } finally {
        agenciesLoading.value = false;
    }
}

async function selectAgency(agencyId) {
    try {
        await publicPortalApi.setGroupAgency(route.params.id, agencyId);
        showAgencyPicker.value = false;
        await loadGroup();
    } catch (e) {
        alert(e?.response?.data?.message ?? t('common.error'));
    }
}

async function payForGroup(provider) {
    paying.value = true;
    try {
        const { data } = await publicPortalApi.payForGroup(route.params.id, provider);
        const url = data?.data?.payment_url;
        if (url && url !== '#') {
            window.open(url, '_blank');
        }
        await loadGroup();
    } catch {
        // ignore
    } finally {
        paying.value = false;
    }
}

onMounted(loadGroup);
</script>

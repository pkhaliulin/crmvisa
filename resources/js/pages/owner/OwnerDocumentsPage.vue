<template>
    <div class="space-y-5">

        <!-- Toolbar -->
        <div class="flex items-center gap-3">
            <SearchSelect v-model="filterCat" @change="load" compact
                :items="categories" allow-all :all-label="t('owner.documents.allCategories')" />
            <span class="text-sm text-gray-400">{{ t('owner.documents.templatesCount', { count: docs.length }) }}</span>
            <button @click="showCreateModal = true"
                class="ml-auto flex items-center gap-2 bg-[#1BA97F] text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-[#169B72] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                {{ t('owner.documents.addDocument') }}
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 border-b border-gray-100 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left">{{ t('owner.documents.document') }}</th>
                        <th class="px-4 py-3 text-left">{{ t('owner.documents.category') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.type') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.aiColumn') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.countriesCount') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="6" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else-if="!docs.length">
                        <td colspan="6" class="px-5 py-10 text-center text-gray-400 text-sm">
                            {{ t('owner.documents.noTemplates') }}
                        </td>
                    </tr>
                    <tr v-else v-for="d in docs" :key="d.id"
                        class="hover:bg-gray-50 transition-colors cursor-pointer"
                        @click="$router.push({ name: 'owner.documents.edit', params: { id: d.id } })">
                        <td class="px-5 py-3">
                            <div class="font-medium text-gray-800">{{ d.name }}</div>
                            <div class="text-xs text-gray-400 font-mono">{{ d.slug }}</div>
                            <div v-if="d.description" class="text-xs text-gray-400 mt-0.5 line-clamp-1">{{ d.description }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">
                                {{ catLabel(d.category) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs px-2 py-1 rounded-full"
                                :class="d.type === 'upload' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'">
                                {{ d.type === 'upload' ? t('owner.documents.file') : t('owner.documents.checkbox') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="d.ai_enabled ? 'text-green-600' : 'text-gray-300'" class="text-xs font-medium">
                                {{ d.ai_enabled ? 'ON' : 'OFF' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-gray-700">{{ d.country_requirements_count ?? 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span :class="d.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'"
                                class="text-xs px-2 py-1 rounded-full">
                                {{ d.is_active ? t('owner.documents.active') : t('owner.documents.disabled') }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create modal (minimal: slug + name + category + type) -->
        <div v-if="showCreateModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">{{ t('owner.documents.newTemplate') }}</h3>
                    <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form @submit.prevent="doCreate" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.slug') }} <span class="text-red-500">*</span></label>
                        <input v-model="createForm.slug" required placeholder="passport_copy"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] font-mono"/>
                        <p class="text-xs text-gray-400 mt-1">{{ t('owner.documents.slugHint') }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.name') }} <span class="text-red-500">*</span></label>
                        <input v-model="createForm.name" required placeholder="Копия паспорта"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.categoryLabel') }} <span class="text-red-500">*</span></label>
                            <SearchSelect v-model="createForm.category" required
                                :items="categories" allow-all :all-label="t('owner.documents.select')" />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.typeLabel') }} <span class="text-red-500">*</span></label>
                            <SearchSelect v-model="createForm.type" required :items="typeOptions" />
                        </div>
                    </div>
                    <p v-if="createError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl">{{ createError }}</p>
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="showCreateModal = false"
                            class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" :disabled="creating"
                            class="flex-1 bg-[#1BA97F] text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-[#169B72] transition-colors disabled:opacity-60">
                            {{ creating ? t('owner.documents.saving') : t('owner.documents.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';
import api from '@/api/index';
import SearchSelect from '@/components/SearchSelect.vue';

const { t } = useI18n();
const router = useRouter();

const docs      = ref([]);
const loading   = ref(true);
const filterCat = ref('');

const showCreateModal = ref(false);
const creating        = ref(false);
const createError     = ref('');
const createForm      = ref({ slug: '', name: '', category: '', type: 'upload' });

const categories = computed(() => [
    { value: 'personal',     label: t('owner.documents.catPersonal') },
    { value: 'financial',    label: t('owner.documents.catFinancial') },
    { value: 'family',       label: t('owner.documents.catFamily') },
    { value: 'property',     label: t('owner.documents.catProperty') },
    { value: 'travel',       label: t('owner.documents.catTravel') },
    { value: 'employment',   label: t('owner.documents.catEmployment') },
    { value: 'confirmation', label: t('owner.documents.catConfirmation') },
    { value: 'other',        label: t('owner.documents.catOther') },
]);

const typeOptions = computed(() => [
    { value: 'upload', label: t('owner.documents.fileUpload') },
    { value: 'checkbox', label: t('owner.documents.checkbox') },
]);

const catLabelsMap = computed(() => Object.fromEntries(categories.value.map(c => [c.value, c.label])));
const catLabel  = (v) => catLabelsMap.value[v] ?? v;

async function load() {
    loading.value = true;
    try {
        const params = { active_only: false };
        if (filterCat.value) params.category = filterCat.value;
        const { data } = await api.get('/admin/document-templates', { params });
        docs.value = data.data;
    } finally {
        loading.value = false;
    }
}

async function doCreate() {
    creating.value   = true;
    createError.value = '';
    try {
        const { data } = await api.post('/admin/document-templates', {
            slug:     createForm.value.slug,
            name:     createForm.value.name,
            category: createForm.value.category,
            type:     createForm.value.type,
            is_active: true,
            sort_order: 0,
        });
        showCreateModal.value = false;
        createForm.value = { slug: '', name: '', category: '', type: 'upload' };
        // Navigate to the new document's edit page
        const created = data.data;
        router.push({ name: 'owner.documents.edit', params: { id: created.id } });
    } catch (e) {
        createError.value = e.response?.data?.message ?? t('owner.documents.saveError');
    } finally {
        creating.value = false;
    }
}

onMounted(load);
</script>

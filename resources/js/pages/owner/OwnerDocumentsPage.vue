<template>
    <div class="space-y-5">

        <!-- Toolbar -->
        <div class="flex items-center gap-3">
            <select v-model="filterCat" @change="load"
                class="border border-gray-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-[#1BA97F]">
                <option value="">{{ t('owner.documents.allCategories') }}</option>
                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <span class="text-sm text-gray-400">{{ t('owner.documents.templatesCount', { count: docs.length }) }}</span>
            <button @click="openCreate"
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
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.repeatable') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.countriesCount') }}</th>
                        <th class="px-4 py-3 text-center">{{ t('owner.documents.status') }}</th>
                        <th class="px-4 py-3 text-right">{{ t('owner.documents.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr v-if="loading" v-for="i in 8" :key="i">
                        <td colspan="7" class="px-5 py-4">
                            <div class="h-4 bg-gray-100 rounded animate-pulse w-3/4"></div>
                        </td>
                    </tr>
                    <tr v-else-if="!docs.length">
                        <td colspan="7" class="px-5 py-10 text-center text-gray-400 text-sm">
                            {{ t('owner.documents.noTemplates') }}
                        </td>
                    </tr>
                    <tr v-else v-for="d in docs" :key="d.id" class="hover:bg-gray-50 transition-colors">
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
                            <span class="text-xs" :class="d.is_repeatable ? 'text-green-600' : 'text-gray-300'">
                                {{ d.is_repeatable ? t('common.yes') : t('common.no') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-gray-700">{{ d.country_requirements_count ?? 0 }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button @click="toggleActive(d)"
                                :class="d.is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
                                class="text-xs px-2 py-1 rounded-full transition-colors cursor-pointer">
                                {{ d.is_active ? t('owner.documents.active') : t('owner.documents.disabled') }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="openEdit(d)"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                    {{ t('common.edit') }}
                                </button>
                                <button @click="confirmDelete(d)"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium transition-colors">
                                    {{ t('common.delete') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create / Edit modal -->
        <div v-if="showModal"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white">
                    <h3 class="text-base font-semibold text-gray-800">
                        {{ editTarget ? t('owner.documents.editTemplate') : t('owner.documents.newTemplate') }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveDoc" class="px-6 py-5 space-y-4">
                    <!-- slug -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.slug') }} <span class="text-red-500">*</span></label>
                        <input v-model="form.slug" :readonly="!!editTarget" required
                            placeholder="passport_copy"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] font-mono"
                            :class="editTarget ? 'bg-gray-50 text-gray-400' : ''"/>
                        <p v-if="!editTarget" class="text-xs text-gray-400 mt-1">{{ t('owner.documents.slugHint') }}</p>
                    </div>

                    <!-- name -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.name') }} <span class="text-red-500">*</span></label>
                        <input v-model="form.name" required placeholder="Копия паспорта"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                    </div>

                    <!-- category + type -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.categoryLabel') }} <span class="text-red-500">*</span></label>
                            <select v-model="form.category" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]">
                                <option value="">{{ t('owner.documents.select') }}</option>
                                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                                <option value="other">{{ t('owner.documents.other') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.typeLabel') }} <span class="text-red-500">*</span></label>
                            <select v-model="form.type" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]">
                                <option value="upload">{{ t('owner.documents.fileUpload') }}</option>
                                <option value="checkbox">{{ t('owner.documents.checkbox') }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- description -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.description') }}</label>
                        <textarea v-model="form.description" rows="3" maxlength="500"
                            :placeholder="t('owner.documents.descriptionPlaceholder')"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F] resize-none"></textarea>
                        <p class="text-xs text-right mt-0.5" :class="form.description.length >= 500 ? 'text-red-500' : 'text-gray-400'">
                            {{ form.description.length }}/500
                        </p>
                    </div>

                    <!-- sort_order + is_repeatable + is_active -->
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">{{ t('owner.documents.sortOrder') }}</label>
                            <input v-model.number="form.sort_order" type="number" min="0" max="999"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm outline-none focus:border-[#1BA97F]"/>
                        </div>
                        <div class="flex flex-col justify-end">
                            <label class="flex items-center gap-2 cursor-pointer pb-2.5">
                                <input type="checkbox" v-model="form.is_repeatable" class="accent-[#1BA97F] w-4 h-4"/>
                                <span class="text-sm text-gray-700">{{ t('owner.documents.repeatableCheckbox') }}</span>
                            </label>
                        </div>
                        <div class="flex flex-col justify-end">
                            <label class="flex items-center gap-2 cursor-pointer pb-2.5">
                                <input type="checkbox" v-model="form.is_active" class="accent-[#1BA97F] w-4 h-4"/>
                                <span class="text-sm text-gray-700">{{ t('owner.documents.activeCheckbox') }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- error -->
                    <p v-if="formError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl">{{ formError }}</p>

                    <!-- actions -->
                    <div class="flex gap-3 pt-1">
                        <button type="button" @click="closeModal"
                            class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                            {{ t('common.cancel') }}
                        </button>
                        <button type="submit" :disabled="saving"
                            class="flex-1 bg-[#1BA97F] text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-[#169B72] transition-colors disabled:opacity-60">
                            {{ saving ? t('owner.documents.saving') : (editTarget ? t('common.save') : t('owner.documents.create')) }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete confirm modal -->
        <div v-if="deleteTarget"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4"
            @click.self="deleteTarget = null">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="text-base font-semibold text-gray-800 mb-2">{{ t('owner.documents.deleteTemplate') }}</h3>
                <p class="text-sm text-gray-500 mb-1">
                    <span class="font-medium text-gray-700">{{ deleteTarget.name }}</span>
                </p>
                <p class="text-xs text-gray-400 mb-5">
                    {{ t('owner.documents.deleteWarning') }}
                </p>
                <p v-if="deleteError" class="text-sm text-red-500 bg-red-50 px-3 py-2 rounded-xl mb-4">{{ deleteError }}</p>
                <div class="flex gap-3">
                    <button @click="deleteTarget = null; deleteError = ''"
                        class="flex-1 border border-gray-200 text-gray-600 rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-gray-50 transition-colors">
                        {{ t('common.cancel') }}
                    </button>
                    <button @click="doDelete" :disabled="deleting"
                        class="flex-1 bg-red-600 text-white rounded-xl px-4 py-2.5 text-sm font-medium hover:bg-red-700 transition-colors disabled:opacity-60">
                        {{ deleting ? t('owner.documents.deleting') : t('common.delete') }}
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '@/api/index';

const { t } = useI18n();

const docs      = ref([]);
const loading   = ref(true);
const filterCat = ref('');

// modal
const showModal  = ref(false);
const editTarget = ref(null);
const saving     = ref(false);
const formError  = ref('');

// delete
const deleteTarget = ref(null);
const deleting     = ref(false);
const deleteError  = ref('');

const form = reactive({
    slug:          '',
    name:          '',
    category:      '',
    type:          'upload',
    description:   '',
    is_repeatable: false,
    is_active:     true,
    sort_order:    0,
});

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

const catLabelsMap = computed(() => Object.fromEntries(categories.value.map(c => [c.value, c.label])));
const catLabel  = (v) => catLabelsMap.value[v] ?? v;

async function load() {
    loading.value = true;
    try {
        const params = {};
        if (filterCat.value) params.category = filterCat.value;
        const { data } = await api.get('/admin/document-templates', { params });
        docs.value = data.data;
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    form.slug          = '';
    form.name          = '';
    form.category      = '';
    form.type          = 'upload';
    form.description   = '';
    form.is_repeatable = false;
    form.is_active     = true;
    form.sort_order    = 0;
    formError.value    = '';
}

function openCreate() {
    editTarget.value = null;
    resetForm();
    showModal.value = true;
}

function openEdit(doc) {
    editTarget.value       = doc;
    form.slug              = doc.slug;
    form.name              = doc.name;
    form.category          = doc.category;
    form.type              = doc.type;
    form.description       = doc.description ?? '';
    form.is_repeatable     = !!doc.is_repeatable;
    form.is_active         = !!doc.is_active;
    form.sort_order        = doc.sort_order ?? 0;
    formError.value        = '';
    showModal.value        = true;
}

function closeModal() {
    showModal.value  = false;
    editTarget.value = null;
}

async function saveDoc() {
    saving.value    = true;
    formError.value = '';
    try {
        const payload = {
            name:          form.name,
            category:      form.category,
            type:          form.type,
            description:   form.description || null,
            is_repeatable: form.is_repeatable,
            is_active:     form.is_active,
            sort_order:    form.sort_order,
        };
        if (!editTarget.value) {
            payload.slug = form.slug;
            await api.post('/admin/document-templates', payload);
        } else {
            await api.patch(`/admin/document-templates/${editTarget.value.id}`, payload);
        }
        closeModal();
        await load();
    } catch (e) {
        formError.value = e.response?.data?.message ?? t('owner.documents.saveError');
    } finally {
        saving.value = false;
    }
}

async function toggleActive(doc) {
    try {
        await api.patch(`/admin/document-templates/${doc.id}`, { is_active: !doc.is_active });
        doc.is_active = !doc.is_active;
    } catch {
        // ignore
    }
}

function confirmDelete(doc) {
    deleteTarget.value = doc;
    deleteError.value  = '';
}

async function doDelete() {
    deleting.value    = true;
    deleteError.value = '';
    try {
        await api.delete(`/admin/document-templates/${deleteTarget.value.id}`);
        deleteTarget.value = null;
        await load();
    } catch (e) {
        deleteError.value = e.response?.data?.message ?? t('owner.documents.deleteError');
    } finally {
        deleting.value = false;
    }
}

onMounted(load);
</script>
